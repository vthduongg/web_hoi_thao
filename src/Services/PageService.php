<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Clock;
use App\Core\Database;
use App\Core\Lang;
use App\Core\Validator;
use App\Repositories\PageRepository;

/**
 * Nghiệp vụ trang nội dung (FR-023 – FR-027): lưu kèm lịch sử phiên bản, thẻ, chuyển hướng slug cũ,
 * lọc HTML, khoá lạc quan khi hai người cùng sửa, ghi nhật ký.
 *
 * Dữ liệu trang ($data) gồm: title, slug, body_html, module, seo_title, seo_description,
 * featured_media_id, tags (list), status, published_at (giờ Việt Nam dạng 2026-03-10T08:30, hoặc null).
 */
final class PageService
{
    public const MODULES = ['none', 'important_dates', 'committees', 'keynote_speakers', 'program', 'sponsors', 'contacts'];
    public const STATUSES = ['draft', 'published', 'hidden'];

    private readonly PageRepository $pages;

    public function __construct(
        private readonly Database $db,
        private readonly HtmlSanitizer $sanitizer,
        private readonly ActivityLogger $logger,
    ) {
        $this->pages = new PageRepository($db);
    }

    /** @return array<string, string> lỗi theo tên trường */
    public function validate(int $conferenceId, array $data, ?int $pageId): array
    {
        $errors = (new Validator(Lang::current()))->validate($data, [
            'title' => 'required|max:255',
            'slug' => 'required',
            'module' => 'required|in:' . implode(',', self::MODULES),
            'status' => 'required|in:' . implode(',', self::STATUSES),
            'published_at' => 'datetime',
            'seo_title' => 'max:255',
            'seo_description' => 'max:500',
        ]);

        if (!isset($errors['slug'])) {
            $problem = SlugValidator::problem($data['slug']);
            if ($problem !== null) {
                $errors['slug'] = t('pages.slug_' . $problem);
            } elseif ($this->pages->slugTaken($conferenceId, $data['slug'], $pageId)) {
                $errors['slug'] = t('pages.slug_taken');
            }
        }
        foreach ($data['tags'] ?? [] as $tag) {
            if (mb_strlen($tag) > 50) {
                $errors['tags'] = t('pages.tag_too_long');
            }
        }

        return $errors;
    }

    public function create(int $conferenceId, array $data, ?int $userId): int
    {
        return $this->db->transaction(function () use ($conferenceId, $data, $userId): int {
            $id = $this->pages->insert(['conference_id' => $conferenceId, 'created_by' => $userId] + $this->columns($data, $userId));
            $this->pages->saveTags($id, $data['tags'] ?? []);
            $this->pages->addRevision($id, $userId);
            $this->logger->log($userId, 'create', 'pages', $id, $data['title'], $conferenceId);
            if ($data['status'] === 'published') {
                $this->logger->log($userId, 'publish', 'pages', $id, $data['title'], $conferenceId);
            }

            return $id;
        });
    }

    /** @throws EditConflict khi trang đã bị người khác sửa (phiên bản không khớp) */
    public function update(int $pageId, array $data, int $expectedVersion, ?int $userId): void
    {
        $this->db->transaction(function () use ($pageId, $data, $expectedVersion, $userId): void {
            $before = $this->db->first('SELECT conference_id, slug, status FROM pages WHERE id = ?', [$pageId]);
            if (!$this->pages->updateIfVersion($pageId, $this->columns($data, $userId), $expectedVersion)) {
                throw new EditConflict();
            }
            if ($before['status'] === 'published' && $before['slug'] !== $data['slug']) {
                $this->pages->addRedirect((int) $before['conference_id'], $before['slug'], $pageId);
            }
            $this->pages->saveTags($pageId, $data['tags'] ?? []);
            $this->pages->addRevision($pageId, $userId);
            $this->logger->log($userId, 'update', 'pages', $pageId, $data['title'], (int) $before['conference_id']);
        });
    }

    /** Xuất bản / ẩn / về bản nháp. $publishedAtLocal rỗng khi xuất bản = xuất bản ngay. */
    public function changeStatus(int $pageId, string $status, ?string $publishedAtLocal, ?int $userId): void
    {
        $page = $this->db->first('SELECT conference_id, title, published_at FROM pages WHERE id = ?', [$pageId]);
        $changes = ['status' => $status, 'updated_by' => $userId];
        if ($status === 'published') {
            $changes['published_at'] = $this->publishTime($publishedAtLocal);
        }
        $this->db->execute('UPDATE pages SET version = version + 1 WHERE id = ?', [$pageId]);
        $this->pages->update($pageId, $changes);
        $this->logger->log($userId, $status === 'published' ? 'publish' : 'unpublish', 'pages', $pageId, $page['title'], (int) $page['conference_id']);
    }

    /** Khôi phục nội dung của một phiên bản cũ; kết quả được lưu thành phiên bản mới. */
    public function restoreRevision(int $pageId, int $revisionNo, ?int $userId): void
    {
        $revision = $this->pages->findRevision($pageId, $revisionNo) ?? throw new \InvalidArgumentException('Không có phiên bản này.');

        $this->db->transaction(function () use ($pageId, $revision, $userId): void {
            $this->db->execute('UPDATE pages SET version = version + 1 WHERE id = ?', [$pageId]);
            $this->pages->update($pageId, [
                'title' => $revision['title'],
                'body_html' => $revision['body_html'],
                'seo_title' => $revision['seo_title'],
                'seo_description' => $revision['seo_description'],
                'featured_media_id' => $revision['featured_media_id'],
                'module' => $revision['module'],
                'updated_by' => $userId,
            ]);
            $this->pages->addRevision($pageId, $userId);
            $conferenceId = (int) $this->db->value('SELECT conference_id FROM pages WHERE id = ?', [$pageId]);
            $this->logger->log($userId, 'update', 'pages', $pageId, $revision['title'], $conferenceId);
        });
    }

    /** Chuyển trang vào thùng rác. */
    public function softDelete(int $pageId, ?int $userId): void
    {
        $page = $this->db->first('SELECT conference_id, title FROM pages WHERE id = ?', [$pageId]);
        $this->pages->update($pageId, ['deleted_at' => Clock::nowForDatabase(), 'deleted_by' => $userId]);
        $this->logger->log($userId, 'delete', 'pages', $pageId, $page['title'], (int) $page['conference_id']);
    }

    /** @return array<string, mixed> cột của bảng pages tính từ dữ liệu biểu mẫu */
    private function columns(array $data, ?int $userId): array
    {
        return [
            'title' => trim($data['title']),
            'slug' => $data['slug'],
            // Đổi link video/bản đồ thành khung nhúng trước, rồi mới lọc HTML
            'body_html' => $this->sanitizer->clean(EmbedConverter::convert($data['body_html'] ?? '')),
            'module' => $data['module'],
            'seo_title' => ($data['seo_title'] ?? '') !== '' ? $data['seo_title'] : null,
            'seo_description' => ($data['seo_description'] ?? '') !== '' ? $data['seo_description'] : null,
            'featured_media_id' => $data['featured_media_id'] ?? null,
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? $this->publishTime($data['published_at'] ?? null) : $this->optionalTime($data['published_at'] ?? null),
            'updated_by' => $userId,
        ];
    }

    private function publishTime(?string $localDateTime): string
    {
        return ($localDateTime ?? '') === '' ? Clock::nowForDatabase() : Clock::vietnamLocalToUtc($localDateTime);
    }

    private function optionalTime(?string $localDateTime): ?string
    {
        return ($localDateTime ?? '') === '' ? null : Clock::vietnamLocalToUtc($localDateTime);
    }
}
