<?php

namespace Bkwld\LaravelPug;

class Install
{
    protected static function getVersion()
    {
        $version = @shell_exec('php artisan --version');
        $version = preg_replace('/^([a-z]+\s+)+/i', '', $version);

        return empty($version) ? null : $version;
    }

    public static function publishVendor()
    {
        $currentDirectory = getcwd();
        for ($i = 0; $i < 8 && !file_exists('artisan'); $i++) {
            chdir('..');
        }
        if ($version = static::getVersion()) {
            $args = version_compare($version, '5.0-dev')
                ? 'vendor:publish --provider="Bkwld\LaravelPug\ServiceProvider"'
                : 'config:publish bkwld/laravel-pug';
            @shell_exec('php artisan ' . $args);
        }
        chdir($currentDirectory);
    }
}
