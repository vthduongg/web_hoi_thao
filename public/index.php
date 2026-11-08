<?php

declare(strict_types=1);

// Front controller: mọi request (trừ tệp tĩnh) đều đi qua đây.

// Server tích hợp của PHP (php -S): để nó tự phục vụ tệp tĩnh có thật.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
}

/** @var App\Core\App $app */
$app = require __DIR__ . '/../src/bootstrap.php';
$app->handle(App\Core\Request::fromGlobals())->send();
