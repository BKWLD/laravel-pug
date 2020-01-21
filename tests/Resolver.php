<?php

namespace Phug\Test;

use Illuminate\View\Engines\EngineResolver;

class Resolver extends EngineResolver
{
    protected $data = [];

    public function register($name, $callback)
    {
        $this->data[$name] = $callback;
    }

    public function get($name)
    {
        return call_user_func($this->data[$name]);
    }
}
