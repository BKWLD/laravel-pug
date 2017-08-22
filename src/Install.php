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
        /** @var \Composer\Script\Event $event */
        $io = $event->getIO();
        $currentDirectory = getcwd();
        for ($i = 0; $i < 8 && !file_exists('artisan'); $i++) {
            chdir('..');
        }
        if ($version = static::getVersion()) {
            $appFile = 'config/app.php';
            if (version_compare($version, '5.5-dev') < 0) {
                if (file_exists($appFile)) {
                    $contents = file_get_contents($appFile);
                    if (mb_strpos($contents, 'Bkwld\LaravelPug\ServiceProvider::class') === false) {
                        $newContents = preg_replace_callback(
                            '/(["\']providers["\']\s*=>\s*\[)([\s\S]*?)\]/',
                            function ($match) {
                                $providers = rtrim($match[2]);
                                if (mb_substr($providers, -1) !== ',') {
                                    $providers .= ',';
                                }

                                return $match[1] .
                                    $providers .
                                    "\n        Bkwld\LaravelPug\ServiceProvider::class," .
                                    "\n\n    ]";
                            },
                            $contents
                        );
                        if ($newContents !== $contents) {
                            if (file_put_contents($appFile, $newContents)) {
                                $io->write('Pug service provided added to your app.');
                            } else {
                                $io->write("$appFile is not writable, please add Bkwld\LaravelPug\ServiceProvider::class, in it in your providers.");
                            }
                        } else {
                            $io->write("$appFile does not contain 'providers' => [], please add a providers list with Bkwld\LaravelPug\ServiceProvider::class in it.");
                        }
                    }
                } else {
                    $io->write('config/app.php not found, please add Bkwld\\LaravelPug\\ServiceProvider::class, in it in your providers.');
                }
            }

            $cmd = 'php artisan ' . (version_compare($version, '5.0-dev')
                    ? 'vendor:publish --provider="Bkwld\LaravelPug\ServiceProvider"'
                    : 'config:publish bkwld/laravel-pug'
                );
            $io->write("> $cmd\n" . @shell_exec($cmd));
        }
        chdir($currentDirectory);
    }
}
