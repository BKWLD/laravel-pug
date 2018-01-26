<?php

namespace Bkwld\LaravelPug;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Pug\Pug;

trait PugHandlerTrait
{
    /**
     * @var array
     */
    protected $pugTarget;

    /**
     * @var Pug
     */
    protected $pug;

    /**
     * Common pug compiler constructor.
     *
     * @param array      $pugTarget
     * @param Filesystem $files
     * @param array      $config
     */
    public function construct(array $pugTarget, Filesystem $files, array $config)
    {
        $this->pugTarget = $pugTarget;
        $cachePath = null;
        foreach (['cache_dir', 'cache', 'defaultCache'] as $name) {
            if (isset($config[$name])) {
                $cachePath = $config[$name];
                break;
            }
        }
        if (!$cachePath) {
            $cachePath = $this->getCachePath();
        }

        parent::__construct($files, $cachePath);
    }

    /**
     * Lazy load Pug and return the instance.
     *
     * @return Pug
     */
    public function getPug()
    {
        if (!$this->pug) {
            $this->pug = $this->pugTarget[0][$this->pugTarget[1]];
        }

        return $this->pug;
    }

    /**
     * Returns cache path.
     *
     * @return string $cachePath
     */
    public function getCachePath()
    {
        $cachePath = $this->getOption('cache');

        return is_string($cachePath) ? $cachePath : $this->getOption('defaultCache');
    }

    /**
     * Get an option from pug engine or default value.
     *
     * @param string $name
     * @param null   $default
     *
     * @return mixed|null
     */
    public function getOption($name, $default = null)
    {
        $pug = $this->getPug();

        if (method_exists($pug, 'hasOption') && !$pug->hasOption($name)) {
            return $default;
        }

        return $pug->getOption($name);
    }

    /**
     * @param string $cachePath
     */
    public function setCachePath($cachePath)
    {
        $this->cachePath = $cachePath;
        $this->getPug()->setOption('cache', $cachePath);
    }

    /**
     * Returns true if the path has an expired imports linked.
     *
     * @param $path
     *
     * @return bool
     */
    private function hasExpiredImport($path)
    {
        $compiled = $this->getCompiledPath($path);
        $importsMap = $compiled . '.imports.serialize.txt';
        $files = $this->files;

        if (!$files->exists($importsMap)) {
            return true;
        }

        $importPaths = unserialize($files->get($importsMap));
        $time = $files->lastModified($compiled);
        foreach ($importPaths as $importPath) {
            if (!$files->exists($importPath) || $files->lastModified($importPath) >= $time) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the view at the given path is expired.
     *
     * @param string $path
     *
     * @return bool
     */
    public function isExpired($path)
    {
        if (!$this->getOption('cache') || parent::isExpired($path)) {
            return true;
        }

        return $this->getPug() instanceof \Phug\Renderer && $this->hasExpiredImport($path);
    }

    /**
     * Return path and set it or get it from the instance.
     *
     * @param string $path
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function extractPath($path)
    {
        if ($path && method_exists($this, 'setPath')) {
            $this->setPath($path);
        }
        if (!$path && method_exists($this, 'getPath')) {
            $path = $this->getPath();
        }
        if (!$path) {
            throw new InvalidArgumentException('Missing path argument.');
        }

        return $path;
    }

    /**
     * Compile the view at the given path.
     *
     * @param string        $path
     * @param callable|null $callback
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function compileWith($path, callable $callback = null)
    {
        $path = $this->extractPath($path);
        if ($this->cachePath) {
            $pug = $this->getPug();
            $compiled = $this->getCompiledPath($path);
            $contents = $pug->compile($this->files->get($path), $path);
            if ($callback) {
                $contents = call_user_func($callback, $contents);
            }
            if ($pug instanceof \Phug\Renderer) {
                $this->files->put(
                    $compiled . '.imports.serialize.txt',
                    serialize($pug->getCompiler()->getCurrentImportPaths())
                );
            }
            $this->files->put($compiled, $contents);
        }
    }
}
