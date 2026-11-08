<?php

declare(strict_types=1);

namespace App\Models;

/** Tài khoản quản trị (bảng users). */
final class User
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_EDITOR = 'editor';

    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $fullName,
        public readonly string $passwordHash,
        public readonly string $role,
        public readonly bool $isActive,
        public readonly int $failedLoginCount,
        public readonly ?string $lockedUntil,
        public readonly int $sessionEpoch,
        public readonly ?string $lastLoginAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            email: $row['email'],
            fullName: $row['full_name'],
            passwordHash: $row['password_hash'],
            role: $row['role'],
            isActive: (bool) $row['is_active'],
            failedLoginCount: (int) $row['failed_login_count'],
            lockedUntil: $row['locked_until'] === null ? null : substr((string) $row['locked_until'], 0, 19),
            sessionEpoch: (int) $row['session_epoch'],
            lastLoginAt: $row['last_login_at'] === null ? null : substr((string) $row['last_login_at'], 0, 19),
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }
}
