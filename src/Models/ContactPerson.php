<?php

declare(strict_types=1);

namespace App\Models;

/** Một người liên hệ (bảng contact_persons). */
final class ContactPerson
{
    public function __construct(
        public readonly int $id,
        public readonly string $fullName,
        public readonly ?string $affiliation,
        public readonly ?string $email,
        public readonly ?string $phone,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self((int) $row['id'], $row['full_name'], $row['affiliation'], $row['email'], $row['phone']);
    }

    /** Số điện thoại dùng cho liên kết tel: (chỉ giữ chữ số và dấu +). */
    public function phoneHref(): string
    {
        return preg_replace('/[^0-9+]/', '', (string) $this->phone);
    }
}
