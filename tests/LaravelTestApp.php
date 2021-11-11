<?php

use Psr\Container\ContainerInterface;

$has = new ReflectionMethod(ContainerInterface::class, 'has');

require $has->getReturnType()
    ? __DIR__.'/LaravelTestAppStable.php'
    : __DIR__.'/LaravelTestAppLowest.php';
