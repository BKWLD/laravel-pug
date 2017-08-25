# Laravel Pug

[![Packagist](https://img.shields.io/packagist/v/bkwld/laravel-pug.svg)](https://packagist.org/packages/bkwld/laravel-pug)
[![Build Status](https://travis-ci.org/bkwld/laravel-pug.svg?branch=master)](https://travis-ci.org/bkwld/laravel-pug)
[![StyleCI](https://styleci.io/repos/84180086/shield?style=flat)](https://styleci.io/repos/84180086)
[![Test Coverage](https://codeclimate.com/github/bkwld/laravel-pug/badges/coverage.svg)](https://codecov.io/github/bkwld/laravel-pug?branch=master)
[![Code Climate](https://codeclimate.com/github/bkwld/laravel-pug/badges/gpa.svg)](https://codeclimate.com/github/bkwld/laravel-pug)
[![License](https://poser.pugx.org/bkwld/laravel-pug/license)](https://packagist.org/packages/bkwld/laravel-pug)

A small package that adds support for compiling Pug (Jade) templates
to Laravel via [Pug.php](https://github.com/pug-php/pug).
Both vanilla php and [Blade syntax](http://laravel.com/docs/5.2/templates#blade-templating)
is supported within the view.


## Installation

Just run `composer require bkwld/laravel-pug` and all is ready to work.


## Usage

Any file with the extension `.pug` will be compiled as a pug template.
Laravel Pug also registers the `.pug.php`, `.pug.blade`, and `.pug.blade.php`
as well as the `.jade`, `.jade.php`, `.jade.blade`, and `.jade.blade.php`
extensions with Laravel and forwards compile requests on to Pug.php but
we highly recommand you to use the clean and standard extension `.pug`
that will be recognized by most systems. It compiles your Pug templates
in the same way as Blade templates; the compiled template is put in your
storage directory. Thus, you don't suffer compile times on every page load.

In other words, just put your Pug files in the regular views directory
and name them like `whatever.pug`. You reference them in Laravel like normal:

* **Laravel 4** : `View::make('home.whatever')` for `app/views/home/whatever.pug`
* **Laravel 5** : `view('home.whatever')` for `resources/views/home/whatever.pug`

The Pug view files can work side-by-side with regular PHP views. To use Blade
templating within your Pug, just name the files with `.pug.blade` or
`.pug.blade.php` extensions. This feature is designed for transition
purpose, since every blade features are available in pug, you would not
need both.


## Troubleshooting

If your `.pug` files are not rendered, you can check if the provider is
set. It's always the case with Laravel 5+. In older version our automated
script should add it automatically. If it fails for some reason, it should
display an error in your console when you execute a composer install, require
or update command. But you still can add it manually:

* **Laravel 4**: You must have a `'providers' => array()` entry in your
/app/config/app.php file (create it if not). And add
`'Bkwld\LaravelPug\ServiceProvider',` in this array.
* **Laravel 5**: You must have a `'providers' => []` entry in your
/config/app.php file (create it if not). And add
`Bkwld\LaravelPug\ServiceProvider::class,` in this array.


## Configuration

All [Pug.php](https://github.com/pug-php/pug) options are passed through via
a Laravel config array file you can edit according to your Laravel version: 

* **Laravel 4**: /app/config/packages/bkwld/laravel-pug/config.php
* **Laravel 5**: /config/laravel-pug.php

If for any reason, the config file is missing, just run the following command:

* **Laravel 4**: `php artisan config:publish bkwld/laravel-pug`
* **Laravel 5**: `php artisan vendor:publish --provider="Bkwld\LaravelPug\ServiceProvider"`


## Extending Layouts / Include Sub-views

Default root directory for templates is `resources/views`, so from any
template any deep in the directory, you can use absolute paths to get
other pug files from the root: `extends /layouts/main` will extends the file `resources/views/layouts/main.(pug|jade)`, `include /partial/foo/bar`, will include `resources/views/partial/foo/bar.(pug|jade)`. You can use the `basedir` option to set the root to an other directory. Paths that does not start with a slash will be resolved relatively to the current template file.


## Histoy

Read the Github [project releases](https://github.com/BKWLD/laravel-pug/releases)
for release notes.
