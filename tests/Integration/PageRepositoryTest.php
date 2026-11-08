<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Clock;
use App\Repositories\PageRepository;
use Tests\Support\CreatesContent;
use Tests\Support\DatabaseTestCase;

final class PageRepositoryTest extends DatabaseTestCase
{
    use CreatesContent;

    private PageRepository $pages;
    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pages = new PageRepository($this->db);
        $this->conferenceId = $this->createConference();
        Clock::freeze('2026-03-10 00:00:00');
    }

    public function test_finds_published_page_by_slug(): void
    {
        $id = $this->createPage($this->conferenceId, ['slug' => 'call-for-papers', 'title' => 'Call for Papers']);

        $page = $this->pages->findPublicBySlug($this->conferenceId, 'call-for-papers');

        self::assertSame($id, $page?->id);
        self::assertSame('Call for Papers', $page->title);
    }

    public function test_draft_hidden_and_deleted_pages_are_not_public(): void
    {
        $this->createPage($this->conferenceId, ['slug' => 'draft', 'status' => 'draft', 'published_at' => null]);
        $this->createPage($this->conferenceId, ['slug' => 'hidden', 'status' => 'hidden']);
        $this->createPage($this->conferenceId, ['slug' => 'deleted', 'deleted_at' => '2026-03-01 00:00:00']);

        foreach (['draft', 'hidden', 'deleted', 'missing'] as $slug) {
            self::assertNull($this->pages->findPublicBySlug($this->conferenceId, $slug), $slug);
        }
    }

    public function test_scheduled_page_appears_only_from_publish_time(): void
    {
        $this->createPage($this->conferenceId, ['slug' => 'later', 'published_at' => '2026-03-10 08:00:00']);

        self::assertNull($this->pages->findPublicBySlug($this->conferenceId, 'later'));

        Clock::freeze('2026-03-10 08:00:00');
        self::assertNotNull($this->pages->findPublicBySlug($this->conferenceId, 'later'));
    }

    public function test_page_of_another_conference_is_not_found(): void
    {
        $this->createPage($this->createConference(), ['slug' => 'other']);

        self::assertNull($this->pages->findPublicBySlug($this->conferenceId, 'other'));
    }

    public function test_old_slug_redirects_to_current_slug_of_public_page(): void
    {
        $id = $this->createPage($this->conferenceId, ['slug' => 'new-slug']);
        $this->db->insert('slug_redirects', ['conference_id' => $this->conferenceId, 'old_slug' => 'old-slug', 'page_id' => $id]);

        self::assertSame('new-slug', $this->pages->findRedirect($this->conferenceId, 'old-slug'));
        self::assertNull($this->pages->findRedirect($this->conferenceId, 'unknown'));
    }

    public function test_redirect_to_non_public_page_is_ignored(): void
    {
        $id = $this->createPage($this->conferenceId, ['slug' => 'secret', 'status' => 'hidden']);
        $this->db->insert('slug_redirects', ['conference_id' => $this->conferenceId, 'old_slug' => 'was-public', 'page_id' => $id]);

        self::assertNull($this->pages->findRedirect($this->conferenceId, 'was-public'));
    }

    public function test_lists_public_pages_and_tags(): void
    {
        $a = $this->createPage($this->conferenceId, ['title' => 'A']);
        $this->createPage($this->conferenceId, ['title' => 'B draft', 'status' => 'draft']);
        $this->tagPage($a, 'deadline', 'submission');

        $public = $this->pages->publicPagesForConference($this->conferenceId);

        self::assertSame([$a], array_map(static fn ($p) => $p->id, $public));
        self::assertSame([$a => ['deadline', 'submission']], $this->pages->tagsFor([$a]));
        self::assertSame([], $this->pages->tagsFor([]));
    }
}
