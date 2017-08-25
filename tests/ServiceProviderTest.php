<?php

namespace Phug\Test;

use ArrayAccess;
use Bkwld\LaravelPug\ServiceProvider;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;

include_once __DIR__ . '/helpers.php';

class Config implements ArrayAccess
{
    protected $data = array();

    public function __construct($source = null)
    {
        $this->data['source'] = $source;
    }

    public function get($input)
    {
        return isset($this->data[$input]) ? $this->data[$input] :array(
            'input' => $input,
        );
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function __toString()
    {
        return strval($this->data['source']);
    }
}

class LaravelTestApp implements Application, ArrayAccess
{
    protected $singletons = array();

    const VERSION = '4.0.0';

    public function version()
    {
        return static::VERSION;
    }

    public function basePath()
    {
        return __DIR__;
    }

    public function environment()
    {
        return 'dev';
    }

    public function runningInConsole()
    {
        return false;
    }

    public function isDownForMaintenance()
    {
        return false;
    }

    public function registerConfiguredProviders()
    {
    }

    public function register($provider, $options = [], $force = false)
    {
    }

    public function registerDeferredProvider($provider, $service = null)
    {
    }

    public function boot()
    {
    }

    public function booting($callback)
    {
    }

    public function booted($callback)
    {
    }

    public function getCachedServicesPath()
    {
        return '';
    }

    public function getCachedPackagesPath()
    {
        return '';
    }

    public function getCachedCompilePath()
    {
        return '';
    }

    public function bound($abstract)
    {
    }

    public function alias($abstract, $alias)
    {
    }

    public function tag($abstracts, $tags)
    {
    }

    public function tagged($tag)
    {
    }

    public function bind($abstract, $concrete = null, $shared = false)
    {
    }

    public function bindIf($abstract, $concrete = null, $shared = false)
    {
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->singletons[$abstract] = $concrete;
    }

    public function getSingleton($abstract)
    {
        return isset($this->singletons[$abstract])
            ? (is_callable($this->singletons[$abstract])
                ? call_user_func($this->singletons[$abstract], $this)
                : $this->singletons[$abstract]
            )
            : null;
    }

    public function extend($abstract, Closure $closure)
    {
    }

    public function instance($abstract, $instance)
    {
    }

    public function when($concrete)
    {
    }

    public function factory($abstract)
    {
    }

    public function make($abstract, array $parameters = [])
    {
        return new Config($abstract);
    }

    public function call($callback, array $parameters = [], $defaultMethod = null)
    {
    }

    public function resolved($abstract)
    {
    }

    public function resolving($abstract, Closure $callback = null)
    {
    }

    public function afterResolving($abstract, Closure $callback = null)
    {
    }

    public function get($id)
    {
    }

    public function has($id)
    {
    }

    public function offsetExists($offset)
    {
        return $this->getSingleton($offset) !== null;
    }

