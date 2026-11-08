<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Lang;
use App\Core\Response;
use App\Models\Conference;
use App\Services\PublicPageContext;

/**
 * Hội thảo các năm trước (FR-038a): /{year}, /{year}/{slug}, và trang /past-conferences.
 *
 * Chỉ hội thảo ĐÃ TỪNG được đặt làm hiện hành mới xem được; năm của hội thảo hiện hành chuyển về đường dẫn chính.
 * Mọi liên kết nội bộ trong trang lưu trữ giữ tiền tố /{year}.
 */
final class ArchiveController extends Controller
{
    public function home(string $year): Response
    {
        $conference = $this->archivedConference($year);
        if ($conference->isCurrent) {
            return Response::redirect('/', 301);
        }
        $this->prepareArchive($conference);

        return (new HomeController($this->services, $this->request))->renderHome($conference, '/' . $year, $this->banner());
    }

    public function page(string $year, string $slug): Response
    {
        $conference = $this->archivedConference($year);
        if ($conference->isCurrent) {
            return Response::redirect('/' . $slug, 301);
        }
        $this->prepareArchive($conference);

        return (new PageController($this->services, $this->request))->renderPage($conference, $slug, "/$year/$slug", $this->banner());
    }

    /** Danh sách các năm đã lưu trữ, mới nhất trước. */
    public function index(): Response
    {
        $current = $this->services->conferences->current() ?? $this->notFound();
        (new PublicPageContext($this->services, $current))->shareWithView('/past-conferences');

        $html = $this->services->view->page('public/layout', 'public/past-conferences', [
            'pageTitle' => t('archive.title'),
            'metaDescription' => t('archive.title'),
            'canonicalUrl' => PublicPageContext::absoluteUrl('/past-conferences'),
            'ogImage' => '',
            'archived' => $this->services->conferences->archived(),
        ]);

        return Response::html($html);
    }

    /** Hội thảo theo năm, chỉ khi đã từng hiện hành; nếu không thì 404. */
    private function archivedConference(string $year): Conference
    {
        $conference = $this->services->conferences->findByYear((int) $year);

        return $conference !== null && $conference->hasBeenCurrent ? $conference : $this->notFound();
    }

    /** Liên kết trong trang có tiền tố /{year}; giao diện dùng ngôn ngữ công khai của hội thảo đó. */
    private function prepareArchive(Conference $conference): void
    {
        $this->services->view->setUrlPrefix('/' . $conference->year);
        Lang::setCurrent($this->services->lang($conference->publicLanguage));
    }

    private function banner(): string
    {
        return $this->services->view->render('public/partials/archive-banner');
    }
}
