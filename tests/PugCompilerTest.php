<?php

namespace Phug\Test;

use Bkwld\LaravelPug\PugCompiler;
use Illuminate\Filesystem\Filesystem;
use Pug\Pug;

class PugCompilerGetAndSetPath extends PugCompiler
{
    protected $overriddenPath;

    public function getPath()
    {
        return $this->overriddenPath;
    }

    public function setPath($path)
    {
        $this->overriddenPath = $path;
    }
}

/**
 * @coversDefaultClass \Bkwld\LaravelPug\PugCompiler
 */
class PugCompilerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::isExpired
     * @covers ::__construct
     */
    public function testIsExpired()
    {
        $pug = new Pug([
            'cache'        => true,
            'defaultCache' => sys_get_temp_dir(),
        ]);
        $compiler = new PugCompiler($pug, new Filesystem());
        $path = realpath(__DIR__ . '/example.pug');
        $compiledPath = $compiler->getCompiledPath($path);

        self::assertSame(sys_get_temp_dir(), dirname($compiledPath));

        // Cleanup
        if (file_exists($compiledPath)) {
            unlink($compiledPath);
            clearstatcache();
        }

        self::assertTrue($compiler->isExpired($path));

        $compiler->compile($path);
        clearstatcache();

        self::assertFalse($compiler->isExpired($path));

        $pug->setOption('cache', false);

        self::assertTrue($compiler->isExpired($path));

        // Cleanup
        if (file_exists($compiledPath)) {
            unlink($compiledPath);
            clearstatcache();
        }

        $cache = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'foo';
        $pug = new Pug([
            'cache'        => $cache,
            'defaultCache' => sys_get_temp_dir(),
        ]);
        $compiler = new PugCompiler($pug, new Filesystem());
        $compiledPath = $compiler->getCompiledPath($path);

        self::assertSame($cache, dirname($compiledPath));
    }

    /**
     * @covers ::compile
     */
    public function testCompile()
    {
        $pug = new Pug([
            'defaultCache' => sys_get_temp_dir(),
        ]);
        $compiler = new PugCompiler($pug, new Filesystem());
        $path = realpath(__DIR__ . '/example.pug');
        $compiledPath = $compiler->getCompiledPath($path);
        $compiler->compile($path);
        ob_start();
        include $compiledPath;
        $contents = ob_get_contents();
        ob_end_clean();

        self::assertSame('<h1>Pug is there</h1><p>{{ $sentence }}</p>', $contents);

        // Cleanup
        if (file_exists($compiledPath)) {
            unlink($compiledPath);
            clearstatcache();
        }
    }

    /**
     * @covers ::compile
     */
    public function testGetAndSetPath()
    {
        $pug = new Pug([
            'defaultCache' => sys_get_temp_dir(),
        ]);
        $compiler = new PugCompilerGetAndSetPath($pug, new Filesystem());
        $compiledPath = $compiler->getCompiledPath('foo');

        try {
            $compiler->compile('foo');
        } catch (\Exception $exception) {
            //
        }

        // Cleanup
        if (file_exists($compiledPath)) {
            unlink($compiledPath);
            clearstatcache();
        }

        self::assertSame('foo', $compiler->getPath());

        $path = realpath(__DIR__ . '/example.pug');
        $compiledPath = $compiler->getCompiledPath($path);
        $compiler->setPath($path);
        $compiler->compile(null);

        $sentence = 'By HTML syntax!';
        ob_start();
        include $compiledPath;
        $contents = ob_get_contents();
        ob_end_clean();

        self::assertSame('<h1>Pug is there</h1><p>{{ $sentence }}</p>', $contents);

        // Cleanup
        if (file_exists($compiledPath)) {
            unlink($compiledPath);
            clearstatcache();
        }
    }

    /**
     * @covers                   ::compile
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Missing path argument.
     */
    public function testCompilePathException()
    {
        $pug = new Pug([
            'defaultCache' => sys_get_temp_dir(),
        ]);
        $compiler = new PugCompiler($pug, new Filesystem());
        $compiler->compile(null);
    }
}
