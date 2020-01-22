<?php

namespace Bkwld\LaravelPug;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Compilers\BladeCompiler;

class PugBladeCompiler extends BladeCompiler implements PugHandlerInterface
{
    use PugHandlerTrait;

    /**
     * Create a new compiler instance.
     *
     * @param array      $pugTarget
     * @param Filesystem $files
     * @param array      $config
     * @param string     $defaultCachePath
     */
    public function __construct(array $pugTarget, Filesystem $files, array $config, $defaultCachePath = null)
    {
        $this->construct($pugTarget, $files, $config, $defaultCachePath);
    }

    /**
     * Copy custom blade directives.
     */
    protected function enableBladeDirectives(): void
    {
        /** @var \Illuminate\View\Compilers\BladeCompiler $blade */
        $blade = Blade::getFacadeRoot();

        if ($blade && method_exists($blade, 'getCustomDirectives')) {
            foreach ($blade->getCustomDirectives() as $name => $directive) {
                $this->directive($name, $directive);
            }
        }
    }

    /**
     * Compile the view at the given path.
     *
     * @param string $path
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function compile($path = null): void
    {
        $app = Blade::getFacadeApplication();

        if (isset($app['view'])) {
            $this->enableBladeDirectives();
        }

        $this->footer = [];
        $this->compileWith($path, [$this, 'compileString']);
    }
}
