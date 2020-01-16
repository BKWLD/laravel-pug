<?php

namespace Phug\Test;

use Bkwld\LaravelPug\PugCompiler;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
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
class PugCompilerTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::construct
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getCachePath
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::hasExpiredImport
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::isExpired
     */
    public function testIsExpired()
    {
        $pug = new Pug([
            'cache'        => true,
            'defaultCache' => sys_get_temp_dir(),
        ]);
        $compiler = new PugCompiler([[$pug], 0], new Filesystem(), [], sys_get_temp_dir());
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
        touch(__DIR__ . '/example.pug', time() - 3600);
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
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::construct
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getCachePath
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::hasExpiredImport
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::isExpired
     */
    public function testIncludeIsExpired()
    {
        $cache = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'foo';
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
        $path = realpath(__DIR__ . '/example.pug');
        $compiler = new PugCompiler([[$pug], 0], $files, [], $cache);
        $compiledPath = $compiler->getCompiledPath($path);

        self::assertSame($cache, dirname($compiledPath));

        $pug->setOption('cache', true);
        $path = realpath(__DIR__ . '/include.pug');
        $compiledPath = $compiler->getCompiledPath($path);

        touch(__DIR__ . '/include.pug', time() - 3600);
        touch(__DIR__ . '/example.pug', time() - 3600);
        $compiler->compile($path);
        clearstatcache();

        self::assertFalse($compiler->isExpired($path));

        touch(__DIR__ . '/example.pug', time() + 3600);
        clearstatcache();

        self::assertTrue($compiler->isExpired($path));

        touch(__DIR__ . '/example.pug', time() - 3600);
        unlink($compiledPath . '.imports.serialize.txt');
        clearstatcache();

        self::assertTrue($compiler->isExpired($path));

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
     */
    public function testCompile()
    {
        $pug = new Pug([
            'defaultCache' => sys_get_temp_dir(),
        ]);
        $compiler = new PugCompiler([[$pug], 0], new Filesystem(), [], sys_get_temp_dir());
        $path = realpath(__DIR__ . '/example.pug');
        $compiledPath = $compiler->getCompiledPath($path);
        $compiler->compile($path);
        ob_start();
        include $compiledPath;
        $contents = ob_get_contents();
        ob_end_clean();

        self::assertSame('<h1>Pug is there</h1><p>{{ $sentence }}</p>@if (1 === 1)<div>Go</div>@endif', $contents);

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
     */
    public function testGetAndSetPath()
    {
        $pug = new Pug([
            'defaultCache' => sys_get_temp_dir(),
        ]);
        $compiler = new PugCompilerGetAndSetPath([[$pug], 0], new Filesystem(), [], sys_get_temp_dir());
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

        self::assertSame('<h1>Pug is there</h1><p>{{ $sentence }}</p>@if (1 === 1)<div>Go</div>@endif', $contents);

        // Cleanup
        if (file_exists($compiledPath)) {
            unlink($compiledPath);
            clearstatcache();
        }
    }

    /**
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getOption
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getCachePath
     */
    public function testGetCachePath()
    {
        $compiler = new PugCompiler([[new Pug()], 0], new Filesystem(), [], sys_get_temp_dir() . '/foo');

        self::assertSame(sys_get_temp_dir() . '/foo', $compiler->getCachePath());

        $compiler = new PugCompiler([[new Pug([
            'cache'        => sys_get_temp_dir() . '/foo',
            'defaultCache' => sys_get_temp_dir() . '/bar',
        ])], 0], new Filesystem(), []);

        self::assertSame(sys_get_temp_dir() . '/foo', $compiler->getCachePath());

        $compiler = new PugCompiler([[new Pug([
            'cache'        => sys_get_temp_dir() . '/foo',
            'defaultCache' => sys_get_temp_dir() . '/bar',
        ])], 0], new Filesystem(), [], sys_get_temp_dir() . '/biz');

        self::assertSame(sys_get_temp_dir() . '/biz', $compiler->getCachePath());

        $compiler = new PugCompiler([[new Pug([
            'defaultCache' => sys_get_temp_dir() . '/bar',
        ])], 0], new Filesystem(), []);

        self::assertSame(sys_get_temp_dir() . '/bar', $compiler->getCachePath());
    }

    /**
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getOption
     */
    public function testGetOption()
    {
        $compiler = new PugCompiler([[new Pug([
            'foo' => 'bar',
        ])], 0], new Filesystem(), [], 'i');

        self::assertSame('bar', $compiler->getOption('foo', 'bop'));
        self::assertSame('bidoup', $compiler->getOption('biz', 'bidoup'));
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
        $compiler = new PugCompiler([[$pug], 0], new Filesystem(), [], sys_get_temp_dir());
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
        $compiler = new PugCompiler([[$pug], 0], new Filesystem(), [], sys_get_temp_dir());
        $compiler->compile(null);
    }

    /**
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::extractPath
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::getCompiler
     * @covers \Bkwld\LaravelPug\PugHandlerTrait::compileWith
     * @covers ::compile
     */
    public function testRender()
    {
        $pug = new Pug([
            'defaultCache' => sys_get_temp_dir(),
        ]);
        $compiler = new PugCompiler([[$pug], 0], new Filesystem(), [], sys_get_temp_dir());
        $path = __DIR__ . '/js-expression.pug';
        $php = $compiler->compile($path);
        $items = ['a', 'b', 'c'];
        ob_start();
        include $compiler->getCompiledPath($path);
        $html = ob_get_contents();
        ob_end_clean();

        self::assertSame('<a href="?item=a">a</a><a href="?item=b">b</a><a href="?item=c">c</a>', $html);
    }
}
