<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ActivityLogger;
use Tests\Support\DatabaseTestCase;

final class ActivityLoggerTest extends DatabaseTestCase
{
    public function test_writes_one_row_with_all_fields(): void
    {
        $logger = new ActivityLogger($this->db);
        $logger->setIpAddress('10.0.0.5');
        $conferenceId = $this->createConference();

        $logger->log(null, 'update', 'pages', 12, 'Call for Papers', $conferenceId);

        $row = $this->db->first('SELECT TOP 1 * FROM activity_logs ORDER BY id DESC');
        self::assertSame('update', $row['action']);
        self::assertSame('pages', $row['entity_type']);
        self::assertSame(12, $row['entity_id']);
        self::assertSame('Call for Papers', $row['entity_label']);
        self::assertSame($conferenceId, $row['conference_id']);
        self::assertSame('10.0.0.5', $row['ip_address']);
    }

    public function test_long_labels_are_cut_to_255_characters(): void
    {
        (new ActivityLogger($this->db))->log(null, 'create', 'pages', 1, str_repeat('ư', 300));

        $label = $this->db->value('SELECT TOP 1 entity_label FROM activity_logs ORDER BY id DESC');
        self::assertSame(255, mb_strlen($label));
    }
}
