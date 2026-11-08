<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\Services;
use App\Models\Conference;
use App\Models\Page;
use App\Repositories\MediaRepository;
use App\Repositories\MenuRepository;
use App\Repositories\PageRepository;

/**
 * Chuẩn bị dữ liệu chung cho mọi trang công khai (hội thảo, menu, logo, footer...)
 * để controller trang công khai ngắn gọn.
 */
final class PublicPageContext
{
    private readonly PageRepository $pages;
    private readonly MediaRepository $media;

    /** @var list<Page>|null trang công khai của hội thảo (nạp một lần mỗi request) */
    private ?array $publicPages = null;

    public function __construct(private readonly Services $services, public readonly Conference $conference)
    {
        $this->pages = new PageRepository($services->db);
        $this->media = new MediaRepository($services->db);
    }

    /** Chia sẻ dữ liệu chung cho layout và các partial. */
    public function shareWithView(string $currentPath): void
    {
        $view = $this->services->view;
        $view->share('conference', $this->conference);
        $view->share('currentPath', $currentPath);
        $view->share('logoUrl', upload_url($this->media->pathOf($this->conference->logoMediaId)));
        $view->share('faviconUrl', upload_url($this->media->pathOf($this->conference->faviconMediaId)));
        $view->share('menuTree', (new MenuRepository($this->services->db))->treeForPublic(
            $this->conference->id,
            $this->publicSlugById(),
            static fn (string $path): string => url($path),
        ));
    }

    /** @return list<Page> */
    public function publicPages(): array
    {
        return $this->publicPages ??= $this->pages->publicPagesForConference($this->conference->id);
    }

    /** Đường dẫn trong site tới trang có slug cho trước, chỉ khi trang đó công khai. */
    public function linkToPage(string $slug): ?string
    {
        return in_array($slug, $this->publicSlugById(), true) ? url('/' . $slug) : null;
    }

    /** Đường dẫn đầy đủ (có tên miền) — dùng cho thẻ og:url, og:image, nút chia sẻ. */
    public static function absoluteUrl(string $path): string
    {
        return rtrim(Env::get('APP_URL', 'http://localhost:8000'), '/') . '/' . ltrim($path, '/');
    }

    /** Ảnh chia sẻ: ảnh đại diện trang, nếu không có thì logo hội thảo. */
    public function shareImageUrl(?int $featuredMediaId): string
    {
        $path = $this->media->pathOf($featuredMediaId) ?? $this->media->pathOf($this->conference->logoMediaId);

        return $path === null ? '' : self::absoluteUrl(upload_url($path));
    }

    /** @return array<int, string> */
    private function publicSlugById(): array
    {
        $slugs = [];
        foreach ($this->publicPages() as $page) {
            $slugs[$page->id] = $page->slug;
        }

        return $slugs;
    }
}