    public function offsetGet($offset)
    {
        return $this->getSingleton($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->singleton($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->singleton($offset, function () {});
    }
}

class Laravel5TestApp extends LaravelTestApp
{
    const VERSION = '5.0.0';
}

class Laravel3TestApp extends LaravelTestApp
{
    const VERSION = '3.0.0';
}

class Laravel4ServiceProvider extends ServiceProvider
{
    protected $currentPackage;

    public function package($package, $namespace = null, $path = null)
    {
        $this->currentPackage = $package;
    }

    public function getCurrentPackage()
    {
        return $this->currentPackage;
    }
}

class Laravel5ServiceProvider extends ServiceProvider
{
    protected $mergedConfig;

    protected $pub;

    public function mergeConfigFrom($path, $key)
    {
        $this->mergedConfig = func_get_args();
    }

    public function getMergedConfig()
    {
        return $this->mergedConfig;
    }

    public function getPub()
    {
        return $this->pub;
    }

    public function publishes(array $pub, $group = null)
    {
        $this->pub = $pub;
    }
}

class View
{
    protected $extensions = array();

    public function addExtension($extension)
    {
        $this->extensions[] = $extension;
    }

    public function getExtensions()
    {
        return $this->extensions;
    }
}

class Resolver
{
    protected $data = array();

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
class ServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LaravelTestApp
     */
    protected $app;

    /**
     * @var Laravel4ServiceProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->app = new LaravelTestApp();
        $this->app->singleton('files', function () {
            return new Filesystem();
        });
        Facade::setFacadeApplication($this->app);
        $this->provider = new Laravel4ServiceProvider($this->app);
    }

    /**
     * @covers ::version
     */
    public function testVersion()
    {
        self::assertSame(4, $this->provider->version());

        $app = new Laravel5TestApp();
        $app->singleton('files', function () {
            return new Filesystem();
        });
        $provider = new ServiceProvider($app);

        self::assertSame(5, $provider->version());
    }

    /**
     * @covers ::register
     */
    public function testRegister()
    {
        self::assertSame(null, $this->app->getSingleton('laravel-pug.pug'));
        self::assertSame(null, $this->app->getSingleton('Bkwld\LaravelPug\PugCompiler'));
        self::assertSame(null, $this->app->getSingleton('Bkwld\LaravelPug\PugBladeCompiler'));

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

        self::assertInstanceOf('Pug\Pug', $pug);
        self::assertInstanceOf(
            'Bkwld\LaravelPug\PugCompiler',
            $this->app->getSingleton('Bkwld\LaravelPug\PugCompiler')
        );
        self::assertInstanceOf(
            'Bkwld\LaravelPug\PugBladeCompiler',
            $this->app->getSingleton('Bkwld\LaravelPug\PugBladeCompiler')
        );
        self::assertStringEndsWith('/views', $defaultCache);
    }

    /**
     * @covers ::register
     * @covers ::registerLaravel5
     */
    public function testRegisterLaravel5()
    {
        $app = new Laravel5TestApp();
        $app->singleton('files', function () {
            return new Filesystem();
        });
        $provider = new Laravel5ServiceProvider($app);

        self::assertSame(null, $app->getSingleton('laravel-pug.pug'));
        self::assertSame(null, $app->getSingleton('Bkwld\LaravelPug\PugCompiler'));
        self::assertSame(null, $app->getSingleton('Bkwld\LaravelPug\PugBladeCompiler'));

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

        self::assertInstanceOf('Pug\Pug', $pug);
        self::assertInstanceOf(
            'Bkwld\LaravelPug\PugCompiler',
            $app->getSingleton('Bkwld\LaravelPug\PugCompiler')
        );
        self::assertInstanceOf(
            'Bkwld\LaravelPug\PugBladeCompiler',
            $app->getSingleton('Bkwld\LaravelPug\PugBladeCompiler')
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
        self::assertSame('laravel-pug::config', $this->provider->getConfig()['input']);

        $app = new Laravel5TestApp();
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
            'Bkwld\LaravelPug\PugCompiler',
            'Bkwld\LaravelPug\PugBladeCompiler',
            'laravel-pug.pug',
        ], $this->provider->provides());
    }

    /**
     * @covers ::boot
     * @covers ::bootLaravel4
     * @covers ::bootLaravel5
     * @covers ::registerPugCompiler
     * @covers ::registerPugBladeCompiler
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
            ["pug","pug.php","jade","jade.php","pug.blade","pug.blade.php","jade.blade","jade.blade.php"],
            $view->getExtensions()
        );
        self::assertSame('bkwld/laravel-pug', $this->provider->getCurrentPackage());
        self::assertInstanceOf('Illuminate\View\Engines\CompilerEngine', $resolver->get('pug'));
        self::assertInstanceOf('Illuminate\View\Engines\CompilerEngine', $resolver->get('pug.blade'));

        $app = new Laravel5TestApp();
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
            ["pug","pug.php","jade","jade.php","pug.blade","pug.blade.php","jade.blade","jade.blade.php"],
            $view->getExtensions()
        );
        self::assertCount(1, $provider->getPub());
        self::assertStringEndsWith('config.php', array_keys($provider->getPub())[0]);
        self::assertSame('laravel-pug.php', array_values($provider->getPub())[0]);
        self::assertInstanceOf('Illuminate\View\Engines\CompilerEngine', $resolver->get('pug'));
        self::assertInstanceOf('Illuminate\View\Engines\CompilerEngine', $resolver->get('pug.blade'));
    }

    /**
     * @covers                   ::boot
     * @expectedException        \Exception
     * @expectedExceptionMessage Unsupported Laravel version.
     */
    public function testBootUnsupportedLaravel()
    {
        $app = new Laravel3TestApp();
        $provider = new ServiceProvider($app);
        $provider->boot();
    }
}
