<?php

namespace Bkwld\LaravelPug;

use Composer\Json\JsonFile;
use Composer\Script\Event;

class UpdateCheck
{
    public static function checkForPugUpgrade(Event $event)
    {
        $composer = $event->getComposer();
        $directory = dirname(realpath($composer->getConfig()->get('vendor-dir')));
        $json = new JsonFile($directory . DIRECTORY_SEPARATOR . 'composer.json');
        try {
            $dependencyConfig = $json->read();
        } catch (\RuntimeException $e) {
            $dependencyConfig = [];
        }
        $dependencies = array_merge(
            isset($dependencyConfig['require-dev']) ? $dependencyConfig['require-dev'] : [],
            isset($dependencyConfig['require']) ? $dependencyConfig['require'] : []
        );
        $version = isset($dependencies['laravel-pug']) ? $dependencies['laravel-pug'] : '';
        if (empty($version) ||
            preg_match('/(?<!\.|\d)[01]\.\*/', $version) ||
            preg_match('/~1\.[0-4](?!\.|\d)/', $version) ||
            preg_match('/^1\.[0-4](?!\d)/', $version) ||
            preg_match('/>=?(0\.|1\.[0-4](?!\d))/', $version) ||
            strpos($version, 'dev-') !== false
        ) {
            $event->getIO()->write('Pug-php have been installed/updated and have possibly upgrade from ' .
                'version 2 to 3. Please check this link to see the impacts of these change or see how to downgrade '.
                'to keep using Pug-php 2: https://github.com/pug-php/pug/blob/master/MIGRATION_GUIDE.md'
            );
        }
    }
}
