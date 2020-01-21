<?php

namespace Phug\Test;

use Bkwld\LaravelPug\Install;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

class Io
{
    /**
     * @var array
     */
    protected $messages = [];

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
class InstallTest extends TestCase
{
    /**
     * @covers ::publishVendor
     */
    public function testPublishVendor()
    {
        $argvFile = __DIR__.'/app/argv';

        foreach ([__DIR__.'/app', __DIR__.'/app/sub-directory'] as $directory) {
            $io = new Io();
            $event = new Event();
            $event->setIo($io);

            chdir($directory);
            file_put_contents('command', '6.0.x-dev');
            Install::publishVendor($event);
            unlink('command');
            $argv = file_get_contents($argvFile);
            unlink($argvFile);

            self::assertSame(
                'artisan vendor:publish --provider=Bkwld\LaravelPug\ServiceProvider',
                str_replace('"', '', $argv)
            );
        }
    }
}
