# Laravel Pug

[![Packagist](https://img.shields.io/packagist/v/bkwld/laravel-pug.svg)](https://packagist.org/packages/bkwld/laravel-pug)
[![GitHub Actions](https://img.shields.io/endpoint.svg?url=https%3A%2F%2Factions-badge.atrox.dev%2FBKWLD%2Flaravel-pug%2Fbadge&&label=Build&logo=none)](https://github.com/BKWLD/laravel-pug/actions)
[![StyleCI](https://styleci.io/repos/63732751/shield?style=flat)](https://styleci.io/repos/63732751)
[![codecov](https://codecov.io/gh/BKWLD/laravel-pug/branch/master/graph/badge.svg)](https://codecov.io/gh/BKWLD/laravel-pug)
[![Code Climate](https://codeclimate.com/github/BKWLD/laravel-pug/badges/gpa.svg)](https://codeclimate.com/github/BKWLD/laravel-pug)
[![License](https://poser.pugx.org/bkwld/laravel-pug/license)](https://packagist.org/packages/bkwld/laravel-pug)

A small package that adds support for compiling Pug (Jade) templates
to Laravel via [Pug.php](https://github.com/pug-php/pug) (see [complete documentation](https://www.phug-lang.com/)).
Both vanilla php and [Blade syntax](https://laravel.com/docs/5.5/blade)
is supported within the view.

This is the documentation for the ongoing version 2.0. [Click here to load the documentation for 1.11](https://github.com/BKWLD/laravel-pug/tree/1.11.0#laravel-pug)

## Installation

First you need composer if you haven't yet: https://getcomposer.org/download/

Now open a terminal at the root of your Laravel project. If it's a new project,
create it with: `composer create-project --prefer-dist laravel/laravel my-new-project`
(replace *my-new-project* with your own project name,
[see the documentation for further information](https://laravel.com/docs/5.5#installing-laravel))

Then run:
```
composer require bkwld/laravel-pug
```

## Usage

```php
Route::get('/', function () {
    return view('my-page');
});
```

Will now try to load `views/my-page.pug` first, or `views/my-page.blade.pug`
or fallback to the default blade engine loading `views/my-page.blade.php`.

As with Blade, you can pass variables to your view:

```php
Route::get('/', function () {
    return view('my-page', [
        'user' => Auth::user(),
        'messages' => ['Hello', 'Bye'],
    ]);
});
```

Any file with the extension `.pug` will be compiled as a pug template.
Laravel Pug also registers the `.pug.blade` which also compile blade code
once Pug code has been compiled; but
we highly recommend you to use the clean and standard extension `.pug`
that will be recognized by most systems. It compiles your Pug templates
in the same way as Blade templates; the compiled template is put in your
storage directory. Thus, you don't suffer compile times on every page load.

In other words, just put your Pug files in the regular views directory
and name them like `whatever.pug`. You reference them in Laravel like normal
such as `view('home.whatever')` for `resources/views/home/whatever.pug`.

The Pug view files can work side-by-side with regular PHP views.

### Use Blade in Pug templates

This feature is designed for transition purpose, since every blade
features are available in pug, you would not need both.

To use Blade templating within your Pug, just name the files with
`.blade.pug` extensions.

<details>
 <summary>Read more</summary>

Be aware that this mode will first render your template with
pug, then give the output to render to blade, it means your template must
have a valid pug syntax and must render a valid blade template. This also
means blade directives are only available through pug text output, see the
example below:
```pug
| @if ($one === 1)
div $one = 1
| @endif
p {{ $two }}
```
If you render this with the following values: `['one' => 1, 'two' => 2]`, you
will get:
```html
<div>$one = 1</div>
<p>2</p>
```
PS: note that you would get the same output with the following pure pug code:
```pug
if one === 1
  div $one = 1
p=two
```
</details>

### Use in Lumen

Register the service in `bootstrap/app.php`
(**Register Service Providers** section is the dedicated place):

```php
$app->register(Bkwld\LaravelPug\ServiceProvider::class);
```

Then you can use it with `view()`:
```php
$router->get('/', function () use ($router) {
    // will render resources/views/test.pug
    return view('test', [
        'name' => 'Bob',
    ]);
});
```

## Configuration

All [Pug.php](https://github.com/pug-php/pug) options are passed through via
a Laravel config array file you can edit **/config/laravel-pug.php**

If for any reason, the config file is missing, just run the following command:
`php artisan vendor:publish --provider="Bkwld\LaravelPug\ServiceProvider"`


## Extending Layouts / Include Sub-views

Default root directory for templates is `resources/views`, so from any
template any deep in the directory, you can use absolute paths to get
other pug files from the root: `extends /layouts/main` will extends the file `resources/views/layouts/main.pug`, `include /partial/foo/bar`, will include `resources/views/partial/foo/bar.pug`. You can use the `basedir` option to set the root to an other directory. Paths that does not start with a slash will be resolved relatively to the current template file.


## History

Read the Github [project releases](https://github.com/BKWLD/laravel-pug/releases)
for release notes.
