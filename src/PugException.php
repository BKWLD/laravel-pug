<?php

namespace Bkwld\LaravelPug;

use Facade\Ignition\Exceptions\ViewException;
use Illuminate\View\Engines\CompilerEngine;
use Phug\Util\Exception\LocatedException;
use Phug\Util\SourceLocationInterface;
use Throwable;

class PugException extends ViewException
{
    /**
     * Wrap a given raw error/exception in a relocated PugException.
     *
     * @param CompilerEngine $context  current $this context (the engine that compiled the template)
     * @param Throwable      $previous the raw error/exception
     *
     * @throws Throwable throw $previous if it cannot be located in the template.
     */
    public function __construct(CompilerEngine $context, Throwable $previous)
    {
        $message = $previous->getMessage();
        $code = $previous->getCode();
        $compiler = $context->getCompiler();
        $file = $previous->getFile();
        $line = $previous->getLine();

        if ($compiler instanceof PugHandlerInterface) {
            $print = 'error-'.md5_file($file).'-'.$line.'-'.md5($previous->getTraceAsString());
            $cachePath = storage_path('framework/views/'.$print.'.txt');
            $location = $this->getLocation($compiler, $cachePath, $file, $previous);

            if ($location) {
                $file = $location->getPath();
                $line = $location->getLine();
            }
        }

        parent::__construct($message, $code, 1, $file, $line, $previous);
    }

    protected function getLocation(PugHandlerInterface $compiler, string $cachePath, string $file, Throwable $previous): ?SourceLocationInterface
    {
        if (file_exists($cachePath)) {
            return unserialize(file_get_contents($cachePath));
        }

        $location = null;
        $pug = $compiler->getPug();
        $error = $pug->getDebugFormatter()->getDebugError(
            $previous,
            file_get_contents($file),
            $file
        );

        if ($error instanceof LocatedException && ($location = $error->getLocation())) {
            file_put_contents($cachePath, serialize($location));
        }

        return $location;
    }
}
