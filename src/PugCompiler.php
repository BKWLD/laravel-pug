<?php

namespace Bkwld\LaravelPug;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\Compiler;
use Illuminate\View\Compilers\CompilerInterface;
use Pug\Pug;

class PugCompiler extends Compiler implements CompilerInterface
{
    use PugHandlerTrait;

    /**
     * Create a new compiler instance.
     *
     * @param Pug        $pug
     * @param Filesystem $files
     */
    public function __construct(Pug $pug, Filesystem $files)
    {
        parent::__construct($files, $this->getCachePath($pug));
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
    public function compile($path)
    {
        $this->compileWith($path);
    }
}
