<?php

namespace Bkwld\LaravelPug;

// Dependencies
use Illuminate\View\Engines\CompilerEngine;
use Pug\Assets;
use Pug\Pug;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * @var Assets
     */
    protected $assets;

    protected function setDefaultOption(Pug $pug, $name, $value)
    {
        if (method_exists($pug, 'hasOption') && !$pug->hasOption($name)) {
            $pug->setCustomOption($name, call_user_func($value));

            return;
        }

        // @codeCoverageIgnoreStart
        try {
            $pug->getOption($name);
        } catch (\InvalidArgumentException $exception) {
            $pug->setCustomOption($name, call_user_func($value));
        }
        // @codeCoverageIgnoreEnd
    }

    protected function getDefaultCache()
    {
        return storage_path($this->version() >= 5 ? '/framework/views' : '/views');
    }

    protected function getAssetsDirectories()
    {
        return array_map(function ($params) {
            list($function, $arg) = $params;

            return function_exists($function) ? call_user_func($function, $arg) : null;
        }, array(
            array('resource_path', 'assets'),
            array('app_path', 'views/assets'),
            array('app_path', 'assets'),
            array('app_path', 'views'),
            array('app_path', ''),
            array('base_path', ''),
        ));
    }

    protected function getPugEngine()
    {
        $config = $this->getConfig();

        if (!isset($config['basedir']) && function_exists('resource_path')) {
            $config['basedir'] = resource_path('views');
        }

        if (!isset($config['extensions']) && $this->app['view']) {
            $extensions = array_keys(array_filter($this->app['view']->getExtensions(), function ($engine) {
                $engines = explode('.', $engine);

                return in_array('pug', $engines) || in_array('jade', $engines);
            }));

            $config['extensions'] = array_map(function ($extension) {
                return ".$extension";
            }, $extensions);
        }

        $pug = new Pug($config);
        $this->assets = new Assets($pug);
        $getEnv = array('App', 'environment');
        $this->assets->setEnvironment(is_callable($getEnv) ? call_user_func($getEnv) : 'production');

        // Determine the cache dir if not configured
        $this->setDefaultOption($pug, 'defaultCache', array($this, 'getDefaultCache'));

        // Determine assets input directory
        $this->setDefaultOption($pug, 'assetDirectory', array($this, 'getAssetsDirectories'));

        // Determine assets output directory
        $this->setDefaultOption($pug, 'outputDirectory', array($this, 'getOutputDirectory'));

        return $pug;
    }

    protected function getPugAssets()
    {
        return $this->app['laravel-pug.pug'] ? $this->assets : null;
    }

    protected function getOutputDirectory()
    {
        return function_exists('public_path') ? public_path() : null;
    }

    protected function getCompilerCreator($compilerClass)
    {
        return function ($app) use ($compilerClass) {
            return new $compilerClass(
                array($app, 'laravel-pug.pug'),
                $app['files'],
                $this->getConfig(),
                $this->getDefaultCache()
            );
        };
    }

    /**
     * Get the major Laravel version number.
     *
     * @return int
     */
    public function version()
    {
        $app = $this->app;
        $tab = explode('Laravel Components ', $app->version());

        return intval(empty($tab[1]) ? $app::VERSION : trim($tab[1], ' ^~><=*.()'));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Version specific registering
        if ($this->version() >= 5) {
            $this->registerLaravel5();
        }

        // Bind the pug assets module
        $this->app->singleton('laravel-pug.pug-assets', function () {
            return $this->getPugAssets();
        });

        // Bind the package-configured Pug instance
        $this->app->singleton('laravel-pug.pug', function () {
            return $this->getPugEngine();
        });

        // Bind the Pug compiler
        $this->app->singleton(
            'Bkwld\LaravelPug\PugCompiler',
            $this->getCompilerCreator('\Bkwld\LaravelPug\PugCompiler')
        );

        // Bind the Pug Blade compiler
        $this->app->singleton(
            'Bkwld\LaravelPug\PugBladeCompiler',
            $this->getCompilerCreator('\Bkwld\LaravelPug\PugBladeCompiler')
        );
    }

    /**
     * Register specific logic for Laravel 5. Merges package config with user config.
     *
     * @return void
     */
    public function registerLaravel5()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'laravel-pug');
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Version specific booting
        switch ($this->version()) {
            case 4: $this->bootLaravel4(); break;
            case 5: $this->bootLaravel5And6(); break;
            case 6: $this->bootLaravel5And6(); break;
            default: throw new Exception('Unsupported Laravel version.');
        }

        // Register compilers
        $this->registerPugCompiler();
        $this->registerPugBladeCompiler();
    }

    /**
     * Boot specific logic for Laravel 4. Tells Laravel about the package for auto
     * namespacing of config files.
     *
     * @return void
     */
    public function bootLaravel4()
    {
        $this->package('bkwld/laravel-pug');
    }

    /**
     * Boot specific logic for Laravel 5 and 6. Registers the config file for publishing
     * to app directory.
     *
     * @return void
     */
    public function bootLaravel5And6()
    {
        if (function_exists('config_path')) {
            $this->publishes(array(
                __DIR__ . '/../config/config.php' => config_path('laravel-pug.php'),
            ), 'laravel-pug');
        }
    }

    /**
     * Returns the view engine resolver according to current framework (laravel/lumen).
     *
     * @return \Illuminate\View\Engines\EngineResolver
     */
    public function getEngineResolver()
    {
        return isset($this->app['view.engine.resolver'])
            ? $this->app['view.engine.resolver']
            : $this->app['view']->getEngineResolver();
    }

    /**
     * Register the regular Pug compiler.
     *
     * @return void
     */
    public function registerPugCompiler($subExtension = '')
    {
        // Add resolver
        $this->getEngineResolver()->register('pug' . $subExtension, function () use ($subExtension) {
            return new CompilerEngine($this->app['Bkwld\LaravelPug\Pug' . ucfirst(ltrim($subExtension, '.')) . 'Compiler']);
        });

        // Add extensions
        $this->app['view']->addExtension('pug' . $subExtension, 'pug' . $subExtension);
        $this->app['view']->addExtension('pug' . $subExtension . '.php', 'pug' . $subExtension);
        $this->app['view']->addExtension('jade' . $subExtension, 'pug' . $subExtension);
        $this->app['view']->addExtension('jade' . $subExtension . '.php', 'pug' . $subExtension);
    }

    /**
     * Register the blade compiler compiler.
     *
     * @return void
     */
    public function registerPugBladeCompiler()
    {
        $this->registerPugCompiler('.blade');
    }

    /**
     * Get the configuration, which is keyed differently in L5 vs l4.
     *
     * @return array
     */
    public function getConfig()
    {
        $key = $this->version() >= 5 ? 'laravel-pug' : 'laravel-pug::config';

        return array_merge(array(
            'allow_composite_extensions' => true,
        ), $this->app->make('config')->get($key));
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'Bkwld\LaravelPug\PugCompiler',
            'Bkwld\LaravelPug\PugBladeCompiler',
            'laravel-pug.pug',
        );
    }
}
