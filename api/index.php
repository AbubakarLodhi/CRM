<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "PHP Version: " . phpversion() . "<br>";
echo "Current dir: " . __DIR__ . "<br>";
echo "Parent dir: " . dirname(__DIR__) . "<br>";

// Check if key Laravel files exist
echo "bootstrap/app.php exists: " . (file_exists(__DIR__ . '/../bootstrap/app.php') ? 'YES' : 'NO') . "<br>";
echo "vendor/autoload.php exists: " . (file_exists(__DIR__ . '/../vendor/autoload.php') ? 'YES' : 'NO') . "<br>";
echo "public/index.php exists: " . (file_exists(__DIR__ . '/../public/index.php') ? 'YES' : 'NO') . "<br>";
echo ".env exists: " . (file_exists(__DIR__ . '/../.env') ? 'YES' : 'NO') . "<br>";
echo "storage writable: " . (is_writable(__DIR__ . '/../storage') ? 'YES' : 'NO') . "<br>";
echo "bootstrap/cache writable: " . (is_writable(__DIR__ . '/../bootstrap/cache') ? 'YES' : 'NO') . "<br>";