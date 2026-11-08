<?php

declare(strict_types=1);

// Việc hằng ngày (đặt lịch chạy lúc 02:00 — xem docs/van-hanh.md):
//   1) Xoá vĩnh viễn các mục đã nằm trong thùng rác quá 30 ngày.
//   2) Sao lưu CSDL (.bak) và thư mục tệp tải lên (.zip), giữ 30 bản mới nhất.
//
// Cách dùng:
//   php bin/daily.php                     chạy thật
//   php bin/daily.php --test              dùng CSDL test
//   php bin/daily.php --dry-run-backup    không chạy lệnh BACKUP DATABASE (chỉ để thử)

require __DIR__ . '/../vendor/autoload.php';

use App\Core\AppFactory;
use App\Core\Env;
use App\Core\Lang;
use App\Services\ActivityLogger;
use App\Services\BackupFailed;
use App\Services\BackupService;
use App\Services\TrashService;

Env::loadFile(__DIR__ . '/../.env');
Lang::setCurrent(Lang::load('vi', __DIR__ . '/../lang'));
$options = getopt('', ['test', 'dry-run-backup']);
$isTest = isset($options['test']);
$publicDir = dirname(__DIR__) . '/public';

try {
    $db = AppFactory::connectDatabase($isTest);
    $logger = new ActivityLogger($db);
    $purged = (new TrashService($db, $logger, $publicDir))->purgeExpired();
    echo "Đã xoá vĩnh viễn $purged mục quá 30 ngày trong thùng rác." . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'LỖI: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$databaseName = $isTest ? Env::get('DB_TEST_NAME', 'conference_test') : Env::get('DB_NAME', 'conference');
try {
    $files = (new BackupService($db, $databaseName, $publicDir, Env::get('BACKUP_DIR'), isset($options['dry-run-backup'])))->run();
    $logger->log(null, 'backup', null, null, basename($files['database']) . ', ' . basename($files['uploads']));
    echo 'Đã sao lưu: ' . $files['database'] . ' và ' . $files['uploads'] . PHP_EOL;
} catch (BackupFailed $e) {
    $logger->log(null, 'backup', null, null, 'LỖI: ' . $e->getMessage());
    fwrite(STDERR, 'LỖI SAO LƯU: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

exit(0);
