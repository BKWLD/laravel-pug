<?php

namespace Phug\Test;

use Bkwld\LaravelPug\UpdateCheck;
use Composer\Config as ComposerConfig;
use Composer\Composer;
use Composer\IO\NullIO;
use Composer\Script\Event as ComposerEvent;

class CaptureIO extends NullIO
{
    /** @var bool */
    private $touched = false;

    /**
     * @return bool
     */
    public function isTouched()
    {
        return $this->touched;
    }

    public function write($messages, $newline = true, $verbosity = self::NORMAL)
    {
        $this->touched = true;
    }
}


/**
* @coversDefaultClass \Bkwld\LaravelPug\UpdateCheck
*/
class UpdateCheckTest extends \PHPUnit_Framework_TestCase
{
    private static function testComposerSettings($directory, $touched)
    {
        $composer = new Composer();
        $config = new ComposerConfig();
        $config->merge(array(
            'config' => array(
                'vendor-dir' => __DIR__ . '/composer-samples/' . $directory . '/vendor',
            ),
        ));
        $composer->setConfig($config);
        $io = new CaptureIO();
        $event = new ComposerEvent('update', $composer, $io);
        UpdateCheck::checkForPugUpgrade($event);

        self::assertSame($touched, $io->isTouched());
    }

    /**
     * @covers ::getDependencies
     * @covers ::getLaravelPugVersion
     * @covers ::checkForPugUpgrade
     */
    public function testCheckForPugUpgrade()
    {
        self::testComposerSettings('broken', true);
        self::testComposerSettings('up-to-date', false);
        self::testComposerSettings('range', true);
        self::testComposerSettings('future', false);
        self::testComposerSettings('old', false);
        self::testComposerSettings('very-old', false);
    }
}
