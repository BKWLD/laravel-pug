<?php

namespace Bkwld\LaravelPug;

// Dependencies
use Illuminate\Filesystem\Filesystem;
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
        $cachePath = $pug->getOption('cache');
        if (!is_string($cachePath)) {
            $cachePath = $pug->getOption('defaultCache');
        }
        parent::__construct($files, $cachePath);
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
        return !$this->pug->getOption('cache') || parent::isExpired($path);
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

            // Save
            $this->files->put($this->getCompiledPath($path), $contents);
        }
    }
}
