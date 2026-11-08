<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Response;
use App\Models\Conference;
use App\Models\Page;
use App\Repositories\ImportantDateRepository;
use App\Repositories\MenuRepository;
use App\Repositories\PageRepository;
use App\Services\PublicPageContext;
use App\Services\RelatedPagesFinder;
use App\Services\UpcomingDateFinder;

/** Trang nội dung công khai: /{slug}. */
final class PageController extends Controller
{
    public function show(string $slug): Response
    {
        $conference = $this->services->conferences->current() ?? $this->notFound();

        return $this->renderPage($conference, $slug, '/' . $slug);
    }

    /** Dùng chung cho hội thảo hiện hành và hội thảo lưu trữ (US8). */
    public function renderPage(Conference $conference, string $slug, string $currentPath): Response
    {
        $pages = new PageRepository($this->services->db);
        $page = $pages->findPublicBySlug($conference->id, $slug);

        if ($page === null) {
            $newSlug = $pages->findRedirect($conference->id, $slug);
            if ($newSlug !== null) {
                return Response::redirect(url('/' . $newSlug), 301);
            }
            $this->notFound();
        }

        return $this->renderPageModel($conference, $page, $currentPath);
    }

    /**
     * Render một trang (kể cả bản nháp — dùng cho Xem trước trong quản trị).
     *
     * @param string $bannerHtml dải thông báo phía trên trang (xem trước, lưu trữ); đã thoát HTML
     */
    public function renderPageModel(Conference $conference, Page $page, string $currentPath, string $bannerHtml = ''): Response
    {
        $pages = new PageRepository($this->services->db);
        $context = new PublicPageContext($this->services, $conference);
        $context->shareWithView($currentPath);
        $pageUrl = PublicPageContext::absoluteUrl(url('/' . $page->slug));

        $html = $this->services->view->page('public/layout', 'public/page', [
            'page' => $page,
            'pageTitle' => ($page->seoTitle ?? '') !== '' ? $page->seoTitle : $page->title,
            'metaDescription' => $page->metaDescription(),
            'canonicalUrl' => $pageUrl,
            'ogImage' => $context->shareImageUrl($page->featuredMediaId),
            'shareUrl' => $pageUrl,
            'relatedPages' => $this->relatedPages($page, $context, $pages),
            'moduleTemplate' => $this->moduleTemplate($page),
            'moduleData' => $this->moduleData($page, $conference),
            'bannerHtml' => $bannerHtml,
        ]);

        return Response::html($html);
    }

    /** @return list<Page> */
    private function relatedPages(Page $page, PublicPageContext $context, PageRepository $pages): array
    {
        $publicPages = $context->publicPages();
        $tags = $pages->tagsFor(array_map(static fn (Page $p): int => $p->id, $publicPages));
        $menu = (new MenuRepository($this->services->db))->allForConference($page->conferenceId);

        return RelatedPagesFinder::find($page, $publicPages, $tags, $menu);
    }

    /** Template của khối dữ liệu hiển thị dưới nội dung trang (null = không có). */
    private function moduleTemplate(Page $page): ?string
    {
        $supported = ['important_dates'];

        return in_array($page->module, $supported, true) ? 'public/modules/' . $page->module : null;
    }

    /** @return array<string, mixed> */
    private function moduleData(Page $page, Conference $conference): array
    {
        return match ($page->module) {
            'important_dates' => $this->importantDatesData($conference),
            default => [],
        };
    }

    /** @return array<string, mixed> */
    private function importantDatesData(Conference $conference): array
    {
        $dates = (new ImportantDateRepository($this->services->db))->visibleForConference($conference->id);

        return ['dates' => $dates, 'upcoming' => UpcomingDateFinder::find($dates)];
    }
}
