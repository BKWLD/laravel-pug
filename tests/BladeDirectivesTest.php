<?php

namespace Phug\Test\Blade;

use ArrayAccess;
use Bkwld\LaravelPug\PugBladeCompiler;
use Bkwld\LaravelPug\ServiceProvider;
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

$file = __DIR__ . '/LaravelTestApp.php';
$contents = file_get_contents($file);

$contents = version_compare(PHP_VERSION, '5.6.0-dev', '>=')
    ? str_replace('(/*...$environments*/)', '(...$environments)', $contents)
    : str_replace('(...$environments)', '(/*...$environments*/)', $contents);

file_put_contents($file, $contents);

include_once __DIR__ . '/LaravelTestApp.php';

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
class BladeDirectivesTest extends TestCase
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
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::compileWith
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::extractPath
     */
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

        if (floatval(getenv('LARAVEL_VERSION')) >= 5.8) {
            Blade::swap(new \Illuminate\View\Compilers\BladeCompiler(new Filesystem(), sys_get_temp_dir()));
        }

        Blade::directive('greet', function ($person) {
            $person = eval("return $person;");

            return "Hello $person!";
        });
        $extensions = $view->getExtensions();
        if (isset($extensions['css'])) {
            unset($extensions['css']);
        }

        self::assertSame(
            [
                'jade.blade.php' => 'pug.blade',
                'jade.blade' => 'pug.blade',
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
