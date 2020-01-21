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
        $exception = null;
        $template = __DIR__.'/lines.pug';
        $cacheDir = sys_get_temp_dir().'/pug'.mt_rand(0, 99999);
        $fs = new Filesystem();
        $fs->makeDirectory($cacheDir, 0777, true);
        $app = new LaravelTestApp();
        $app['config'] = new Config();
        $resolver = new EngineResolver();
        $view = new View();
        $app['files'] = $fs;
        $app['view'] = $view;
        $app['view.engine.resolver'] = $resolver;
        $compiler = new PugCompiler(
            [$app, 'laravel-pug.pug'],
            $fs,
            [],
            $cacheDir
        );
        $service = new ServiceProvider($app);
        $service->register();
        $service->registerPugCompiler();
        $compiler->compile($template);
        $phpPath = $compiler->getCompiledPath($template);
        $php = strtr(file_get_contents($phpPath), [
            'Section content' => '<?php throw new \\Exception("Foo Bar"); ?>',
        ]);
        file_put_contents($phpPath, $php);

        /** @var CompilerEngine $compilerEngine */
        $compilerEngine = $resolver->resolve('pug');
        $closure = function () use ($phpPath) {
            include $phpPath;
        };

        try {
            $closure->call($compilerEngine);
        } catch (Throwable $e) {
            $exception = $e;
        }

        $fs->deleteDirectory($cacheDir);

        self::assertInstanceOf(PugException::class, $exception);
        self::assertInstanceOf(ViewException::class, $exception);
        self::assertSame('Foo Bar', $exception->getMessage());
        self::assertSame(6, $exception->getLine());
    }
}
