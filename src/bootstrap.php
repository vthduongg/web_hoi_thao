<?php

declare(strict_types=1);

// Khởi động ứng dụng: nạp thư viện, đọc .env, đặt múi giờ UTC, tạo App.

require __DIR__ . '/../vendor/autoload.php';

App\Core\Env::loadFile(__DIR__ . '/../.env');
date_default_timezone_set('UTC');

return App\Core\AppFactory::create(dirname(__DIR__));
