<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Normalize SCRIPT_NAME when served from a subfolder (e.g. /crmdemo) via root .htaccess
if (isset($_SERVER['SCRIPT_NAME']) && str_ends_with($_SERVER['SCRIPT_NAME'], '/public/index.php')) {
    $publicDir = substr($_SERVER['SCRIPT_NAME'], 0, -strlen('/index.php'));
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (!str_starts_with($requestPath, $publicDir . '/') && $requestPath !== $publicDir) {
        $basePath = substr($publicDir, 0, -strlen('/public'));
        $_SERVER['SCRIPT_NAME'] = $basePath . '/index.php';
        if (isset($_SERVER['PHP_SELF']) && str_ends_with($_SERVER['PHP_SELF'], '/public/index.php')) {
            $_SERVER['PHP_SELF'] = $basePath . '/index.php';
        }
    }
}

$app->handleRequest(Request::capture());
