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

    protected static function addProvider($io, $laravel4)
    {
        $appFile = ($laravel4 ? 'app/' : '') . 'config/app.php';

        if (file_exists($appFile)) {
            $contents = file_get_contents($appFile);
            if (mb_strpos($contents, 'Bkwld\LaravelPug\ServiceProvider') === false) {
                $newContents = preg_replace_callback(
                    '/(["\']providers["\']\s*=>\s*(?:\[|array\s*\())([\s\S]*?)(\]|\))/',
                    function ($match) use ($laravel4) {
                        $providers = rtrim($match[2]);
                        if (mb_substr($providers, -1) !== ',') {
                            $providers .= ',';
                        }
                        $provider = $laravel4
                            ? "'Bkwld\\LaravelPug\\ServiceProvider'"
                            : 'Bkwld\\LaravelPug\\ServiceProvider::class';

                        return $match[1] .
                            $providers .
                            "\n        $provider," .
                            "\n\n    " .
                            $match[3];
                    },
                    $contents
                );
                if ($newContents !== $contents) {
                    if (file_put_contents($appFile, $newContents)) {
                        $io->write('Pug service provided added to your app.');

                        return;
                    }

                    // @codeCoverageIgnoreStart

                    $io->write("$appFile is not writable, please add Bkwld\LaravelPug\ServiceProvider::class, in it in your providers.");

                    return;

                    // @codeCoverageIgnoreEnd
                }

                $io->write("$appFile does not contain 'providers' => [], please add a providers list with Bkwld\LaravelPug\ServiceProvider::class in it.");
            }

            return;
        }

        $io->write("$appFile not found, please add Bkwld\\LaravelPug\\ServiceProvider::class, in it in your providers.");
    }

    public static function publishVendor($event)
    {
        $currentDirectory = getcwd();

        for ($i = 0; $i < 8 && !file_exists('artisan'); $i++) {
            chdir('..');
        }

        if ($version = static::getVersion()) {
            /** @var \Composer\Script\Event $event */
            $io = $event->getIO();

            $laravel4 = version_compare($version, '5.0-dev') < 0;

            if (version_compare($version, '5.5-dev') < 0) {
                static::addProvider($io, $laravel4);
            }

            $cmd = 'php artisan ' . ($laravel4
                    ? 'config:publish bkwld/laravel-pug'
                    : 'vendor:publish --provider="Bkwld\LaravelPug\ServiceProvider"'
                );

            $io->write("> $cmd\n" . @shell_exec($cmd));
        }

        chdir($currentDirectory);
    }
}
