<?php

declare(strict_types=1);

namespace Tests\Http\Public;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class SubmissionButtonTest extends HttpTestCase
{
    use CreatesContent;

    public function test_configured_url_opens_in_new_tab_on_home_and_content_pages(): void
    {
        $conferenceId = $this->createCurrentConference(['submission_url' => 'https://forms.example.com/elt-2026']);
        $this->createPage($conferenceId, ['slug' => 'paper-submission']);

        foreach (['/', '/paper-submission'] as $path) {
            $this->get($path);
            $this->assertSee('<a class="button button--primary" href="https://forms.example.com/elt-2026" target="_blank" rel="noopener">Submit your paper</a>');
        }
    }

    public function test_missing_url_shows_disabled_button(): void
    {
        $conferenceId = $this->createCurrentConference(['submission_url' => null]);
        $this->createPage($conferenceId, ['slug' => 'call-for-papers']);

        $this->get('/call-for-papers');

        $this->assertSee('aria-disabled="true"');
        $this->assertSee('Submission opening soon');
        $this->assertDontSee('Submit your paper');
    }
}
