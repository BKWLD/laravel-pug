<?php

namespace Phug\Test\Blade;

use Bkwld\LaravelPug\Exception;
use Bkwld\LaravelPug\PugBladeCompiler;
use Phug\Test\Laravel5ServiceProvider;
use Phug\Test\LaravelTestApp;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use PHPUnit\Framework\TestCase;

include_once __DIR__.'/helpers.php';
include_once __DIR__.'/LaravelTestApp.php';
include_once __DIR__.'/Laravel5ServiceProvider.php';

/**
 * @coversDefaultClass \Bkwld\LaravelPug\ServiceProvider
 */
class BladeDirectivesTest extends TestCase
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
        Blade::setFacadeApplication($this->app);
        $this->provider = new Laravel5ServiceProvider($this->app);
    }

    /**
     * @covers ::getCompilerCreator
     * @covers ::getPugEngine
     * @covers ::getEngineResolver
     * @covers ::getDefaultCache
     * @covers ::getAssetsDirectories
     * @covers ::getOutputDirectory
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::construct
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::setCachePath
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getPug
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getCompiler
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::compileWith
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::extractPath
     *
     * @throws Exception
     */
    public function testCustomDirective()
    {
        $resolver = new EngineResolver();
        $fileSystem = new Filesystem();
        $this->app->singleton(BladeCompiler::class, function () use ($fileSystem) {
            return new BladeCompiler($fileSystem, sys_get_temp_dir());
        });
        $resolver->register('blade', function () {
            return new CompilerEngine($this->app[BladeCompiler::class]);
        });
        $finder = new FileViewFinder($fileSystem, []);
        $view = new Factory($resolver, $finder, new Dispatcher());
        $this->app['view.engine.resolver'] = $resolver;
        $this->app['view'] = $view;
        $this->provider->register();
        $this->provider->boot();

        Blade::swap(new BladeCompiler(new Filesystem(), sys_get_temp_dir()));

        Blade::directive('greet', function ($person) {
            $person = eval("return $person;");

            return "Hello $person!";
        });
        $extensions = $view->getExtensions();

        foreach (['css', 'php', 'html', 'blade.php'] as $ignoredExtension) {
            if (isset($extensions[$ignoredExtension])) {
                unset($extensions[$ignoredExtension]);
            }
        }

        self::assertSame(
            [
                'blade.pug' => 'pug.blade',
                'pug.blade' => 'pug.blade',
                'pug'       => 'pug',
            ],
            $extensions
        );

        /** @var CompilerEngine $engine */
        $engine = $resolver->resolve('pug.blade');
        /** @var PugBladeCompiler $compiler */
        $compiler = $engine->getCompiler();
        $compiler->setCachePath(sys_get_temp_dir());
        $path = realpath(__DIR__.'/greeting.pug');
        $compiledPath = $compiler->getCompiledPath($path);
        $compiler->compile($path);
        ob_start();
        include $compiledPath;
        $contents = ob_get_contents();
        ob_end_clean();

        self::assertSame('Hello Bob!', $contents);

        // Cleanup
        if (file_exists($compiledPath)) {
            unlink($compiledPath);
            clearstatcache();
        }
    }
}
