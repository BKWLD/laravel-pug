<?php namespace Bkwld\LaravelPug;

// Dependencies
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\CompilerInterface;
use Illuminate\Filesystem\Filesystem;
use Pug\Pug;

class PugBladeCompiler extends BladeCompiler implements CompilerInterface {

	/**
	 * The MtHaml instance.
	 *
	 * @var Pug
	 */
	protected $pug;

	/**
	 * Create a new compiler instance.
	 *
	 * @param  Pug $pug
	 * @param  \Illuminate\Filesystem\Filesystem  $files
	 * @param  string  $cachePath
	 * @return void
	 */
	public function __construct(Pug $pug, Filesystem $files, $cachePath)
	{
		$this->pug = $pug;
		parent::__construct($files, $cachePath);
	}

	/**
	 * Compile the view at the given path.
	 *
	 * @param  string  $path
	 * @return void
	 */
	public function compile($path) {
		$this->footer = array();

		if (is_null($this->cachePath)) return;

		// First compile the Pug syntax
		$contents = $this->pug->compile($this->files->get($path), $path);

		// Then the Blade syntax
		$contents = $this->compileString($contents);

		// Save
		$this->files->put($this->getCompiledPath($path), $contents);
	}

}
