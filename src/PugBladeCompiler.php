<?php

namespace Bkwld\LaravelPug;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\CompilerInterface;

class PugBladeCompiler extends BladeCompiler implements CompilerInterface
{
    use PugHandlerTrait;

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
