<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\EmbedConverter;
use PHPUnit\Framework\TestCase;

final class EmbedConverterTest extends TestCase
{
    public function test_youtube_watch_link_in_its_own_paragraph_becomes_nocookie_iframe(): void
    {
        $html = EmbedConverter::convert('<p>https://www.youtube.com/watch?v=abc123XYZ_-&amp;t=10</p>');

        self::assertStringContainsString('<div class="embed-responsive">', $html);
        self::assertStringContainsString('src="https://www.youtube-nocookie.com/embed/abc123XYZ_-"', $html);
        self::assertStringNotContainsString('<p>', $html);
    }

    public function test_short_youtu_be_link_inside_anchor(): void
    {
        $html = EmbedConverter::convert('<p><a href="https://youtu.be/abc123">https://youtu.be/abc123</a></p>');

        self::assertStringContainsString('src="https://www.youtube-nocookie.com/embed/abc123"', $html);
    }

    public function test_google_maps_embed_link_becomes_iframe(): void
    {
        $html = EmbedConverter::convert('<p>https://www.google.com/maps/embed?pb=!1m18!1m12</p>');

        self::assertStringContainsString('<div class="embed-responsive"><iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12"', $html);
    }

    public function test_existing_youtube_iframe_is_wrapped_and_switched_to_nocookie(): void
    {
        $html = EmbedConverter::convert('<iframe src="https://www.youtube.com/embed/xyz" width="560" height="315"></iframe>');

        self::assertStringContainsString('<div class="embed-responsive"><iframe src="https://www.youtube-nocookie.com/embed/xyz"', $html);
    }

    public function test_is_idempotent(): void
    {
        $once = EmbedConverter::convert('<p>https://youtu.be/abc123</p><iframe src="https://www.google.com/maps/embed?pb=x"></iframe>');

        self::assertSame($once, EmbedConverter::convert($once));
        self::assertSame(2, substr_count($once, 'embed-responsive'));
    }

    public function test_other_hosts_and_links_inside_sentences_are_left_alone(): void
    {
        foreach ([
            '<p>https://evil.example.com/watch?v=abc</p>',
            '<p>Watch https://youtu.be/abc123 before the conference.</p>',
            '<p>https://www.google.com/maps/place/Hanoi</p>',
        ] as $input) {
            self::assertSame($input, EmbedConverter::convert($input));
        }
    }
}
