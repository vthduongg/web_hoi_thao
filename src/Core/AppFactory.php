<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\ConferenceRepository;
use App\Services\ActivityLogger;
use App\Services\HtmlSanitizer;
use App\Services\Mailer;

/** Tạo App với đầy đủ dịch vụ. Dùng ở public/index.php và trong test. */
final class AppFactory
{
    public static function create(string $rootDir, ?Database $db = null, ?SessionStore $sessionStore = null): App
    {
        $environment = Env::get('APP_ENV', 'production');
        $db ??= self::connectDatabase();
        $sessionStore ??= new NativeSessionStore(str_starts_with(Env::get('APP_URL'), 'https://'));
        $session = new Session($sessionStore);

        $services = new Services(
            rootDir: $rootDir,
            db: $db,
            session: $session,
            csrf: new Csrf($session),
            flash: new Flash($session),
            view: new View($rootDir . '/templates'),
            sanitizer: new HtmlSanitizer(sys_get_temp_dir() . '/conference-htmlpurifier'),
            logger: new ActivityLogger($db),
            conferences: new ConferenceRepository($db),
            environment: $environment,
        );
        $services->auth = new Auth($db, $session, $services->logger);
        $services->mailer = Mailer::fromEnv($environment);
        $router = new Router();
        (require $rootDir . '/src/routes.php')($router);

        return new App($router, $services);
    }

    /** Kết nối CSDL chạy thật; khi APP_USE_TEST_DB=1 (test trình duyệt) dùng CSDL test. */
    public static function connectDatabase(bool $useTestDatabase = false, bool $createIfMissing = false): Database
    {
        $useTestDatabase = $useTestDatabase || Env::get('APP_USE_TEST_DB') === '1';
        $host = Env::get('DB_HOST', 'localhost\\SQLEXPRESS');
        $name = $useTestDatabase ? Env::get('DB_TEST_NAME', 'conference_test') : Env::get('DB_NAME', 'conference');

        if ($createIfMissing) {
            Database::createIfMissing($host, $name, Env::get('DB_USER'), Env::get('DB_PASS'));
        }

        return Database::connect($host, $name, Env::get('DB_USER'), Env::get('DB_PASS'));
    }
}
