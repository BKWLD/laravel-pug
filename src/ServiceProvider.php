<?php

namespace Bkwld\LaravelPug;

// Dependencies
use Closure;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Phug\Component\ComponentExtension;
use Pug\Assets;
use Pug\Pug;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * @var Assets
     */
    protected $assets;

    /**
     * @var ComponentExtension
     */
    protected $componentExtension;

    protected function setDefaultOption(Pug $pug, string $name, $value): void
    {
        if (!$pug->hasOption($name)) {
            $pug->setOption($name, call_user_func($value));
        }
    }

    protected function getDefaultCache(): string
    {
        return storage_path('/framework/views');
    }

    protected function getAssetsDirectories(): array
    {
        return array_map(function ($params) {
            [$function, $arg] = $params;

            return function_exists($function) ? call_user_func($function, $arg) : null;
        }, [
            ['resource_path', 'assets'],
            ['app_path', 'views/assets'],
            ['app_path', 'assets'],
            ['app_path', 'views'],
            ['app_path', ''],
            ['base_path', ''],
        ]);
    }

    public function getPugEngine(): Pug
    {
        $config = $this->getConfig();

        if (!isset($config['extensions']) && $this->app['view']) {
            $extensions = array_keys(array_filter($this->app['view']->getExtensions(), function ($engine) {
                $engines = explode('.', $engine);

                return in_array('pug', $engines);
            }));

            $config['extensions'] = array_map(function ($extension) {
                return ".$extension";
            }, $extensions);
        }

        $pug = new Pug($config);

        if ($config['assets'] ?? true) {
            $this->assets = new Assets($pug);
            $getEnv = ['App', 'environment'];
            $this->assets->setEnvironment(is_callable($getEnv) ? call_user_func($getEnv) : 'production');
        }

        if ($config['component'] ?? true) {
            ComponentExtension::enable($pug);

            $this->componentExtension = $pug->getModule(ComponentExtension::class);
        }

        // Determine the cache dir if not configured
        $this->setDefaultOption($pug, 'defaultCache', [$this, 'getDefaultCache']);

        // Determine assets input directory
        $this->setDefaultOption($pug, 'assetDirectory', [$this, 'getAssetsDirectories']);

        // Determine assets output directory
        $this->setDefaultOption($pug, 'outputDirectory', [$this, 'getOutputDirectory']);

        // Optimize paths
        $this->setDefaultOption($pug, 'paths', array_unique($pug->getOption('paths')));

        $pug->getCompiler()->setOption('mixin_keyword', $pug->getOption('mixin_keyword'));

        return $pug;
    }

    protected function getPugAssets(): ?Assets
    {
        return $this->app['laravel-pug.pug'] ? $this->assets : null;
    }

    protected function getOutputDirectory(): ?string
    {
        return function_exists('public_path') ? public_path() : null;
    }

    protected function getCompilerCreator($compilerClass): Closure
    {
        return function ($app) use ($compilerClass) {
            return new $compilerClass(
                [$app, 'laravel-pug.pug'],
                $app['files'],
                $this->getConfig(),
                $this->getDefaultCache()
            );
        };
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-pug');

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
            PugCompiler::class,
            $this->getCompilerCreator(PugCompiler::class)
        );

        // Bind the Pug Blade compiler
        $this->app->singleton(
            PugBladeCompiler::class,
            $this->getCompilerCreator(PugBladeCompiler::class)
        );
    }

    /**
     * Bootstrap the application events.
     *
     * @throws Exception for unsupported Laravel version
     */
    public function boot(): void
    {
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-pug.php'),
            ], 'laravel-pug');
        }

        // Register compilers
        $this->registerPugCompiler();
        $this->registerPugBladeCompiler();
    }

    /**
     * Returns the view engine resolver according to current framework (laravel/lumen).
     */
    public function getEngineResolver(): EngineResolver
    {
        return isset($this->app['view.engine.resolver'])
            ? $this->app['view.engine.resolver']
            : $this->app['view']->getEngineResolver();
    }

    /**
     * Register the regular Pug compiler.
     */
    public function registerPugCompiler(string $subExtension = ''): void
    {
        $mainExtension = 'pug'.$subExtension;

        // Add resolver
        $this->getEngineResolver()->register($mainExtension, function () use ($subExtension) {
            return new CompilerEngine($this->app['Bkwld\LaravelPug\Pug'.ucfirst(ltrim($subExtension, '.')).'Compiler']);
        });

        $this->app['view']->addExtension($mainExtension, $mainExtension);

        if ($subExtension !== '') {
            $subExtensionPrefix = substr($subExtension, 1).'.';
            $this->app['view']->addExtension($subExtensionPrefix.'pug', $mainExtension);
        }
    }

    /**
     * Register the blade compiler compiler.
     */
    public function registerPugBladeCompiler(): void
    {
        $this->registerPugCompiler('.blade');
    }

    /**
     * Get the configuration from the current app config file.
     */
    public function getConfig(): array
    {
        $config = $this->app->make('config');

        return array_merge([
            'allow_composite_extensions' => true,
        ], [
            'paths' => $config->get('view.paths'),
            'debug' => $config->get('app.debug'),
        ], $this->app->make('config')->get('laravel-pug'));
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            PugCompiler::class,
            PugBladeCompiler::class,
            'laravel-pug.pug',
        ];
    }
}
