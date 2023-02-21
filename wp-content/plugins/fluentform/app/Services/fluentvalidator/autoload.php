<?php

spl_autoload_register(function ($class) {
    $namespace = 'FluentValidator';

    if (substr($class, 0, strlen($namespace)) !== $namespace) {
        return;
    }

    $className = str_replace(
        ['\\', $namespace, strtolower($namespace)],
        ['/', 'src', ''],
        $class
    );

    $basePath = plugin_dir_path(__FILE__);

    $file = $basePath . trim($className, '/') . '.php';

    if (is_readable($file)) {
        include $file;
    }
});
