<?php

namespace Phug\Test;

use Bkwld\LaravelPug\ServiceProvider;

class Laravel5ServiceProvider extends ServiceProvider
{
    protected $mergedConfig = [];

    protected $pub;

    protected $currentPackage;

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

    public function package($package, $namespace = null, $path = null)
    {
        $this->currentPackage = $package;
    }

    public function getCurrentPackage()
    {
        return $this->currentPackage;
    }
}
