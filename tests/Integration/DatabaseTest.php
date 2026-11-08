<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\DatabaseTestCase;

final class DatabaseTest extends DatabaseTestCase
{
    public function test_insert_returns_id_and_first_reads_row(): void
    {
        $id = $this->createConference(['name' => 'Hội thảo quốc tế về giảng dạy tiếng Anh']);

        $row = $this->db->first('SELECT id, name FROM conferences WHERE id = ?', [$id]);

        self::assertSame($id, $row['id']);
        self::assertSame('Hội thảo quốc tế về giảng dạy tiếng Anh', $row['name'], 'NVARCHAR phải giữ nguyên tiếng Việt có dấu');
    }

    /**
     * Lỗi đã gặp: PDO::exec() của pdo_sqlsrv huỷ batch còn thông báo chưa đọc (BACKUP DATABASE không chạy,
     * không báo lỗi). exec() phải chạy trọn batch.
     */
    public function test_exec_runs_whole_batch_even_after_many_info_messages(): void
    {
        $id = $this->createConference(['venue' => 'Trước']);

        $this->db->exec("DECLARE @i int = 0; WHILE @i < 50 BEGIN RAISERROR('progress', 0, 1) WITH NOWAIT; SET @i += 1; END;
            UPDATE conferences SET venue = N'Sau' WHERE id = $id;");

        self::assertSame('Sau', $this->db->value('SELECT venue FROM conferences WHERE id = ?', [$id]));
    }

    public function test_exec_reports_sql_errors(): void
    {
        $this->expectException(\PDOException::class);

        $this->db->exec('SELECT * FROM bang_khong_ton_tai');
    }

    public function test_query_value_and_execute(): void
    {
        $id = $this->createConference();

        $changed = $this->db->execute('UPDATE conferences SET venue = ? WHERE id = ?', ['Phúc Yên', $id]);

        self::assertSame(1, $changed);
        self::assertSame('Phúc Yên', $this->db->value('SELECT venue FROM conferences WHERE id = ?', [$id]));
        self::assertCount(1, $this->db->query('SELECT id FROM conferences WHERE id = ?', [$id]));
    }

    public function test_first_returns_null_when_nothing_found(): void
    {
        self::assertNull($this->db->first('SELECT id FROM conferences WHERE id = ?', [-1]));
    }

    public function test_nested_transaction_rolls_back_only_inner_work_on_error(): void
    {
        $outerId = $this->createConference();

        try {
            $this->db->transaction(function (): void {
                $this->createConference(['name' => 'Sẽ bị huỷ']);
                throw new \RuntimeException('lỗi giữa chừng');
            });
        } catch (\RuntimeException) {
        }

        self::assertNotNull($this->db->first('SELECT id FROM conferences WHERE id = ?', [$outerId]));
        self::assertNull($this->db->first("SELECT id FROM conferences WHERE name = N'Sẽ bị huỷ'"));
    }

    public function test_update_sets_updated_at(): void
    {
        $id = $this->createConference();
        $this->db->execute("UPDATE conferences SET updated_at = '2000-01-01' WHERE id = ?", [$id]);

        $this->db->update('conferences', ['venue' => 'Hà Nội'], 'id = ?', [$id]);

        self::assertGreaterThan('2020', (string) $this->db->value('SELECT updated_at FROM conferences WHERE id = ?', [$id]));
    }

    public function test_bool_parameters_are_stored_as_bit(): void
    {
        $id = $this->createConference();
        $this->db->update('conferences', ['has_been_current' => true], 'id = ?', [$id]);

        self::assertSame(1, (int) $this->db->value('SELECT has_been_current FROM conferences WHERE id = ?', [$id]));
    }
}
