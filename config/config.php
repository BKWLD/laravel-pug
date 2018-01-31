<?php return [

    /**
     * Pug options for Laravel >= 5.
     * Passthrough php-pug config options.
     *
     * @see https://www.phug-lang.com/#options
     */

    // Required for extending layouts
    'basedir' => resource_path('views'),
    'debug'   => env('APP_DEBUG', false),
];
