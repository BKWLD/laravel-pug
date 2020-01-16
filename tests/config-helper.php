<?php

namespace Phug\Test;

use ArrayAccess;

class Config implements ArrayAccess
{
    protected $useSysTempDir = false;

    protected $data = [];

    public function __construct($source = null)
    {
        $this->data['source'] = $source;
    }

    public function setUseSysTempDir($useSysTempDir)
    {
        $this->useSysTempDir = $useSysTempDir;
    }

    public function get($input)
    {
        if ($this->useSysTempDir && in_array($input, ['laravel-pug', 'laravel-pug::config'])) {
            return [
                'assetDirectory'  => __DIR__ . '/assets',
                'outputDirectory' => sys_get_temp_dir(),
                'defaultCache'    => sys_get_temp_dir(),
            ];
        }

        return isset($this->data[$input]) ? $this->data[$input] : [
            'input' => $input,
        ];
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function __toString()
    {
        return strval($this->data['source']);
    }
}
