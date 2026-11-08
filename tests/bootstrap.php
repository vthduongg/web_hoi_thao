<?php

declare(strict_types=1);

// Khởi động cho PHPUnit: nạp autoload, đọc .env, ép môi trường testing.

require __DIR__ . '/../vendor/autoload.php';

putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';

App\Core\Env::loadFile(__DIR__ . '/../.env');
