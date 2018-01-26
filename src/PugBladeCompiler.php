<?php

namespace Bkwld\LaravelPug;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\CompilerInterface;

class PugBladeCompiler extends BladeCompiler implements CompilerInterface
{
    use PugHandlerTrait;

    /**
     * Create a new compiler instance.
     *
     * @param array      $pugTarget
     * @param Filesystem $files
     * @param array      $config
     */
    public function __construct(array $pugTarget, Filesystem $files, array $config)
    {
        $this->construct($pugTarget, $files, $config);
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
    public function compile($path = null)
    {
        $app = Blade::getFacadeApplication();
        if (isset($app['view'])) {
            $blade = Blade::getFacadeRoot();
            foreach ($blade->getCustomDirectives() as $name => $directive) {
                $this->directive($name, $directive);
            }
        }
        $this->footer = array();
        $this->compileWith($path, array($this, 'compileString'));
    }
}
