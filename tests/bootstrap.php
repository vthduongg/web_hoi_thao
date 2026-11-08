<?php

declare(strict_types=1);

// Khởi động cho PHPUnit: nạp autoload, đọc .env, ép môi trường testing.

require __DIR__ . '/../vendor/autoload.php';

putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';

App\Core\Env::loadFile(__DIR__ . '/../.env');

// Lỗi do test cố tình gây ra (trang 500) ghi vào file, không làm rối màn hình kết quả test.
ini_set('error_log', sys_get_temp_dir() . '/conference-test-errors.log');
