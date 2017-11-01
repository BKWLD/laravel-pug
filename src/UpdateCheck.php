<?php

namespace Bkwld\LaravelPug;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;

class UpdateCheck implements PluginInterface, EventSubscriberInterface
{
    protected $io;
    protected $composer;

    // @codeCoverageIgnoreStart

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $this->composer = $composer;
    }

    public static function getSubscribedEvents()
    {
        return array(
            'post-autoload-dump' => array(
                array('onAutoloadDump', 0),
            ),
        );
    }

    public function onAutoloadDump(Event $event)
    {
        static::checkForPugUpgrade($event);
    }

    // @codeCoverageIgnoreEnd

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
