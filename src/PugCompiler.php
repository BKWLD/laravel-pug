<?php namespace Bkwld\LaravelPug;

// Dependencies
use Illuminate\View\Compilers\Compiler;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\Filesystem\Filesystem;
use Pug\Pug;

class PugCompiler extends Compiler implements CompilerInterface
{
    /**
     * @var Pug
     */
    protected $pug;

    /**
     * Create a new compiler instance.
     *
     * @param  Pug $pug
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  string  $cachePath
     * @return void
     */
    public function __construct(Pug $pug, Filesystem $files)
    {
        $this->pug = $pug;
        $this->files = $files;
        $this->cachePath = $pug->getOption('cache');
        if (!is_string($this->cachePath)) {
            $this->cachePath = $pug->getOption('defaultCache');
        }
    }

    /**
     * Determine if the view at the given path is expired.
     *
     * @param  string  $path
     * @return bool
     */
    public function isExpired($path)
    {
        return !$this->pug->getOption('cache') || parent::isExpired($path);
    }

    /**
     * Compile the view at the given path.
     *
     * @param  string  $path
     * @return void
     */
    public function compile($path)
    {
        if ($this->cachePath) {
            $contents = $this->pug->compile($this->files->get($path), $path);
            $this->files->put($this->getCompiledPath($path), $contents);
        }
    }

}
