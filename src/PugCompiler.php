<?php

namespace Bkwld\LaravelPug;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\Compiler;

class PugCompiler extends Compiler implements PugHandlerInterface
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
