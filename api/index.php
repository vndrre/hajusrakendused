<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

$autoloadPath = __DIR__.'/../vendor/autoload.php';

if (! is_file($autoloadPath)) {
    http_response_code(500);
    header('content-type: text/plain; charset=utf-8');
    echo 'Missing vendor/autoload.php. Composer dependencies likely not installed in Vercel build.';
    exit;
}

require __DIR__.'/../public/index.php';
