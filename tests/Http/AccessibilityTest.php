<?php

declare(strict_types=1);

namespace Tests\Http;

use DOMDocument;
use DOMXPath;
use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

/** Kiểm tra tiếp cận cơ bản trên mọi loại trang công khai (FR-031, SC Lighthouse ≥ 90). */
final class AccessibilityTest extends HttpTestCase
{
    use CreatesContent;

    protected function setUp(): void
    {
        parent::setUp();
        $logo = $this->createMedia(['stored_path' => 'uploads/a11y/logo.png']);
        $photo = $this->createMedia(['stored_path' => 'uploads/a11y/photo.jpg', 'thumb_small_path' => 'uploads/a11y/photo-400.jpg']);
        $conference = $this->createCurrentConference(['name' => 'ELT 7027', 'year' => 7027, 'logo_media_id' => $logo]);
        $this->db->insert('slides', ['conference_id' => $conference, 'media_id' => $photo, 'alt_text' => 'Main hall', 'sort_order' => 10]);
        $this->db->insert('keynote_speakers', ['conference_id' => $conference, 'full_name' => 'Jane Doe', 'photo_media_id' => $photo, 'sort_order' => 10]);
        $tier = $this->db->insert('sponsor_tiers', ['conference_id' => $conference, 'name' => 'Organizer', 'sort_order' => 10]);
        $this->db->insert('sponsors', ['conference_id' => $conference, 'sponsor_tier_id' => $tier, 'name' => 'HPU2', 'logo_media_id' => $logo, 'sort_order' => 10]);
        $cfp = $this->createPage($conference, ['slug' => 'call-for-papers', 'title' => 'Call for Papers', 'body_html' => '<h2>Topics</h2><p><img src="/uploads/a11y/photo.jpg" alt="Poster"></p>']);
        $this->createPage($conference, ['slug' => 'keynote-speakers', 'title' => 'Keynote Speakers', 'module' => 'keynote_speakers']);
        $this->createPage($conference, ['slug' => 'sponsors', 'title' => 'Sponsors', 'module' => 'sponsors']);
        $group = $this->createMenuItem($conference, ['label' => 'Conference']);
        $this->createMenuItem($conference, ['parent_id' => $group, 'label' => 'Call for Papers', 'page_id' => $cfp]);
    }

    private function dom(string $path, int $status = 200): DOMXPath
    {
        $response = $this->get($path);
        self::assertSame($status, $response->status, $path);
        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $response->body);
        libxml_clear_errors();

        return new DOMXPath($document);
    }

    /** @return list<array{0: string, 1: int}> */
    public static function pages(): array
    {
        return [['/', 200], ['/call-for-papers', 200], ['/keynote-speakers', 200], ['/sponsors', 200], ['/past-conferences', 200], ['/no-such-page', 404]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_every_public_page_meets_basic_accessibility(string $path, int $status): void
    {
        $xpath = $this->dom($path, $status);

        self::assertSame(1, $xpath->query('//h1')->length, "$path: phải có đúng một <h1>");
        foreach ($xpath->query('//img') as $img) {
            self::assertTrue($img->hasAttribute('alt'), "$path: ảnh {$img->getAttribute('src')} thiếu alt");
        }
        $skip = $xpath->query('//a[@href="#main-content"]');
        self::assertSame(1, $skip->length, "$path: thiếu link Skip to content");
        self::assertSame('Skip to content', trim($skip->item(0)->textContent));
        self::assertSame(1, $xpath->query('//*[@id="main-content"]')->length, "$path: thiếu đích của Skip to content");
        self::assertSame('en', $xpath->query('//html')->item(0)->getAttribute('lang'));
    }

    public function test_submenu_buttons_have_aria_expanded(): void
    {
        $xpath = $this->dom('/call-for-papers');

        $toggles = $xpath->query('//button[@data-submenu-toggle]');
        self::assertGreaterThan(0, $toggles->length);
        foreach ($toggles as $button) {
            self::assertSame('false', $button->getAttribute('aria-expanded'));
            self::assertSame(1, $xpath->query('//*[@id="' . $button->getAttribute('aria-controls') . '"]')->length);
        }
    }

    public function test_content_images_get_alt_and_lazy_loading(): void
    {
        $xpath = $this->dom('/call-for-papers');

        $poster = $xpath->query('//article//img[@alt="Poster"]')->item(0);
        self::assertNotNull($poster);
        self::assertSame('lazy', $poster->getAttribute('loading'));
    }

    public function test_speaker_photo_has_responsive_srcset(): void
    {
        $xpath = $this->dom('/keynote-speakers');

        $photo = $xpath->query('//img[contains(@class, "speaker-card__photo")]')->item(0);
        self::assertSame('/uploads/a11y/photo-400.jpg 400w', $photo->getAttribute('srcset'));
    }
}
