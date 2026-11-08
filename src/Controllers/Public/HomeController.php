<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Response;
use App\Models\Conference;
use App\Repositories\HomeRepository;
use App\Repositories\ImportantDateRepository;
use App\Services\PublicPageContext;

/** Trang chủ công khai. */
final class HomeController extends Controller
{
    public function index(): Response
    {
        $conference = $this->services->conferences->current() ?? $this->notFound();

        return $this->renderHome($conference, '/');
    }

    /**
     * Dùng chung cho trang chủ hiện hành và trang chủ hội thảo lưu trữ (US8).
     *
     * @param string $bannerHtml dải thông báo phía trên (ví dụ "This is an archived conference"); đã thoát HTML
     */
    public function renderHome(Conference $conference, string $currentPath, string $bannerHtml = ''): Response
    {
        $context = new PublicPageContext($this->services, $conference);
        $context->shareWithView($currentPath);

        $dates = new ImportantDateRepository($this->services->db);
        $home = new HomeRepository($this->services->db);
        $intro = (string) $conference->homeIntroHtml;

        $html = $this->services->view->page('public/layout', 'public/home', [
            'pageTitle' => '',
            'metaDescription' => mb_substr(trim(html_entity_decode(strip_tags($intro), ENT_QUOTES, 'UTF-8')), 0, 160),
            'canonicalUrl' => PublicPageContext::absoluteUrl(url('/')),
            'ogImage' => $context->shareImageUrl(null),
            'introHtml' => $intro,
            'conferenceDay' => $dates->findVisibleByRole($conference->id, 'conference_day'),
            'abstractDeadline' => $dates->findVisibleByRole($conference->id, 'abstract_deadline'),
            'fullPaperDeadline' => $dates->findVisibleByRole($conference->id, 'full_paper_deadline'),
            'slides' => $home->visibleSlides($conference->id),
            'highlights' => $home->visibleHighlights($conference->id),
            'callForPapersUrl' => $context->linkToPage('call-for-papers'),
            'paperSubmissionUrl' => $context->linkToPage('paper-submission'),
            'bannerHtml' => $bannerHtml,
        ]);

        return Response::html($html);
    }
}
