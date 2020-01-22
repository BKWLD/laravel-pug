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
     */
    public function testPugException()
    {
        $cwd = getcwd();
        $template = __DIR__.'/lines.pug';
        $cacheDir = sys_get_temp_dir().'/pug'.mt_rand(0, 99999);
        $fs = new Filesystem();
        $fs->makeDirectory("$cacheDir/framework/views", 0777, true);
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

        /** @var PugException $exception */
        $exception = $closure->call($compilerEngine);

        $fs->deleteDirectory($cacheDir);
        chdir($cwd);

        self::assertInstanceOf(PugException::class, $exception);
        self::assertInstanceOf(ViewException::class, $exception);
        self::assertSame('Division by zero', $exception->getMessage());
        self::assertSame(6, $exception->getLine());
    }
}
