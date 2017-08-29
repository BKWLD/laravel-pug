<?php

if (!interface_exists('Illuminate\Contracts\Foundation\Application')) {
    include_once __DIR__ . '/LaravelLegacyApplicationInterface.php';
    include_once __DIR__ . '/LaravelApplicationInterface.php';
}

if (!function_exists('config_path')) {
    function config_path($input) {
        return $input;
    }
}
if (!function_exists('storage_path')) {
    function storage_path($input) {
        return $input;
    }
}
