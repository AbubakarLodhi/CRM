<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "<h3>Environment Variables:</h3>";
echo "<pre>";
foreach ($_ENV as $key => $value) {
    // Hide sensitive values
    if (str_contains(strtolower($key), 'password') || str_contains(strtolower($key), 'secret') || str_contains(strtolower($key), 'key')) {
        echo $key . " = [HIDDEN]\n";
    } else {
        echo $key . " = " . $value . "\n";
    }
}
echo "</pre>";

echo "<h3>APP_KEY set: " . (getenv('APP_KEY') ? 'YES' : 'NO') . "</h3>";
echo "<h3>DB_HOST set: " . (getenv('DB_HOST') ? 'YES' : 'NO') . "</h3>";
echo "<h3>/tmp writable: " . (is_writable('/tmp') ? 'YES' : 'NO') . "</h3>";