<?php

namespace Phug\Test;

use Bkwld\LaravelPug\PugCompiler;
use Bkwld\LaravelPug\PugException;
use Bkwld\LaravelPug\ServiceProvider;
use Exception;
use Facade\Ignition\Exceptions\ViewException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use PHPUnit\Framework\TestCase;
use Phug\Util\SourceLocation;
use Throwable;

include_once __DIR__.'/helpers.php';
include_once __DIR__.'/LaravelTestApp.php';
include_once __DIR__.'/Laravel5ServiceProvider.php';
include_once __DIR__.'/View.php';
include_once __DIR__.'/config-helper.php';

/**
 * @coversDefaultClass \Bkwld\LaravelPug\PugException
 */
class PugExceptionTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::getLocation
     */
    public function testPugException()
    {
        $cwd = getcwd();
        $template = __DIR__.'/lines.pug';
        $cacheDir = sys_get_temp_dir().'/pug'.mt_rand(0, 99999);
        $fs = new Filesystem();
        $viewCache = "$cacheDir/framework/views";

        if (!$fs->isDirectory($viewCache)) {
            $fs->makeDirectory($viewCache, 0777, true);
        }

        chdir($cacheDir);
        $app = new LaravelTestApp();
        $app['config'] = new Config();
        $resolver = new EngineResolver();
        $view = new View();
        $app['files'] = $fs;
        $app['view'] = $view;
        $app['view.engine.resolver'] = $resolver;
        $service = new ServiceProvider($app);
        $service->register();
        $service->registerPugCompiler();
        /** @var CompilerEngine $compilerEngine */
        $compilerEngine = $resolver->resolve('pug');
        /** @var PugCompiler $compiler */
        $compiler = $compilerEngine->getCompiler();
        $compiler->setCachePath($cacheDir);
        $compiler->compile($template);
        $phpPath = $compiler->getCompiledPath($template);
        $originalContent = file_get_contents($phpPath);

        $closure = function () use ($phpPath) {
            $exception = null;
            ob_start();

            try {
                include $phpPath;
            } catch (Throwable $e) {
                $exception = $e;
            }

            ob_end_clean();

            return $exception;
        };

        /** @var PugException $exception1 */
        $exception1 = $closure->call($compilerEngine);

        file_put_contents($phpPath, strtr(file_get_contents($phpPath), [
            '12 / 0' => 'call_user_func(function () {throw new \\Exception("Other error");})',
        ]));

        /** @var PugException $exception2 */
        $exception2 = $closure->call($compilerEngine);

        file_put_contents($phpPath, $originalContent);

        /** @var PugException $exception3 */
        $exception3 = null;

        /** @var PugException $exception4 */
        $exception4 = null;

        for ($i = 0; $i < 2; $i++) {
            if ($i === 1) {
                $print = 'error-'.md5_file($exception3->getPrevious()->getFile()).'-'.
                    $exception3->getPrevious()->getLine().'-'.
                    md5($exception3->getPrevious()->getTraceAsString());
                $cachePath = storage_path('framework/views/'.$print.'.txt');
                /** @var SourceLocation $location */
                $location = unserialize(file_get_contents($cachePath));
                file_put_contents($cachePath, serialize(new SourceLocation(
                    $location->getPath(),
                    72,
                    $location->getOffset(),
                    $location->getOffsetLength()
                )));
            }

            ${'exception'.(3 + $i)} = $closure->call($compilerEngine);
        }

        $fs->deleteDirectory($cacheDir);
        chdir($cwd);

        self::assertInstanceOf(PugException::class, $exception1);
        self::assertInstanceOf(ViewException::class, $exception1);
        self::assertSame('Division by zero', $exception1->getMessage());
        self::assertSame(6, $exception1->getLine());

        self::assertInstanceOf(PugException::class, $exception2);
        self::assertInstanceOf(ViewException::class, $exception2);
        self::assertSame('Other error', $exception2->getMessage());
        self::assertSame(6, $exception2->getLine());

        self::assertInstanceOf(PugException::class, $exception3);
        self::assertInstanceOf(ViewException::class, $exception3);
        self::assertSame('Division by zero', $exception3->getMessage());
        self::assertSame(6, $exception3->getLine());

        self::assertInstanceOf(PugException::class, $exception4);
        self::assertInstanceOf(ViewException::class, $exception4);
        self::assertSame('Division by zero', $exception4->getMessage());
        self::assertSame(72, $exception4->getLine());
    }
}
