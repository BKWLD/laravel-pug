<?php

namespace Phug\Test\Blade;

use ArrayAccess;
use Bkwld\LaravelPug\PugBladeCompiler;
use Bkwld\LaravelPug\ServiceProvider;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

include_once __DIR__ . '/helpers.php';

class Config implements ArrayAccess
{
    protected $useSysTempDir = false;

    protected $data = array();

    public function __construct($source = null)
    {
        $this->data['source'] = $source;
    }

    public function setUseSysTempDir($useSysTempDir)
    {
        $this->useSysTempDir = $useSysTempDir;
    }

    public function get($input)
    {
        if ($this->useSysTempDir && in_array($input, ['laravel-pug', 'laravel-pug::config'])) {
            return [
                'assetDirectory' => __DIR__ . '/assets',
                'outputDirectory' => sys_get_temp_dir(),
                'defaultCache' => sys_get_temp_dir(),
            ];
        }

        return isset($this->data[$input]) ? $this->data[$input] : array(
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
    protected $useSysTempDir = false;

    protected $singletons = array();

    const VERSION = '4.0.0';

    public function version()
    {
        return static::VERSION;
    }

    public function setUseSysTempDir($useSysTempDir)
    {
        $this->useSysTempDir = $useSysTempDir;
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
        $config = new Config($abstract);
        $config->setUseSysTempDir($this->useSysTempDir);

        return $config;
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
        $this->singleton($offset, function () {
        });
    }
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

/**
 * @coversDefaultClass \Bkwld\LaravelPug\ServiceProvider
 */
class BladeDirectivesTest extends \PHPUnit_Framework_TestCase
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
        Blade::setFacadeApplication($this->app);
        $this->provider = new Laravel4ServiceProvider($this->app);
    }

    public function testCustomDirective()
    {
        $laravelVersion = intval(getenv('LARAVEL_VERSION'));

        if ($laravelVersion && $laravelVersion < 5) {
            self::markTestSkipped('Blade::directive only available since Laravel 5.0.');

            return;
        }

        $resolver = new EngineResolver();
        $fileSystem = new Filesystem();
        $this->app->singleton('Illuminate\View\Compilers\BladeCompiler', function () use ($fileSystem) {
            return new BladeCompiler($fileSystem, sys_get_temp_dir());
        });
        $resolver->register('blade', function () {
            return new CompilerEngine($this->app['Illuminate\View\Compilers\BladeCompiler']);
        });
        $finder = new FileViewFinder($fileSystem, []);
        $view = new Factory($resolver, $finder, new Dispatcher());
        $this->app['view.engine.resolver'] = $resolver;
        $this->app['view'] = $view;
        $this->provider->register();
        $this->provider->boot();
        Blade::directive('greet', function ($person) {
            return "Hello $person!";
        });
        $extensions = $view->getExtensions();
        if (isset($extensions['css'])) {
            unset($extensions['css']);
        }

        self::assertSame(
            [
                'jade.blade.php' => 'jade.blade',
                'jade.blade' => 'jade.blade',
                'pug.blade.php' => 'pug.blade',
                'pug.blade' => 'pug.blade',
                'jade.php' => 'pug',
                'jade' => 'pug',
                'pug.php' => 'pug',
                'pug' => 'pug',
                'blade.php' => 'blade',
                'php' => 'php',
            ],
            $extensions
        );

        /** @var CompilerEngine $engine */
        $engine = $resolver->resolve('pug.blade');
        /** @var PugBladeCompiler $compiler */
        $compiler = $engine->getCompiler();
        $compiler->setCachePath(sys_get_temp_dir());
        $path = realpath(__DIR__ . '/greeting.pug');
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
