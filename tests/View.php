<?php

namespace Phug\Test;

class View
{
    protected $extensions = [];

    public function addExtension($extension, $engine)
    {
        $this->extensions[$extension] = $engine;
    }

    public function getExtensions()
    {
        return $this->extensions;
    }
}
