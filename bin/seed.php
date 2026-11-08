<?php

declare(strict_types=1);

// Nạp dữ liệu mẫu (hội thảo mẫu, các trang mặc định...). Chạy lại nhiều lần không tạo trùng.
//
// Cách dùng:
//   php bin/seed.php                 CSDL chạy thật
//   php bin/seed.php --test          CSDL test, kèm tài khoản admin@test.local / editor@test.local
//   php bin/seed.php --test --reset  xoá sạch dữ liệu CSDL test trước khi nạp

require __DIR__ . '/../vendor/autoload.php';

use App\Core\AppFactory;
use App\Core\Env;
use App\Services\Seeder;

Env::loadFile(__DIR__ . '/../.env');

$options = getopt('', ['test', 'reset']);
$isTest = isset($options['test']);

if (isset($options['reset']) && !$isTest) {
    fwrite(STDERR, 'LỖI: --reset chỉ dùng được cùng --test (để không xoá nhầm dữ liệu thật).' . PHP_EOL);
    exit(1);
}

try {
    $db = AppFactory::connectDatabase($isTest);
    (new Seeder($db, dirname(__DIR__)))->run(withTestAccounts: $isTest, reset: isset($options['reset']));
} catch (Throwable $e) {
    fwrite(STDERR, 'LỖI: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo 'Đã nạp dữ liệu mẫu.' . PHP_EOL;
exit(0);
