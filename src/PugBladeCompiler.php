<?php

namespace Bkwld\LaravelPug;

// Dependencies
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\CompilerInterface;
use InvalidArgumentException;
use Pug\Pug;

class PugBladeCompiler extends BladeCompiler implements CompilerInterface
{
    /**
     * The MtHaml instance.
     *
     * @var Pug
     */
    protected $pug;

    /**
     * Create a new compiler instance.
     *
     * @param Pug        $pug
     * @param Filesystem $files
     * @param string     $cachePath
     */
    public function __construct(Pug $pug, Filesystem $files)
    {
        $this->pug = $pug;
        $cachePath = $this->getOption('cache');
        if (!is_string($cachePath)) {
            $cachePath = $this->getOption('defaultCache');
        }
        parent::__construct($files, $cachePath);
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
        if (method_exists($this->pug, 'hasOption') && !$this->pug->hasOption($name)) {
            return $default;
        }

        return $this->pug->getOption($name);
    }

    /**
     * @param string $cachePath
     */
    public function setCachePath($cachePath)
    {
        $this->cachePath = $cachePath;
        $this->pug->setOption('cache', $cachePath);
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

        if ($this->pug instanceof \Phug\Renderer) {
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
        }

        return false;
    }

    /**
     * Compile the view at the given path.
     *
     * @param string $path
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
        if ($path && method_exists($this, 'setPath')) {
            $this->setPath($path);
        }
        if (!$path && method_exists($this, 'getPath')) {
            $path = $this->getPath();
        }
        if (!$path) {
            throw new InvalidArgumentException('Missing path argument.');
        }

        $this->footer = array();

        if ($this->cachePath) {
            // First compile the Pug syntax
            $contents = $this->pug->compile($this->files->get($path), $path);

            // Then the Blade syntax
            $contents = $this->compileString($contents);
            $compiled = $this->getCompiledPath($path);
            $this->files->put(
                $compiled . '.imports.serialize.txt',
                serialize($this->pug->getCompiler()->getCurrentImportPaths())
            );

            // Save
            $this->files->put($compiled, $contents);
        }
    }
}
