<?php

declare(strict_types=1);

// Tạo tài khoản quản trị viên đầu tiên và in ra mật khẩu tạm (người dùng nên đổi ngay sau khi đăng nhập).
//
// Cách dùng:
//   php bin/create-admin.php [--test] <email> "<họ tên>"

require __DIR__ . '/../vendor/autoload.php';

use App\Core\AppFactory;
use App\Core\Env;
use App\Repositories\UserRepository;

Env::loadFile(__DIR__ . '/../.env');

$args = array_slice($argv, 1);
$isTest = in_array('--test', $args, true);
$args = array_values(array_filter($args, static fn (string $arg): bool => $arg !== '--test'));

if (count($args) !== 2 || filter_var($args[0], FILTER_VALIDATE_EMAIL) === false || trim($args[1]) === '') {
    fwrite(STDERR, 'Cách dùng: php bin/create-admin.php [--test] <email> "<họ tên>"' . PHP_EOL);
    exit(1);
}
[$email, $fullName] = $args;

try {
    $users = new UserRepository(AppFactory::connectDatabase($isTest));
    if ($users->emailTaken($email)) {
        fwrite(STDERR, 'LỖI: Email này đã được dùng.' . PHP_EOL);
        exit(1);
    }

    // 12 ký tự ngẫu nhiên, bỏ các ký tự dễ nhầm (0/O, 1/l/I)
    $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $password = '';
    for ($i = 0; $i < 12; $i++) {
        $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    $users->create($email, trim($fullName), $password, 'admin');
} catch (Throwable $e) {
    fwrite(STDERR, 'LỖI: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

echo 'Đã tạo quản trị viên ' . UserRepository::normalizeEmail($email) . PHP_EOL;
echo 'Mật khẩu tạm: ' . $password . PHP_EOL;
exit(0);
