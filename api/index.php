<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Create writable directories in /tmp
$tmpBase = '/tmp/laravel';
$dirs = [
    $tmpBase . '/storage/app/public',
    $tmpBase . '/storage/framework/cache/data',
    $tmpBase . '/storage/framework/sessions',
    $tmpBase . '/storage/framework/views',
    $tmpBase . '/storage/logs',
    $tmpBase . '/bootstrap/cache',
];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

// Set all environment variables from $_SERVER and $_ENV
$envVars = array_merge($_ENV, $_SERVER);
foreach ($envVars as $key => $value) {
    if (is_string($value)) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

chdir(__DIR__ . '/..');

$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../public';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require __DIR__ . '/../public/index.php';