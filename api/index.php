<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Use /tmp for writable directories
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

// Create .env in /tmp from environment variables
$envPath = $tmpBase . '/.env';
if (!file_exists($envPath)) {
    $envContent = '';
    foreach ($_ENV as $key => $value) {
        $envContent .= $key . '="' . addslashes($value) . '"' . "\n";
    }
    file_put_contents($envPath, $envContent);
}

// Override Laravel paths to use /tmp
$_ENV['APP_STORAGE_PATH'] = $tmpBase . '/storage';
$_ENV['APP_BOOTSTRAP_CACHE'] = $tmpBase . '/bootstrap/cache';

putenv('APP_STORAGE_PATH=' . $tmpBase . '/storage');
putenv('APP_BOOTSTRAP_CACHE=' . $tmpBase . '/bootstrap/cache');

chdir(__DIR__ . '/..');

$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../public';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

define('LARAVEL_STORAGE_PATH', $tmpBase . '/storage');
define('LARAVEL_BOOTSTRAP_CACHE', $tmpBase . '/bootstrap/cache');

require __DIR__ . '/../public/index.php';