<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use Tests\Support\CreatesContent;
use Tests\Support\HttpTestCase;

final class ContactsTest extends HttpTestCase
{
    use CreatesContent;

    protected function setUp(): void
    {
        parent::setUp();
        $conferenceId = $this->createCurrentConference(['contact_email' => 'elt@hpu2.edu.vn']);
        $this->createPage($conferenceId, ['slug' => 'contact', 'module' => 'contacts']);
        $this->loginAs('editor');
    }

    private function form(array $overrides = []): array
    {
        return array_merge(['full_name' => 'Nguyễn Thị Hoa', 'affiliation' => 'Faculty of Foreign Languages', 'email' => 'hoa@hpu2.edu.vn', 'phone' => '+84 912.345-678'], $overrides);
    }

    public function test_invalid_email_and_phone_are_rejected(): void
    {
        $this->post('/admin/contacts/new', $this->form(['email' => 'not-email']));
        $this->assertStatus(422);
        $this->assertSee('Email không hợp lệ.');

        $this->post('/admin/contacts/new', $this->form(['phone' => '0912 abc']));
        $this->assertStatus(422);
        $this->assertSee('Số điện thoại chỉ gồm số, khoảng trắng và các dấu + . -');
    }

    public function test_public_contact_page_has_mailto_and_tel_links(): void
    {
        $this->post('/admin/contacts/new', $this->form());
        $this->assertRedirect('/admin/contacts');

        $this->get('/contact');

        $this->assertSee('href="mailto:elt@hpu2.edu.vn"');
        $this->assertSee('Nguyễn Thị Hoa');
        $this->assertSee('Faculty of Foreign Languages');
        $this->assertSee('href="mailto:hoa@hpu2.edu.vn"');
        $this->assertSee('href="tel:+84912345678"');
    }
}
