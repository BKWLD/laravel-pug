<?php

namespace Bkwld\LaravelPug;

class Install
{
    protected static function getVersion()
    {
        $version = @shell_exec('php artisan --version');
        $version = preg_replace('/^([a-z]+\s+)+/i', '', trim($version));

        return empty($version) ? null : $version;
    }

    public static function publishVendor($event)
    {
        $currentDirectory = getcwd();

        for ($i = 0; $i < 8 && !file_exists('artisan'); $i++) {
            chdir('..');
        }

        if (file_exists('artisan') && ($version = static::getVersion())) {
            /** @var \Composer\Script\Event $event */
            $io = $event->getIO();

            $cmd = 'php artisan vendor:publish --provider="'.ServiceProvider::class.'"';

            $io->write("> $cmd\n".@shell_exec($cmd));
        }

        chdir($currentDirectory);
    }
}
