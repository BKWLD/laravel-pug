<?php

namespace Bkwld\LaravelPug;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\CompilerInterface;
use InvalidArgumentException;
use Phug\CompilerInterface as PhugCompiler;
use Pug\Pug;

interface PugHandlerInterface extends CompilerInterface
{
    /**
     * Common pug compiler constructor.
     *
     * @param array      $pugTarget
     * @param Filesystem $files
     * @param array      $config
     */
    public function construct(array $pugTarget, Filesystem $files, array $config, $defaultCachePath = null);

    /**
     * Lazy load Pug and return the instance.
     *
     * @return Pug
     */
    public function getPug();

    /**
     * Returns cache path.
     *
     * @return string $cachePath
     */
    public function getCachePath();

    /**
     * Get an option from pug engine or default value.
     *
     * @param string $name
     * @param null   $default
     *
     * @return mixed|null
     */
    public function getOption($name, $default = null);

    /**
     * @param string $cachePath
     */
    public function setCachePath($cachePath);

    /**
     * Determine if the view at the given path is expired.
     *
     * @param string $path
     *
     * @return bool
     */
    public function isExpired($path): bool;

    /**
     * Return path and set it or get it from the instance.
     *
     * @param string $path
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public function extractPath($path): ?string;

    /**
     * Returns the object the more appropriate to compile.
     */
    public function getCompiler(): PhugCompiler;

    /**
     * Compile the view at the given path.
     *
     * @param string        $path
     * @param callable|null $callback
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    public function compileWith($path, callable $callback = null): void;
}
