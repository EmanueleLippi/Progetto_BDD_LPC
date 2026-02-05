<?php

// Basic Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Start Session
session_start();

// Simple Router
$request = $_SERVER['REQUEST_URI'];
$basePath = '/'; // Update if running in a subdirectory

// Remove query string
$request = strtok($request, '?');

// Route handling
switch ($request) {
    case '/':
    case '/index.php':
        require __DIR__ . '/../src/Views/home.php';
        break;


    case '/login':
    case '/login.php':
        $auth = new \App\Controllers\AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth->login();
        } else {
            $auth->showLogin();
        }
        break;

    default:
        http_response_code(404);
        echo "404 Not Found";
        break;
}
