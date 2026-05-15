<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

$tmpBase = '/tmp/laravel';

// Create writable directories
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

// Write .env to /tmp from environment variables
$envPath = $tmpBase . '/.env';
$envContent = '';
foreach ($_ENV as $key => $value) {
    if (is_string($value)) {
        $value = str_replace('"', '\\"', $value);
        $envContent .= $key . '="' . $value . '"' . PHP_EOL;
    }
}
file_put_contents($envPath, $envContent);

// Tell Laravel to use /tmp/.env
putenv('APP_BASE_PATH=' . dirname(__DIR__));

chdir(dirname(__DIR__));

$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../public';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

// Override the env file location
define('LARAVEL_ENV_FILE', $tmpBase . '/.env');

require __DIR__ . '/../public/index.php';