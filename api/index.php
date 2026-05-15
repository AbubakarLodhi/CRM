<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

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

// Write .env to /tmp
$envPath = $tmpBase . '/.env';
$envContent = '';
foreach ($_ENV as $key => $value) {
    if (is_string($value)) {
        $value = str_replace('"', '\\"', $value);
        $envContent .= $key . '="' . $value . '"' . PHP_EOL;
    }
}
file_put_contents($envPath, $envContent);

chdir(dirname(__DIR__));

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__) . '/public';
$_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__) . '/public/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require dirname(__DIR__) . '/public/index.php';