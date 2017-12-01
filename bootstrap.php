<?php

include __DIR__ . '/vendor/autoload.php';

$classLoader = new \Composer\Autoload\ClassLoader();
$classLoader->addPsr4("Assert\\", __DIR__ . '/phpunit/asserts/src/', true);
$classLoader->register();