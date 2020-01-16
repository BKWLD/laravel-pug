<?php

namespace Phug\Test;

use ArrayAccess;
use Bkwld\LaravelPug\Exception;
use Bkwld\LaravelPug\PugBladeCompiler;
use Bkwld\LaravelPug\PugCompiler;
use Bkwld\LaravelPug\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Engines\CompilerEngine;
use PHPUnit\Framework\TestCase;
use Pug\Assets;
use Pug\Pug;

include_once __DIR__ . '/helpers.php';

$file = __DIR__ . '/LaravelTestApp.php';
$contents = file_get_contents($file);

$contents = version_compare(PHP_VERSION, '5.6.0-dev', '>=')
    ? str_replace('(/*...$environments*/)', '(...$environments)', $contents)
    : str_replace('(...$environments)', '(/*...$environments*/)', $contents);

file_put_contents($file, $contents);

include_once __DIR__ . '/LaravelTestApp.php';
include_once __DIR__ . '/Laravel5ServiceProvider.php';

class EmptyConfigServiceProvider extends ServiceProvider
{
    public function getConfig()
    {
        return [];
    }

    public function getEngine()
    {
        return $this->getPugEngine();
    }
}

class View
{
    protected $extensions = [];

    public function addExtension($extension, $engine)
    {
        $this->extensions[$extension] = $engine;
    }

    public function getExtensions()
    {
        return $this->extensions;
    }
}

class Resolver
{
    protected $data = [];

    public function register($name, $callback)
    {
        $this->data[$name] = $callback;
    }

    public function get($name)
    {
        return call_user_func($this->data[$name]);
    }
}

/**
 * @coversDefaultClass \Bkwld\LaravelPug\ServiceProvider
 */
class ServiceProviderTest extends TestCase
{
    /**
     * @var LaravelTestApp
     */
    protected $app;

    /**
     * @var Laravel5ServiceProvider
     */
    protected $provider;

    protected function setUp(): void
    {
        $this->app = new LaravelTestApp();
        $this->app->singleton('files', function () {
            return new Filesystem();
        });
        Facade::setFacadeApplication($this->app);
        $this->provider = new Laravel5ServiceProvider($this->app);
    }

    /**
     * @covers ::register
     * @covers ::setDefaultOption
     * @covers ::getCompilerCreator
     * @covers ::getPugEngine
     * @covers ::getDefaultCache
     * @covers ::getAssetsDirectories
     * @covers ::getOutputDirectory
     */
    public function testRegister()
    {
        self::assertNull($this->app->getSingleton('laravel-pug.pug'));
        self::assertNull($this->app->getSingleton(PugCompiler::class));
        self::assertNull($this->app->getSingleton(PugBladeCompiler::class));

        $this->provider->register();
        /** @var \Pug\Pug $pug */
        $pug = $this->app->getSingleton('laravel-pug.pug');
        $defaultCache = $pug->getOption('defaultCache');
        if (!is_string($defaultCache)) {
            $defaultCache = $defaultCache->get('source');
        }
        if ($defaultCache === 'path.storage') {
            $defaultCache = '/views';
        }

        self::assertInstanceOf(Pug::class, $pug);
        self::assertInstanceOf(
            PugCompiler::class,
            $this->app->getSingleton(PugCompiler::class)
        );
        self::assertInstanceOf(
            PugBladeCompiler::class,
            $this->app->getSingleton(PugBladeCompiler::class)
        );
        self::assertStringEndsWith('/views', $defaultCache);
    }

    /**
     * @covers ::register
     */
    public function testRegisterLaravel5()
    {
        $app = new LaravelTestApp();
        $app->singleton('files', function () {
            return new Filesystem();
        });
        $provider = new Laravel5ServiceProvider($app);

        self::assertNull($app->getSingleton('laravel-pug.pug'));
        self::assertNull($app->getSingleton(PugCompiler::class));
        self::assertNull($app->getSingleton(PugBladeCompiler::class));

        $provider->register();
        /** @var \Pug\Pug $pug */
        $pug = $app->getSingleton('laravel-pug.pug');
        $defaultCache = $pug->getOption('defaultCache');
        if (!is_string($defaultCache)) {
            $defaultCache = $defaultCache->get('source');
        }
        if ($defaultCache === 'path.storage') {
            $defaultCache = '/framework/views';
        }
        $configs = $provider->getMergedConfig();

        self::assertInstanceOf(Pug::class, $pug);
        self::assertInstanceOf(
            PugCompiler::class,
            $app->getSingleton(PugCompiler::class)
        );
        self::assertInstanceOf(
            PugBladeCompiler::class,
            $app->getSingleton(PugBladeCompiler::class)
        );
        self::assertStringEndsWith('/framework/views', $defaultCache);
        self::assertCount(2, $configs);
        self::assertStringEndsWith('config.php', $configs[0]);
        self::assertSame('laravel-pug', $configs[1]);
    }

    /**
     * @covers ::getConfig
     */
    public function testGetConfig()
    {
        $app = new LaravelTestApp();
        $app->singleton('files', function () {
            return new Filesystem();
        });
        $provider = new ServiceProvider($app);

        self::assertSame('laravel-pug', $provider->getConfig()['input']);
    }

