<?php

namespace Phug\Test;

use ArrayAccess;
use Closure;
use Illuminate\Contracts\Foundation\Application;

class LaravelTestApp implements Application, ArrayAccess
{
    protected $useSysTempDir = false;

    protected $singletons = [];

    const VERSION = '6.0.0';

    public function version()
    {
        return static::VERSION;
    }

    public function setUseSysTempDir($useSysTempDir)
    {
        $this->useSysTempDir = $useSysTempDir;
    }

    public function basePath($path = '')
    {
        return __DIR__;
    }

    public function environment(...$environments)
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
        $config->set('app.debug', true);
        $config->set('view.paths', ['resource/views']);

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

    public function runningUnitTests()
    {
    }

    public function bootstrapPath($path = '')
    {
    }

    public function configPath($path = '')
    {
    }

    public function databasePath($path = '')
    {
    }

    public function environmentPath()
    {
    }

    public function resourcePath($path = '')
    {
    }

    public function storagePath()
    {
    }

    public function resolveProvider($provider)
    {
    }

    public function bootstrapWith(array $bootstrappers)
    {
    }

    public function configurationIsCached()
    {
    }

    public function detectEnvironment(Closure $callback)
    {
    }

    public function environmentFile()
    {
    }

    public function environmentFilePath()
    {
    }

    public function getCachedConfigPath()
    {
    }

    public function getCachedRoutesPath()
    {
    }

    public function getLocale()
    {
    }

    public function getNamespace()
    {
    }

    public function getProviders($provider)
    {
    }

    public function hasBeenBootstrapped()
    {
    }

    public function loadDeferredProviders()
    {
    }

    public function loadEnvironmentFrom($file)
    {
    }

    public function routesAreCached()
    {
    }

    public function setLocale($locale)
    {
    }

    public function shouldSkipMiddleware()
    {
    }

    public function terminate()
    {
    }

    public function addContextualBinding($concrete, $abstract, $implementation)
    {
    }

    public function flush()
    {
    }

    public function singletonIf($abstract, $concrete = null)
    {
    }
}
