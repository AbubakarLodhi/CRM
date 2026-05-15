<?php

// Set the working directory to the project root
chdir(__DIR__ . '/..');

// Define the public path
$_SERVER['DOCUMENT_ROOT'] = __DIR__ . '/../public';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

// Load the Laravel application
require __DIR__ . '/../public/index.php';