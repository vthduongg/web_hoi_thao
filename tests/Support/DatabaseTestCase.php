<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Clock;
use App\Core\Database;
use App\Core\Migrator;
use PHPUnit\Framework\TestCase;

/**
 * Lớp cha cho test cần CSDL.
 *
 * - Kết nối CSDL test (DB_TEST_NAME), tự chạy migrations một lần cho mỗi lần chạy PHPUnit.
 * - Mỗi test chạy trong một transaction và bị rollback khi xong, nên các test không ảnh hưởng nhau.
 */
abstract class DatabaseTestCase extends TestCase
{
    private static ?Database $sharedDb = null;

    protected Database $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = self::database();
        $this->db->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        Clock::reset();
        parent::tearDown();
    }

    protected static function database(): Database
    {
        if (self::$sharedDb === null) {
            self::$sharedDb = \App\Core\AppFactory::connectDatabase(useTestDatabase: true, createIfMissing: true);
            (new Migrator(self::$sharedDb, dirname(__DIR__, 2) . '/database/migrations'))->run();
        }

        return self::$sharedDb;
    }

    /** Tạo nhanh một hội thảo và trả về id. */
    protected function createConference(array $overrides = []): int
    {
        $data = array_merge([
            'name' => 'Test Conference',
            'theme' => 'Practices, Opportunities and Challenges',
            'year' => 2090 + random_int(0, 9000),
            'venue' => 'Hanoi Pedagogical University 2',
            'conference_language' => 'English',
            'public_language' => 'en',
            'contact_email' => 'conference@example.com',
            'contact_phone' => '+84 24 1234 5678',
            'footer_text' => 'Footer',
            'copyright_text' => '© Test',
            'submission_url' => 'https://forms.example.com/submit',
            'home_intro_html' => '<p>Welcome</p>',
            'is_current' => 0,
            'has_been_current' => 0,
        ], $overrides);

        return $this->db->insert('conferences', $data);
    }
}
