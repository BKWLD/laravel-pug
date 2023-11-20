<?php

namespace Bkwld\LaravelPug;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\Compiler;

class PugCompiler extends Compiler implements PugHandlerInterface
{
    use PugHandlerTrait;

    /**
     * Create a new compiler instance.
     */
    public function __construct(
        array $pugTarget,
        Filesystem $files,
        array $config,
        $defaultCachePath = null,
        $compiler = null
    ) {
        $this->construct($pugTarget, $files, $config, $defaultCachePath, $compiler);
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
    public function compile($path): void
    {
        $this->compileWith($path);
    }
}
