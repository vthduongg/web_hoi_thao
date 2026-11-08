<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Lớp bọc PDO cho SQL Server. Mọi truy vấn dùng prepared statement với dấu "?".
 *
 * transaction() lồng nhau được: lớp ngoài dùng transaction thật, lớp trong dùng SAVE TRANSACTION.
 */
final class Database
{
    private int $transactionDepth = 0;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** Kết nối SQL Server. Để trống $user và $password để dùng đăng nhập Windows. */
    public static function connect(string $host, string $database, string $user = '', string $password = ''): self
    {
        $dsn = sprintf('sqlsrv:Server=%s;Database=%s;TrustServerCertificate=1', $host, $database);
        $pdo = new PDO($dsn, $user !== '' ? $user : null, $password !== '' ? $password : null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => true,
            PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
        ]);

        return new self($pdo);
    }

    /** Tạo cơ sở dữ liệu nếu chưa có (kết nối qua CSDL master). */
    public static function createIfMissing(string $host, string $database, string $user = '', string $password = ''): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            throw new \InvalidArgumentException("Tên CSDL không hợp lệ: $database");
        }
        $master = self::connect($host, 'master', $user, $password);
        if ($master->value('SELECT DB_ID(?)', [$database]) === null) {
            $master->exec("CREATE DATABASE [$database]");
        }
    }

    /** @return list<array<string, mixed>> */
    public function query(string $sql, array $params = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute(self::normalize($params));

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null dòng đầu tiên, hoặc null nếu không có */
    public function first(string $sql, array $params = []): ?array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute(self::normalize($params));
        $row = $statement->fetch();
        $statement->closeCursor();

        return $row === false ? null : $row;
    }

    /** Giá trị cột đầu tiên của dòng đầu tiên. */
    public function value(string $sql, array $params = []): mixed
    {
        $row = $this->first($sql, $params);

        return $row === null ? null : reset($row);
    }

    /** Chạy câu lệnh ghi, trả về số dòng bị ảnh hưởng. */
    public function execute(string $sql, array $params = []): int
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute(self::normalize($params));

        return $statement->rowCount();
    }

    /**
     * Thêm một dòng, trả về id mới.
     *
     * @param string $table tên bảng do code truyền vào (không bao giờ lấy từ người dùng)
     * @param array<string, mixed> $data
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        return (int) $this->value(
            "INSERT INTO $table ($columns) OUTPUT INSERTED.id VALUES ($placeholders)",
            array_values($data),
        );
    }

    /**
     * Thêm một dòng vào bảng không có cột id (ví dụ page_tags, page_committees).
     *
     * @param array<string, mixed> $data
     */
    public function insertRow(string $table, array $data): void
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $this->execute("INSERT INTO $table ($columns) VALUES ($placeholders)", array_values($data));
    }

    /**
     * Cập nhật các dòng thoả điều kiện, tự đặt updated_at. Trả về số dòng bị ảnh hưởng.
     *
     * @param array<string, mixed> $data
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $assignments = implode(', ', array_map(static fn (string $column): string => "$column = ?", array_keys($data)));

        return $this->execute(
            "UPDATE $table SET $assignments, updated_at = SYSUTCDATETIME() WHERE $where",
            [...array_values($data), ...$whereParams],
        );
    }

    /**
     * Chạy $work trong một transaction; lỗi thì rollback và ném lại lỗi.
     *
     * @template T
     * @param callable(): T $work
     * @return T
     */
    public function transaction(callable $work): mixed
    {
        $savepoint = 'sp' . ($this->transactionDepth + 1);
        $this->transactionDepth === 0 ? $this->pdo->beginTransaction() : $this->pdo->exec("SAVE TRANSACTION $savepoint");
        $this->transactionDepth++;

        try {
            $result = $work();
            $this->transactionDepth--;
            if ($this->transactionDepth === 0) {
                $this->pdo->commit();
            }

            return $result;
        } catch (\Throwable $e) {
            $this->transactionDepth--;
            if ($this->transactionDepth === 0) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            } else {
                $this->pdo->exec("ROLLBACK TRANSACTION $savepoint");
            }
            throw $e;
        }
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
        $this->transactionDepth++;
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
        $this->transactionDepth = 0;
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /** Chạy một đoạn SQL nhiều câu lệnh không có tham số (dùng cho migration). */
    public function exec(string $sql): void
    {
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage() . "\nSQL: " . mb_substr($sql, 0, 300), (int) $e->getCode(), $e);
        }
    }

    /**
     * Mẫu "chứa chuỗi" cho LIKE, coi % _ [ \ là ký tự thường.
     * Câu SQL phải dùng kèm ESCAPE '\', ví dụ: "name LIKE ? ESCAPE '\'".
     */
    public static function likeContains(string $text): string
    {
        return '%' . addcslashes($text, '\\%_[') . '%';
    }

    /** Đổi bool thành 0/1 để SQL Server nhận đúng kiểu BIT. */
    private static function normalize(array $params): array
    {
        return array_map(static fn (mixed $value): mixed => is_bool($value) ? (int) $value : $value, array_values($params));
    }
}
