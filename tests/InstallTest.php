<?php

namespace Phug\Test;

use Bkwld\LaravelPug\Install;
use Illuminate\Filesystem\Filesystem;
use Pug\Pug;

class Io
{
    /**
     * @var array
     */
    protected $messages = array();

    /**
     * @param string $message
     */
    public function write($message)
    {
        $this->messages[] = $message;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}

class Event
{
    /**
     * @var Io
     */
    protected $io;

    /**
     * @param Io $io
     */
    public function setIo(Io $io)
    {
        $this->io = $io;
    }

    /**
     * @return Io
     */
    public function getIo()
    {
        return $this->io;
    }
}

/**
 * @coversDefaultClass \Bkwld\LaravelPug\Install
 */
class InstallTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::getVersion
     * @covers ::addProvider
     * @covers ::publishVendor
     */
    public function testPublishVendorLaravel4()
    {
        $io = new Io();
        $event = new Event();
        $event->setIo($io);

        chdir(__DIR__ . '/app');
        if (file_exists('app/config/app.php')) {
            unlink('app/config/app.php');
        }
        file_put_contents('command', '4.2.0');
        Install::publishVendor($event);
        unlink('command');
        $argv = file_get_contents('argv');
        unlink('argv');

        self::assertSame('artisan config:publish bkwld/laravel-pug', $argv);

        self::assertEquals([
            'app/config/app.php not found, please add Bkwld\LaravelPug\ServiceProvider::class, in it in your providers.',
            '> php artisan config:publish bkwld/laravel-pug' . "\nOK",
        ], $io->getMessages());

        $io = new Io();
        $event = new Event();
        $event->setIo($io);

        file_put_contents('command', '4.2.0');
        copy('app/config/laravel-4-app-config.php', 'app/config/app.php');
        Install::publishVendor($event);
        unlink('command');
        unlink('argv');

        $diff = '';
        try {
            self::assertSame(
                file_get_contents('app/config/laravel-4-app-config.php'),
                file_get_contents('app/config/app.php')
            );
        } catch (\PHPUnit_Framework_ExpectationFailedException $exception) {
            $diff = $exception->getComparisonFailure()->getDiff();
        }

        unlink('app/config/app.php');

        self::assertEquals([
            'Pug service provided added to your app.',
            '> php artisan config:publish bkwld/laravel-pug' . "\nOK",
        ], $io->getMessages());

        self::assertStringStartsWith(implode("\n", [
            '--- Expected',
            '+++ Actual',
            '@@ @@',
            "         'Illuminate\\Workbench\\WorkbenchServiceProvider',",
            "+        'Bkwld\\LaravelPug\\ServiceProvider',",
            ' ',
            '     ),',
        ]), ltrim($diff));

        $io = new Io();
        $event = new Event();
        $event->setIo($io);

        file_put_contents('command', '4.2.0');
        copy('app/config/missing-comma-config.php', 'app/config/app.php');
        Install::publishVendor($event);
        unlink('command');
        unlink('argv');

        $diff = '';
        try {
            self::assertSame(
                file_get_contents('app/config/missing-comma-config.php'),
                file_get_contents('app/config/app.php')
            );
        } catch (\PHPUnit_Framework_ExpectationFailedException $exception) {
            $diff = $exception->getComparisonFailure()->getDiff();
        }

        unlink('app/config/app.php');

        self::assertEquals([
            'Pug service provided added to your app.',
            '> php artisan config:publish bkwld/laravel-pug' . "\nOK",
        ], $io->getMessages());

        self::assertStringStartsWith(implode("\n", [
            '--- Expected',
            '+++ Actual',
            '@@ @@',
            "         'Illuminate\View\ViewServiceProvider',",
            "-        'Illuminate\Workbench\WorkbenchServiceProvider'",
            "+        'Illuminate\Workbench\WorkbenchServiceProvider',",
            "+        'Bkwld\LaravelPug\ServiceProvider',",
            ' ',
            '     ),',
        ]), ltrim($diff));
    }

