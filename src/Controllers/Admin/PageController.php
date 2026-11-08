<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Public\PageController as PublicPageController;
use App\Core\Lang;
use App\Core\Response;
use App\Models\Conference;
use App\Models\Page;
use App\Repositories\MediaRepository;
use App\Repositories\PageRepository;
use App\Services\EditConflict;
use App\Services\PageService;
use App\Services\SlugValidator;

/** Quản lý trang nội dung (FR-023 – FR-027). */
final class PageController extends AdminController
{
    private function pages(): PageRepository
    {
        return new PageRepository($this->services->db);
    }

    private function service(): PageService
    {
        return new PageService($this->services->db, $this->services->sanitizer, $this->services->logger);
    }

    private function conference(): Conference
    {
        return $this->editingConference() ?? $this->notFound();
    }

    /** Trang thuộc hội thảo đang sửa, hoặc 404. */
    private function findPage(string $id): Page
    {
        return $this->pages()->findForAdmin($this->conference()->id, (int) $id) ?? $this->notFound();
    }

    public function index(): Response
    {
        $filters = [
            'status' => $this->request->input('status'),
            'tag' => mb_strtolower($this->request->input('tag')),
            'q' => $this->request->input('q'),
        ];

        return $this->render('admin/pages/index', [
            'pageTitle' => t('pages.title'),
            'pages' => $this->pages()->listForAdmin($this->conference()->id, $filters['status'], $filters['tag'], $filters['q']),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return $this->form(null, [
            'title' => '', 'slug' => '', 'body_html' => '', 'module' => 'none', 'seo_title' => '', 'seo_description' => '',
            'featured_media_id' => null, 'tags' => [], 'status' => 'draft', 'published_at' => '',
        ]);
    }

    public function store(): Response
    {
        $data = $this->formData();
        $errors = $this->service()->validate($this->conference()->id, $data, null);
        if ($errors !== []) {
            return $this->form(null, $data, $errors);
        }

        $id = $this->service()->create($this->conference()->id, $data, $this->currentUser()->id);
        $this->services->flash->success(t('common.saved'));

        return $this->redirect("/admin/pages/$id");
    }

    public function edit(string $id): Response
    {
        $page = $this->findPage($id);

        return $this->form($page, [
            'title' => $page->title,
            'slug' => $page->slug,
            'body_html' => $page->bodyHtml,
            'module' => $page->module,
            'seo_title' => $page->seoTitle ?? '',
            'seo_description' => $page->seoDescription ?? '',
            'featured_media_id' => $page->featuredMediaId,
            'tags' => $this->pages()->tagsFor([$page->id])[$page->id] ?? [],
            'status' => $page->status,
            'published_at' => format_date($page->publishedAt, 'input'),
        ]);
    }

    public function update(string $id): Response
    {
        $page = $this->findPage($id);
        $data = $this->formData();
        $errors = $this->service()->validate($this->conference()->id, $data, $page->id);
        if ($errors !== []) {
            return $this->form($page, $data, $errors);
        }

        try {
            $this->service()->update($page->id, $data, (int) $this->request->input('version'), $this->currentUser()->id);
        } catch (EditConflict) {
            return $this->form($page, $data, [], conflict: true);
        }
        $this->services->flash->success(t('common.saved'));

        return $this->redirect("/admin/pages/{$page->id}");
    }

    public function preview(string $id): Response
    {
        $page = $this->findPage($id);
        $conference = $this->conference();
        $banner = $this->services->view->render('public/partials/preview-banner', ['text' => t('pages.preview_banner')]);

        // Phần còn lại của trang dùng ngôn ngữ công khai, giống hệt khách sẽ thấy
        Lang::setCurrent($this->services->lang($conference->publicLanguage));

        return (new PublicPageController($this->services, $this->request))
            ->renderPageModel($conference, $page, '/' . $page->slug, $banner);
    }

    public function publish(string $id): Response
    {
        return $this->changeStatus($id, 'published', $this->request->input('published_at'));
    }

    public function hide(string $id): Response
    {
        return $this->changeStatus($id, 'hidden', null);
    }

    public function draft(string $id): Response
    {
        return $this->changeStatus($id, 'draft', null);
    }

    public function revisions(string $id): Response
    {
        $page = $this->findPage($id);

        return $this->render('admin/pages/revisions', [
            'pageTitle' => t('pages.revisions'),
            'page' => $page,
            'revisions' => $this->pages()->revisions($page->id),
        ]);
    }

    public function restore(string $id, string $revisionNo): Response
    {
        $page = $this->findPage($id);
        if ($this->pages()->findRevision($page->id, (int) $revisionNo) === null) {
            $this->notFound();
        }

        $this->service()->restoreRevision($page->id, (int) $revisionNo, $this->currentUser()->id);
        $this->services->flash->success(t('pages.restored'));

        return $this->redirect("/admin/pages/{$page->id}");
    }

    public function delete(string $id): Response
    {
        $page = $this->findPage($id);
        $menuLabels = $this->pages()->menuLabelsPointingTo($page->id);

        // Trang đang có trong menu: hỏi xác nhận lần hai, nêu rõ mục menu sẽ bị ẩn
        if ($menuLabels !== [] && $this->request->input('confirmed') !== '1') {
            return $this->render('admin/pages/confirm-delete', [
                'pageTitle' => t('common.delete'),
                'page' => $page,
                'menuLabels' => $menuLabels,
            ]);
        }

        $this->service()->softDelete($page->id, $this->currentUser()->id);
        $this->services->flash->success(t('common.deleted'));

        return $this->redirect('/admin/pages');
    }

    private function changeStatus(string $id, string $status, ?string $publishedAt): Response
    {
        $page = $this->findPage($id);
        $publishedAt = ($publishedAt ?? '') === '' ? null : $publishedAt;
        if ($publishedAt !== null && $this->validate(['t' => $publishedAt], ['t' => 'datetime']) !== []) {
            $this->services->flash->error(t('validation.date'));

            return $this->redirect("/admin/pages/{$page->id}");
        }

        $this->service()->changeStatus($page->id, $status, $publishedAt, $this->currentUser()->id);
        $this->services->flash->success(t('pages.status_changed'));

        return $this->redirect($this->request->input('return') === 'list' ? '/admin/pages' : "/admin/pages/{$page->id}");
    }

    /** Dữ liệu từ biểu mẫu, đã chuẩn hoá (thẻ thành danh sách, slug tự gợi ý từ tiêu đề nếu để trống). */
    private function formData(): array
    {
        $title = $this->request->input('title');
        $slug = $this->request->input('slug');
        $mediaId = $this->request->input('featured_media_id');

        return [
            'title' => $title,
            'slug' => $slug === '' && $title !== '' ? SlugValidator::fromTitle($title) : $slug,
            'body_html' => (string) ($this->request->post['body_html'] ?? ''),
            'module' => $this->request->input('module', 'none'),
            'seo_title' => $this->request->input('seo_title'),
            'seo_description' => $this->request->input('seo_description'),
            'featured_media_id' => ctype_digit($mediaId) ? (int) $mediaId : null,
            'tags' => self::parseTags($this->request->input('tags')),
            'status' => $this->request->input('status', 'draft'),
            'published_at' => $this->request->input('published_at'),
        ];
    }

    /** "Hạn nộp, Travel ,hạn nộp" → ["hạn nộp", "travel"] */
    private static function parseTags(string $input): array
    {
        $tags = array_map(static fn (string $tag): string => mb_strtolower(trim($tag)), explode(',', $input));

        return array_values(array_unique(array_filter($tags, static fn (string $tag): bool => $tag !== '')));
    }

    private function form(?Page $page, array $values, array $errors = [], bool $conflict = false): Response
    {
        $status = $conflict ? 409 : ($errors === [] ? 200 : 422);

        return $this->render('admin/pages/form', [
            'pageTitle' => $page === null ? t('pages.create') : t('pages.edit'),
            'page' => $page,
            'values' => $values,
            'errors' => $errors,
            'conflict' => $conflict,
            'featuredMedia' => (new MediaRepository($this->services->db))->find($values['featured_media_id']),
            'extraScripts' => ['vendor/tinymce/tinymce.min.js', 'js/editor.js', 'js/media-picker.js'],
        ], $status);
    }
}
