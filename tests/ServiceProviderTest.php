<?php

namespace Phug\Test;

use Bkwld\LaravelPug\ServiceProvider;
use Closure;
use Illuminate\Contracts\Foundation\Application;

if (!interface_exists('Illuminate\Contracts\Foundation\Application')) {
    include_once __DIR__ . '/LaravelLegacyApplicationInterface.php';
    include_once __DIR__ . '/LaravelApplicationInterface.php';
}

class MyApp implements Application
{
    const VERSION = '5.0.0';

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
}

/**
 * @coversDefaultClass \Bkwld\LaravelPug\ServiceProvider
 */
class ServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::version
     */
    public function testVersion()
    {
        $app = new MyApp();
        $provider = new ServiceProvider($app);

        self::assertSame(5, $provider->version());
    }
}
