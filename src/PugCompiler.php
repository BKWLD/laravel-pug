<?php

namespace Bkwld\LaravelPug;

use Illuminate\View\Compilers\Compiler;
use Illuminate\View\Compilers\CompilerInterface;

class PugCompiler extends Compiler implements CompilerInterface
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
    public function compile($path)
    {
        $this->compileWith($path);
    }
}
