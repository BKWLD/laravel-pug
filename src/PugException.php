<?php

namespace Bkwld\LaravelPug;

use Facade\Ignition\Exceptions\ViewException;
use Illuminate\View\Engines\CompilerEngine;
use Phug\Util\Exception\LocatedException;
use Phug\Util\SourceLocation;
use Throwable;

class PugException extends ViewException
{
    public function __construct(CompilerEngine $context, Throwable $previous)
    {
        $message = $previous->getMessage();
        $code = $previous->getCode();
        $compiler = $context->getCompiler();
        $file = $previous->getFile();
        $line = $previous->getLine();

        if ($compiler instanceof PugCompiler || $compiler instanceof PugBladeCompilerCompiler) {
            $print = 'error-' . md5_file($file) . '-' . $line . '-' . md5($previous->getTraceAsString());
            $cachePath = storage_path('framework/views/' . $print . '.txt');
            $location = null;

            if (file_exists($cachePath)) {
                list($path, $line, $offset, $offsetLength) = unserialize(file_get_contents($cachePath));
                $location = new SourceLocation($path, $line, $offset, $offsetLength);
            } else {
                $pug = $compiler->getPug();
                $error = $pug->getDebugFormatter()->getDebugError(
                    $previous,
                    file_get_contents($file),
                    $file
                );

                if ($error instanceof LocatedException && ($location = $error->getLocation())) {
                    file_put_contents($cachePath, serialize(array(
                        $location->getPath(),
                        $location->getLine(),
                        $location->getOffset(),
                        $location->getOffsetLength(),
                    )));
                }
            }

            if ($location) {
                $file = $location->getPath();
                $line = $location->getLine();
            }
        }

        parent::__construct($message, $code, 1, $file, $line, $previous);
    }
}
