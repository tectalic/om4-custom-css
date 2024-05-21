<?php

namespace OM4\Vendor;

if (\version_compare(\PHP_VERSION, '5.6') < 0) {
    throw new \Exception('scssphp requires PHP 5.6 or above');
}
if (!\class_exists('OM4\\Vendor\\ScssPhp\\ScssPhp\\Version')) {
    \spl_autoload_register(function ($class) {
        if (0 !== \strpos($class, 'OM4\\Vendor\\ScssPhp\\ScssPhp\\')) {
            // Not a ScssPhp class
            return;
        }
        $subClass = \substr($class, \strlen('OM4\\Vendor\\ScssPhp\\ScssPhp\\'));
        $path = __DIR__ . '/src/' . \str_replace('\\', '/', $subClass) . '.php';
        if (\file_exists($path)) {
            require $path;
        }
    });
}
