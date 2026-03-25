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

// Vercel's filesystem is mostly read-only (e.g. /var/task). Laravel still expects
// `storage/` and `bootstrap/cache/` to be writable, so redirect them to /tmp.
if (isset($_SERVER['VERCEL']) || getenv('VERCEL') !== false) {
    $tmpRoot = '/tmp/laravel';
    $storagePath = $tmpRoot.'/storage';
    $bootstrapPath = $tmpRoot.'/bootstrap';

    // Ensure Laravel's common writable directories exist.
    @mkdir($storagePath.'/logs', 0777, true);
    @mkdir($storagePath.'/framework/cache', 0777, true);
    @mkdir($storagePath.'/framework/sessions', 0777, true);
    @mkdir($storagePath.'/framework/views', 0777, true);
    @mkdir($storagePath.'/app', 0777, true);
    @mkdir($bootstrapPath.'/cache', 0777, true);

    $app->useStoragePath($storagePath);
    $app->useBootstrapPath($bootstrapPath);

    // Ensure Laravel generates `https://...` URLs (Vercel runs behind HTTPS).
    // This prevents "Mixed Content" warnings when the environment accidentally
    // sets APP_URL to `http://...`.
    \Illuminate\Support\Facades\URL::forceScheme('https');

    $host = $_SERVER['HTTP_HOST'] ?? getenv('VERCEL_URL') ?: null;
    if (is_string($host) && $host !== '') {
        \Illuminate\Support\Facades\Config::set('app.url', 'https://'.$host);
    }
}

$app->handleRequest(Request::capture());
