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

echo ".env written: " . (file_exists($envPath) ? 'YES' : 'NO') . "<br>";
echo ".env size: " . filesize($envPath) . " bytes<br>";
echo "storage writable: " . (is_writable($tmpBase . '/storage') ? 'YES' : 'NO') . "<br>";
echo "bootstrap/cache writable: " . (is_writable($tmpBase . '/bootstrap/cache') ? 'YES' : 'NO') . "<br>";

// Try loading Laravel
try {
    chdir(dirname(__DIR__));
    require __DIR__ . '/../vendor/autoload.php';
    echo "Autoload: OK<br>";
    
    $app = require __DIR__ . '/../bootstrap/app.php';
    echo "App bootstrap: OK<br>";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}