<?php

namespace Bkwld\LaravelPug;

use Composer\Json\JsonFile;
use Composer\Script\Event;

class UpdateCheck
{
    private static function getDependencies(Event $event)
    {
        $composer = $event->getComposer();
        $directory = dirname(realpath($composer->getConfig()->get('vendor-dir')));
        $json = new JsonFile($directory . DIRECTORY_SEPARATOR . 'composer.json');

        try {
            $dependencyConfig = $json->read();
        } catch (\Exception $e) {
            $dependencyConfig = array();
        }

        return array_merge(
            isset($dependencyConfig['require-dev']) ? $dependencyConfig['require-dev'] : array(),
            isset($dependencyConfig['require']) ? $dependencyConfig['require'] : array()
        );
    }

    public static function getLaravelPugVersion(array $dependencies)
    {
        foreach ($dependencies as $key => $value) {
            if (preg_match('/\/laravel-pug$/i', $key)) {
                return $value;
            }
        }

        return '';
    }

    public static function checkForPugUpgrade(Event $event)
    {
        $version = static::getLaravelPugVersion(static::getDependencies($event));

        if (empty($version) ||
            preg_match('/(?<!\.|\d)[01]\.\*/', $version) ||
            preg_match('/~\s*1\.[0-4](?!\.|\d)/', $version) ||
            preg_match('/\^\s*1\.[0-4](?!\d)/', $version) ||
            preg_match('/>=?\s*(0\.|1\.[0-4](?!\d))/', $version) ||
            strpos($version, 'dev-') !== false
        ) {
            $event->getIO()->write('Pug-php have been installed/updated and have possibly upgrade from ' .
                'version 2 to 3. Please check this link to see the impacts of these change or see how to downgrade ' .
                'to keep using Pug-php 2: https://github.com/pug-php/pug/blob/master/MIGRATION_GUIDE.md'
            );
        }
    }
}
