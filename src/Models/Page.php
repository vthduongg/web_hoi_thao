<?php

declare(strict_types=1);

namespace App\Models;

/** Một trang nội dung (bảng pages). */
final class Page
{
    public function __construct(
        public readonly int $id,
        public readonly int $conferenceId,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $bodyHtml,
        public readonly string $module,
        public readonly ?string $seoTitle,
        public readonly ?string $seoDescription,
        public readonly ?int $featuredMediaId,
        public readonly string $status,
        public readonly ?string $publishedAt,
        public readonly int $version,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) $row['id'],
            conferenceId: (int) $row['conference_id'],
            title: $row['title'],
            slug: $row['slug'],
            bodyHtml: (string) $row['body_html'],
            module: $row['module'],
            seoTitle: $row['seo_title'],
            seoDescription: $row['seo_description'],
            featuredMediaId: $row['featured_media_id'],
            status: $row['status'],
            publishedAt: $row['published_at'] === null ? null : (string) $row['published_at'],
            version: (int) $row['version'],
        );
    }

    /** Đã đặt xuất bản nhưng chưa tới giờ (hẹn giờ). */
    public function isScheduled(): bool
    {
        return $this->status === 'published' && $this->publishedAt !== null && $this->publishedAt > \App\Core\Clock::nowForDatabase();
    }

    /** Mô tả cho thẻ meta: mô tả SEO, hoặc 160 ký tự đầu của nội dung. */
    public function metaDescription(): string
    {
        if (($this->seoDescription ?? '') !== '') {
            return $this->seoDescription;
        }
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($this->bodyHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return mb_strlen($text) > 160 ? rtrim(mb_substr($text, 0, 157)) . '...' : $text;
    }
}
