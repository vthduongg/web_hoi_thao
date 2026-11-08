<?php

declare(strict_types=1);

// Tạo/cập nhật bảng CSDL bằng các tệp database/migrations/*.sql chưa chạy.
//
// Cách dùng:
//   php bin/migrate.php              CSDL chạy thật (DB_NAME)
//   php bin/migrate.php --test       CSDL test (DB_TEST_NAME)
//   php bin/migrate.php --dir=PATH   dùng thư mục migration khác (dùng trong test)

require __DIR__ . '/../vendor/autoload.php';

use App\Core\AppFactory;
use App\Core\Env;
use App\Core\Migrator;

Env::loadFile(__DIR__ . '/../.env');

$options = getopt('', ['test', 'dir:']);
$directory = $options['dir'] ?? __DIR__ . '/../database/migrations';

try {
    $db = AppFactory::connectDatabase(isset($options['test']), createIfMissing: true);
    $applied = (new Migrator($db, $directory))->run();
} catch (Throwable $e) {
    fwrite(STDERR, 'LỖI: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo $applied === []
    ? 'CSDL đã ở phiên bản mới nhất.' . PHP_EOL
    : 'Đã áp dụng: ' . implode(', ', $applied) . PHP_EOL;
exit(0);