    /**
     * @covers ::provides
     */
    public function testProvides()
    {
        self::assertSame([
            PugCompiler::class,
            PugBladeCompiler::class,
            'laravel-pug.pug',
        ], $this->provider->provides());
    }

    /**
     * @covers ::boot
     * @covers ::registerPugCompiler
     * @covers ::registerPugBladeCompiler
     * @covers ::getEngineResolver
     *
     * @throws Exception
     */
    public function testBoot()
    {
        $view = new View();
        $resolver = new Resolver();
        $this->app['view.engine.resolver'] = $resolver;
        $this->app['view'] = $view;
        $this->provider->register();
        $this->provider->boot();

        self::assertSame(
            ['pug', 'pug.blade', 'blade.pug'],
            array_keys($view->getExtensions())
        );
        self::assertInstanceOf(CompilerEngine::class, $resolver->get('pug'));
        self::assertInstanceOf(CompilerEngine::class, $resolver->get('pug.blade'));

        $app = new LaravelTestApp();
        $app->singleton('files', function () {
            return new Filesystem();
        });
        $provider = new Laravel5ServiceProvider($app);
        $resolver = new Resolver();
        $app['view.engine.resolver'] = $resolver;
        $view = new View();
        $pug = $view;
        $app['view'] = $pug;
        $provider->register();
        $provider->boot();

        self::assertSame(
            ['pug', 'pug.blade', 'blade.pug'],
            array_keys($view->getExtensions())
        );
        self::assertCount(1, $provider->getPub());
        self::assertStringEndsWith('config.php', array_keys($provider->getPub())[0]);
        self::assertSame('laravel-pug.php', array_values($provider->getPub())[0]);
        self::assertInstanceOf(CompilerEngine::class, $resolver->get('pug'));
        self::assertInstanceOf(CompilerEngine::class, $resolver->get('pug.blade'));
    }

    /**
     * @covers ::register
     * @covers ::setDefaultOption
     * @covers ::getPugAssets
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::construct
     *
     * @throws Exception
     */
    public function testView()
    {
        $this->app->setUseSysTempDir(true);
        $view = new View();
        $resolver = new Resolver();
        $this->app['view.engine.resolver'] = $resolver;
        $this->app['view'] = $view;
        $this->provider->register();
        $this->provider->boot();
        $path = __DIR__ . '/assets.pug';

        /** @var CompilerEngine $pug */
        $pug = $resolver->get('pug');

        self::assertSame(
            '<head><script src="js/app.min.js"></script></head>',
            preg_replace(
                '/\s{2,}/',
                '',
                $this->app['view.engine.resolver']->get('pug')->get($path)
            )
        );

        $contents = file_get_contents(sys_get_temp_dir() . '/js/app.min.js');

        self::assertSame('a();b();', trim($contents));

        unlink(sys_get_temp_dir() . '/js/app.min.js');
        unlink($pug->getCompiler()->getCompiledPath($path));

        /** @var Pug $pugEngine */
        $pugEngine = $this->app['laravel-pug.pug'];
        $method = method_exists($pugEngine, 'renderFile') ? [$pugEngine, 'renderFile'] : [$pugEngine, 'render'];

        /** @var Assets $assets */
        $assets = $this->app['laravel-pug.pug-assets'];
        $assets->setEnvironment('dev');

        self::assertSame(
            '<head><minify>app<script src="foo.js"></script><script src="bar.js"></script></minify></head>',
            preg_replace('/\s{2,}/', '', call_user_func($method, $path))
        );

        @unlink($pug->getCompiler()->getCompiledPath($path));

        $assets->setEnvironment('production');

        self::assertSame(
            '<head><script src="js/app.min.js"></script></head>',
            preg_replace(
                '/\s{2,}/',
                '',
                $this->app['view.engine.resolver']->get('pug')->get($path)
            )
        );

        self::assertSame('a();b();', trim($contents));

        unlink(sys_get_temp_dir() . '/js/app.min.js');
        unlink($pug->getCompiler()->getCompiledPath($path));

        $assets->unsetMinify();

        self::assertSame(
            '<head><minify>app<script src="foo.js"></script><script src="bar.js"></script></minify></head>',
            preg_replace('/\s{2,}/', '', call_user_func($method, $path))
        );

        @unlink($pug->getCompiler()->getCompiledPath($path));

        if (!method_exists($pugEngine, 'renderFile')) {
            return; // Skip for Pug-php < 3
        }

        $path = __DIR__.'/example2.pug.blade';

        self::assertSame(
            '<h1>{{ \'Start\' }}</h1><h1>Pug is there</h1><p>{{ $sentence }}</p>@if (1 === 1)<div>Go</div>@endif',
            preg_replace('/\s{2,}/', '', call_user_func($method, $path))
        );

        @unlink($pug->getCompiler()->getCompiledPath($path));

        $path = __DIR__.'/composite-extension/welcome.pug.blade';

        self::assertSame(
            '<h2>test from layout block content</h2><h2>test from welcome</h2>',
            preg_replace('/\s{2,}/', '', call_user_func($method, $path))
        );

        @unlink($pug->getCompiler()->getCompiledPath($path));
    }

    public function testWithEmptyConfig()
    {
        $app = new LaravelTestApp();
        $provider = new EmptyConfigServiceProvider($app);

        self::assertSame('resource/views', $provider->getEngine()->getOption('basedir'));
    }
}
