<?php

namespace Bkwld\LaravelPug;

use Composer\Script\Event;

class Install
{
    public static function publishVendor($event): void
    {
        $currentDirectory = getcwd();

        for ($i = 0; $i < 8 && !file_exists('artisan'); $i++) {
            chdir('..');
        }

        if (file_exists('artisan')) {
            /** @var Event $event */
            $io = $event->getIO();

            $cmd = 'php artisan vendor:publish --provider="'.ServiceProvider::class.'"';

            $io->write("> $cmd\n".@shell_exec($cmd));
        }

        chdir($currentDirectory);
    }
}
