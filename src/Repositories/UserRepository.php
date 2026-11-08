<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;

/** Truy vấn bảng users. Email luôn lưu và tìm ở dạng chữ thường. */
final class UserRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function find(int $id): ?User
    {
        $row = $this->db->first('SELECT * FROM users WHERE id = ?', [$id]);

        return $row === null ? null : User::fromRow($row);
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->db->first('SELECT * FROM users WHERE email = ?', [self::normalizeEmail($email)]);

        return $row === null ? null : User::fromRow($row);
    }

    /** @return list<User> */
    public function all(): array
    {
        return array_map([User::class, 'fromRow'], $this->db->query('SELECT * FROM users ORDER BY full_name'));
    }

    public function emailTaken(string $email, ?int $exceptId = null): bool
    {
        return (int) $this->db->value(
            'SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?',
            [self::normalizeEmail($email), $exceptId ?? 0],
        ) > 0;
    }

    public function create(string $email, string $fullName, string $password, string $role): int
    {
        return $this->db->insert('users', [
            'email' => self::normalizeEmail($email),
            'full_name' => $fullName,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);
    }

    /** @param array<string, mixed> $data cột => giá trị */
    public function update(int $id, array $data): void
    {
        if (isset($data['email'])) {
            $data['email'] = self::normalizeEmail($data['email']);
        }
        $this->db->update('users', $data, 'id = ?', [$id]);
    }

    public function setPassword(int $id, string $password): void
    {
        $this->db->update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$id]);
    }

    /** Làm mọi phiên đăng nhập hiện có của người dùng hết hiệu lực. Trả về epoch mới. */
    public function incrementEpoch(int $id): int
    {
        $this->db->execute('UPDATE users SET session_epoch = session_epoch + 1, updated_at = SYSUTCDATETIME() WHERE id = ?', [$id]);

        return (int) $this->db->value('SELECT session_epoch FROM users WHERE id = ?', [$id]);
    }

    public function countActiveAdmins(): int
    {
        return (int) $this->db->value("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1");
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
