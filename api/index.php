<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Create .env file from Vercel environment variables
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) {
    $envContent = '';
    foreach ($_ENV as $key => $value) {
        $envContent .= $key . '=' . $value . "\n";
    }
    file_put_contents($envPath, $envContent);
}

// Make storage and bootstrap/cache writable
$dirs = [
    __DIR__ . '/../storage',
    __DIR__ . '/../storage/app',
    __DIR__ . '/../storage/app/public',
    __DIR__ . '/../storage/framework',
    __DIR__ . '/../storage/framework/cache',
    __DIR__ . '/../storage/framework/sessions',
    __DIR__ . '/../storage/framework/views',
    __DIR__ . '/../storage/logs',
    __DIR__ . '/../bootstrap/cache',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

// Set working directory
chdir(__DIR__ . '/..');

$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../public';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require __DIR__ . '/../public/index.php';