<?php

namespace Bkwld\LaravelPug;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use Phug\Compiler;
use Phug\CompilerInterface;
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
    public function construct(array $pugTarget, Filesystem $files, array $config, $defaultCachePath = null)
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
            $cachePath = $defaultCachePath ?: $this->getCachePath();
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
        if ($this->cachePath) {
            return $this->cachePath;
        }

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

        try {
            if (method_exists($pug, 'hasOption') && !$pug->hasOption($name)) {
                throw new InvalidArgumentException('invalid option');
            }

            return $pug->getOption($name);
        } catch (InvalidArgumentException $exception) {
            return $default;
        }
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
     */
    private function hasExpiredImport($path): bool
    {
        $compiled = $this->getCompiledPath($path);
        $importsMap = $compiled.'.imports.serialize.txt';
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
    public function isExpired($path): bool
    {
        if (!$this->cachePath || parent::isExpired($path)) {
            return true;
        }

        return is_subclass_of('\Pug\Pug', '\Phug\Renderer') && $this->hasExpiredImport($path);
    }

    /**
     * Return path and set it or get it from the instance.
     *
     * @param string $path
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public function extractPath($path): ?string
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
     * Returns the object the more appropriate to compile.
     */
    public function getCompiler(): CompilerInterface
    {
        return $this->getPug()->getCompiler();
    }

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
    public function compileWith($path, callable $callback = null): void
    {
        $path = $this->extractPath($path);

        if ($this->cachePath) {
            $pug = $this->getCompiler();
            $compiled = $this->getCompiledPath($path);
            $engine = $this->getPug();
            $importCarrier = null;

            $contents = $engine->compile($this->files->get($path), $path);

            if ($callback) {
                $contents = call_user_func($callback, $contents);
            }

            if ($pug instanceof Compiler) {
                if ($importCarrier === null) {
                    $importCarrier = $engine->getCompiler();
                }

                $this->files->put(
                    $compiled.'.imports.serialize.txt',
                    serialize($importCarrier->getCurrentImportPaths())
                );

                if ($pug->getOption('debug') && class_exists('Facade\\Ignition\\Exceptions\\ViewException')) {
                    $contents = "<?php try { ?>$contents<?php } ".
                        "catch (\Throwable \$exception) { throw new \Bkwld\LaravelPug\PugException(\$this, \$exception); }";
                }
            }

            $this->files->put($compiled, $contents);
        }
    }
}
