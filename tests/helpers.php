<?php

use Phug\Test\Config;

if (!interface_exists('Illuminate\Contracts\Foundation\Application')) {
    include_once __DIR__.'/LaravelLegacyApplicationInterface.php';
    include_once __DIR__.'/LaravelApplicationInterface.php';
}

if (!class_exists(Config::class)) {
    include_once __DIR__.'/config-helper.php';
}

if (!class_exists('Facade\Ignition\Exceptions\ViewException')) {
    include_once __DIR__.'/ViewException.php';
}

if (!function_exists('config_path')) {
    function config_path($input)
    {
        return $input;
    }
}

if (!function_exists('storage_path')) {
    function storage_path($input)
    {
        return $input;
    }
}

if (!function_exists('resource_path')) {
    function resource_path($input)
    {
        return "resource/$input";
    }
}
