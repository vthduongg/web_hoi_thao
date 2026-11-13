<?php

declare(strict_types=1);

// Nạp nội dung thật của hội thảo VietESL 2025 (https://vietesl2025.hpu2.edu.vn/) vào CSDL.
// Thường dùng với một CSDL riêng, ví dụ trong Git Bash:
//   DB_NAME=vietesl2025 php bin/migrate.php
//   DB_NAME=vietesl2025 php bin/seed-vietesl2025.php
//   DB_NAME=vietesl2025 php bin/create-admin.php admin@hpu2.edu.vn "Quản trị viên"
//   DB_NAME=vietesl2025 php -S localhost:8000 -t public public/index.php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\AppFactory;
use App\Core\Env;
use App\Services\VietEsl2025Seeder;

Env::loadFile(__DIR__ . '/../.env');

try {
    $db = AppFactory::connectDatabase(false);
    $conferenceId = (new VietEsl2025Seeder($db, dirname(__DIR__)))->run();
} catch (Throwable $e) {
    fwrite(STDERR, 'LỖI: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo 'Đã nạp VietESL 2025 vào CSDL ' . Env::get('DB_NAME') . " (hội thảo id $conferenceId)." . PHP_EOL;
exit(0);
