<?php

namespace Phug\Test;

use Bkwld\LaravelPug\PugBladeCompiler;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pug\Pug;

class PugBladeCompilerGetAndSetPath extends PugBladeCompiler
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
 * @coversDefaultClass \Bkwld\LaravelPug\PugBladeCompiler
 */
class PugBladeCompilerTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::enableBladeDirectives
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::construct
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getCachePath
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::hasExpiredImport
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::isExpired
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getOption
     */
    public function testIsExpired()
    {
        $pug = new Pug([
            'cache'        => true,
            'defaultCache' => sys_get_temp_dir(),
        ]);
        $compiler = new PugBladeCompiler([[$pug], 0], new Filesystem(), [], sys_get_temp_dir());
        $path = realpath(__DIR__.'/example.pug');
        $compiledPath = $compiler->getCompiledPath($path);

        // Cleanup
        if (file_exists($compiledPath)) {
            unlink($compiledPath);
            clearstatcache();
        }

        self::assertTrue($compiler->isExpired($path));

        $compiler->compile($path);
        touch(__DIR__.'/example.pug', time() - 3600);
        clearstatcache();

        self::assertFalse($compiler->isExpired($path));

        $compiler->setCachePath(null);

        self::assertTrue($compiler->isExpired($path));

        // Cleanup
        if (file_exists($compiledPath)) {
            unlink($compiledPath);
            clearstatcache();
        }
    }

    /**
     * @covers ::__construct
     * @covers ::enableBladeDirectives
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::construct
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getCachePath
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::hasExpiredImport
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::isExpired
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getOption
     */
    public function testIncludeIsExpired()
    {
        $cache = sys_get_temp_dir().DIRECTORY_SEPARATOR.'foo';
        $pug = new Pug([
            'cache'        => $cache,
            'defaultCache' => sys_get_temp_dir(),
        ]);

        if (!($pug instanceof \Phug\Renderer)) {
            self::markTestSkipped('Include cache expiration only available since pug-php 3.');
        }

        $files = new Filesystem();
        if (!$files->exists($cache)) {
            $files->makeDirectory($cache);
        }
        $path = realpath(__DIR__.'/example.pug');
        $compiler = new PugBladeCompiler([[$pug], 0], $files, [], $cache);
        $compiledPath = $compiler->getCompiledPath($path);

        self::assertSame($cache, dirname($compiledPath));

        $path = realpath(__DIR__.'/include.pug');
        $compiledPath = $compiler->getCompiledPath($path);

        touch(__DIR__.'/include.pug', time() - 3600);
        touch(__DIR__.'/example.pug', time() - 3600);
        $compiler->compile($path);
        clearstatcache();

        self::assertFileExists($compiledPath);

        self::assertFalse($compiler->isExpired($path));

        touch(__DIR__.'/example.pug', time() + 3600);
        clearstatcache();

        self::assertTrue($compiler->isExpired($path));

        touch(__DIR__.'/example.pug', time() - 3600);
        unlink($compiledPath.'.imports.serialize.txt');
        clearstatcache();

        self::assertTrue($compiler->isExpired($path));

        // Cleanup
        if (file_exists($compiledPath)) {
            unlink($compiledPath);
            clearstatcache();
        }
    }

    /**
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getOption
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::extractPath
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getCompiler
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::compileWith
     * @covers ::compile
     * @covers ::enableBladeDirectives
     */
    public function testCompile()
    {
        $pug = new Pug([
            'defaultCache' => sys_get_temp_dir(),
        ]);
        $compiler = new PugBladeCompiler([[$pug], 0], new Filesystem(), [], sys_get_temp_dir());
        $path = realpath(__DIR__.'/example.pug');
        $compiledPath = $compiler->getCompiledPath($path);
        $compiler->compile($path);
        $sentence = 'By HTML syntax!';
        ob_start();
        include $compiledPath;
        $contents = ob_get_contents();
        ob_end_clean();

        self::assertSame('<h1>Pug is there</h1><p>By HTML syntax!</p><div>Go</div>', $contents);

        // Cleanup
        if (file_exists($compiledPath)) {
            unlink($compiledPath);
            clearstatcache();
        }
    }

    /**
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getOption
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::extractPath
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getCompiler
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::compileWith
     * @covers ::compile
     */
    public function testGetAndSetPath()
    {
        $pug = new Pug([
            'defaultCache' => sys_get_temp_dir(),
        ]);
        $compiler = new PugBladeCompilerGetAndSetPath([[$pug], 0], new Filesystem(), [], sys_get_temp_dir());
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

        $path = realpath(__DIR__.'/example.pug');
        $compiledPath = $compiler->getCompiledPath($path);
        $compiler->setPath($path);
        $compiler->compile(null);

        $sentence = 'By HTML syntax!';
        ob_start();
        include $compiledPath;
        $contents = ob_get_contents();
        ob_end_clean();

        self::assertSame('<h1>Pug is there</h1><p>By HTML syntax!</p><div>Go</div>', $contents);

        // Cleanup
        if (file_exists($compiledPath)) {
            unlink($compiledPath);
            clearstatcache();
        }
    }

    /**
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::extractPath
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getCompiler
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::compileWith
     * @covers ::compile
     * @covers ::enableBladeDirectives
     */
    public function testPhpDirective()
    {
        $pug = new Pug([
            'defaultCache' => sys_get_temp_dir(),
        ]);

        if (!($pug instanceof \Phug\Renderer)) {
            self::markTestSkipped('@php directive test need pug-php 3+.');

            return;
        }

        $compiler = new PugBladeCompiler([[$pug], 0], new Filesystem(), [], sys_get_temp_dir());
        $path = realpath(__DIR__.'/php-directive.pug');
        $compiledPath = $compiler->getCompiledPath($path);
        $compiler->compile($path);

        ob_start();
        include $compiledPath;
        $contents = ob_get_contents();
        ob_end_clean();

        self::assertSame('<div><p>12</p><p>24</p></div>', $contents);

        // Cleanup
        if (file_exists($compiledPath)) {
            unlink($compiledPath);
            clearstatcache();
        }
    }

    /**
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getOption
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::setCachePath
     */
    public function testSetCachePath()
    {
        $pug = new Pug([
            'defaultCache' => sys_get_temp_dir(),
        ]);
        $compiler = new PugBladeCompiler([[$pug], 0], new Filesystem(), [], sys_get_temp_dir());
        $compiler->setCachePath('foo');

        self::assertStringStartsWith('foo/', $compiler->getCompiledPath('bar.pug'));
        self::assertSame('foo', $pug->getOption('cache'));
    }

    /**
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::extractPath
     */
    public function testCompilePathException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing path argument.');

        $pug = new Pug([
            'defaultCache' => sys_get_temp_dir(),
        ]);
        $compiler = new PugBladeCompiler([[$pug], 0], new Filesystem(), [], sys_get_temp_dir());
        $compiler->compile(null);
    }
}