    /**
     * @covers ::getVersion
     * @covers ::addProvider
     * @covers ::publishVendor
     */
    public function testPublishVendorLaravel54()
    {
        $io = new Io();
        $event = new Event();
        $event->setIo($io);

        chdir(__DIR__ . '/app');
        if (file_exists('config/app.php')) {
            unlink('config/app.php');
        }
        file_put_contents('command', '5.4.0');
        Install::publishVendor($event);
        unlink('command');
        $argv = file_get_contents('argv');
        unlink('argv');

        self::assertSame(
            'artisan vendor:publish --provider=Bkwld\LaravelPug\ServiceProvider',
            str_replace('"', '', $argv)
        );

        self::assertEquals([
            'config/app.php not found, please add Bkwld\LaravelPug\ServiceProvider::class, in it in your providers.',
            '> php artisan vendor:publish --provider="Bkwld\LaravelPug\ServiceProvider"' . "\nOK",
        ], $io->getMessages());

        $io = new Io();
        $event = new Event();
        $event->setIo($io);

        file_put_contents('command', '5.4.0');
        copy('config/laravel-5-app-config.php', 'config/app.php');
        Install::publishVendor($event);
        unlink('command');
        unlink('argv');

        $diff = '';
        try {
            self::assertSame(
                file_get_contents('config/laravel-5-app-config.php'),
                file_get_contents('config/app.php')
            );
        } catch (\PHPUnit_Framework_ExpectationFailedException $exception) {
            $diff = $exception->getComparisonFailure()->getDiff();
        }

        unlink('config/app.php');

        self::assertEquals([
            'Pug service provided added to your app.',
            '> php artisan vendor:publish --provider="Bkwld\LaravelPug\ServiceProvider"' . "\nOK",
        ], $io->getMessages());

        self::assertStringStartsWith(implode("\n", [
            '--- Expected',
            '+++ Actual',
            '@@ @@',
            '         App\Providers\RouteServiceProvider::class,',
            '+        Bkwld\LaravelPug\ServiceProvider::class,',
            ' ',
            '     ],',
        ]), ltrim($diff));

        $io = new Io();
        $event = new Event();
        $event->setIo($io);

        chdir(__DIR__ . '/app');
        file_put_contents('config/app.php', '<?php return [];');
        file_put_contents('command', '5.4.0');
        Install::publishVendor($event);
        unlink('command');
        $argv = file_get_contents('argv');
        unlink('argv');

        self::assertSame(
            'artisan vendor:publish --provider=Bkwld\LaravelPug\ServiceProvider',
            str_replace('"', '', $argv)
        );

        self::assertEquals([
            "config/app.php does not contain 'providers' => [], " .
            'please add a providers list with Bkwld\LaravelPug\ServiceProvider::class in it.',

            '> php artisan vendor:publish --provider="Bkwld\LaravelPug\ServiceProvider"' .
            "\nOK",
        ], $io->getMessages());

        unlink('config/app.php');
    }

    /**
     * @covers ::getVersion
     * @covers ::addProvider
     * @covers ::publishVendor
     */
    public function testPublishVendorLaravel55()
    {
        $io = new Io();
        $event = new Event();
        $event->setIo($io);

        chdir(__DIR__ . '/app/config');
        file_put_contents('../command', '5.5.x-dev');
        Install::publishVendor($event);
        unlink('../command');
        $argv = file_get_contents('../argv');
        unlink('../argv');

        self::assertSame(
            'artisan vendor:publish --provider=Bkwld\LaravelPug\ServiceProvider',
            str_replace('"', '', $argv)
        );

        chdir(__DIR__ . '/app');
        file_put_contents('command', '5.5.x-dev');
        Install::publishVendor($event);
        unlink('command');
        $argv = file_get_contents('argv');
        unlink('argv');

        self::assertSame(
            'artisan vendor:publish --provider=Bkwld\LaravelPug\ServiceProvider',
            str_replace('"', '', $argv)
        );
    }
}
