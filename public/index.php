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

    $host = $_SERVER['HTTP_HOST'] ?? getenv('VERCEL_URL') ?: null;
    if (is_string($host) && $host !== '') {
        // Ensure Laravel generates `https://...` URLs.
        //
        // Note: do NOT touch container bindings (like `url` or `config`) here;
        // on Vercel this file runs before some bindings are registered.
        // Updating env vars is safe and will be picked up when config loads.
        $httpsUrl = 'https://'.$host;
        $appUrl = (string) getenv('APP_URL');
        if ($appUrl === '' || str_starts_with($appUrl, 'http://')) {
            putenv('APP_URL='.$httpsUrl);
            $_ENV['APP_URL'] = $httpsUrl;
            $_SERVER['APP_URL'] = $httpsUrl;
        }
    }
}

$app->handleRequest(Request::capture());
