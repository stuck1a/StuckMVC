<?php
/*
 *  This file is part of StuckMVC <https://stuck1a.de/coding/stuckmvc>,
 *  Copyright (c) 2021-2026.
 *  The StuckMVC framework is free software: you can redistribute it and/or modify it
 *  under the terms of the GNU General Public License version 3 as published by
 *  the Free Software Foundation. See the GNU General Public License for more details.
 *
 *  FOR A SOMEWHAT FREER WORLD.
 */

declare(strict_types=1);

use Stuck1A\StuckMVC\Core\Http\Request;
use Stuck1A\StuckMVC\Core\Kernel;

// PHP's built-in web server (local development): deliver existing static files directly
if (PHP_SAPI === 'cli-server') {
    $file = realpath(__DIR__ . parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($file !== false && is_file($file) && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR . 'out' . DIRECTORY_SEPARATOR)) {
        return false;
    }
}

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
    // dependencies are not installed (run 'composer install') - show the maintenance page
    http_response_code(503);
    readfile(__DIR__ . '/offline.html');
    exit;
}

require __DIR__ . '/vendor/autoload.php';

$kernel = Kernel::boot(__DIR__);
$kernel->handle(Request::fromGlobals($kernel->basePath()))->send();
