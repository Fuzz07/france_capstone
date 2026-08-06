<?php

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Auto-Create Required Storage and Cache Directories
|--------------------------------------------------------------------------
|
| Laravel requires several directories under storage/ and bootstrap/cache/
| to exist and be writable. Git does not track empty directories, which
| can cause "Failed to open stream" errors in production. This block
| automatically creates them with correct permissions if they are missing.
|
*/
$required_dirs = [
    __DIR__.'/../storage',
    __DIR__.'/../storage/app',
    __DIR__.'/../storage/app/public',
    __DIR__.'/../storage/framework',
    __DIR__.'/../storage/framework/cache',
    __DIR__.'/../storage/framework/cache/data',
    __DIR__.'/../storage/framework/sessions',
    __DIR__.'/../storage/framework/views',
    __DIR__.'/../storage/logs',
    __DIR__.'/../bootstrap/cache',
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
        @chmod($dir, 0775);
    }
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
