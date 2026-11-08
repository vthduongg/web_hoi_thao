<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class SettingsTest extends HttpTestCase
{
    use CreatesContent;

    private int $conferenceId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conferenceId = $this->createCurrentConference(['year' => 2950]);
        $this->createPage($this->conferenceId, ['slug' => 'contact']);
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'name' => 'ELT Conference 2950', 'theme' => 'Practices, Opportunities and Challenges', 'year' => '2950',
            'venue' => 'HPU2', 'conference_language' => 'English', 'logo_media_id' => '', 'favicon_media_id' => '',
            'contact_email' => 'new@hpu2.edu.vn', 'contact_phone' => '+84 211 000', 'footer_text' => 'Faculty of Foreign Languages',
            'copyright_text' => '© 2950 HPU2', 'submission_url' => 'https://forms.example.com/new',
            'facebook_url' => '', 'linkedin_url' => '', 'x_url' => '', 'youtube_url' => '',
            'conference_date_start' => '2950-06-18', 'conference_date_end' => '2950-06-19',
        ], $overrides);
    }

    public function test_only_admin_can_access(): void
    {
        $this->loginAs('editor');

        $this->get('/admin/settings');
        $this->assertStatus(403);
        $this->post('/admin/settings', $this->form());
        $this->assertStatus(403);
    }

    public function test_admin_saves_settings_and_public_pages_use_them(): void
    {
        $logo = $this->createMedia(['stored_path' => 'uploads/t/new-logo.png']);
        $favicon = $this->createMedia(['stored_path' => 'uploads/t/favicon.png']);
        $this->loginAs('admin');

        $this->get('/admin/settings');
        $this->assertStatus(200);
        $this->post('/admin/settings', $this->form(['logo_media_id' => (string) $logo, 'favicon_media_id' => (string) $favicon]));
        $this->assertRedirect('/admin/settings');

        $this->get('/contact');
        $this->assertSee('src="/uploads/t/new-logo.png"');
        $this->assertSee('<link rel="icon" href="/uploads/t/favicon.png">');
        $this->assertSee('href="mailto:new@hpu2.edu.vn"');
        $this->assertSee('© 2950 HPU2');
    }

    public function test_conference_dates_are_stored_in_the_conference_day_milestone(): void
    {
        $this->loginAs('admin');

        $this->post('/admin/settings', $this->form());

        $row = $this->db->first("SELECT date_start, date_end FROM important_dates WHERE conference_id = ? AND special_role = 'conference_day' AND deleted_at IS NULL", [$this->conferenceId]);
        self::assertSame('2950-06-18', substr((string) $row['date_start'], 0, 10));
        self::assertSame('2950-06-19', substr((string) $row['date_end'], 0, 10));

        $this->post('/admin/settings', $this->form(['conference_date_start' => '2950-07-01', 'conference_date_end' => '']));
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM important_dates WHERE conference_id = ? AND special_role = 'conference_day'", [$this->conferenceId]));
        $this->get('/');
        $this->assertSee('1 July 2950');
    }

    public function test_validation(): void
    {
        $this->loginAs('admin');

        $this->post('/admin/settings', $this->form(['name' => '', 'year' => '', 'contact_email' => 'bad', 'submission_url' => 'forms.example.com']));

        $this->assertStatus(422);
        self::assertSame(2, substr_count($this->lastResponse->body, 'Trường này là bắt buộc.'));
        $this->assertSee('Email không hợp lệ.');
        $this->assertSee('Đường dẫn phải bắt đầu bằng http:// hoặc https://.');
    }

    public function test_year_must_be_unique(): void
    {
        $this->createConference(['year' => 2951]);
        $this->loginAs('admin');

        $this->post('/admin/settings', $this->form(['year' => '2951']));

        $this->assertStatus(422);
        $this->assertSee('Đã có hội thảo khác dùng năm này.');
    }
}
