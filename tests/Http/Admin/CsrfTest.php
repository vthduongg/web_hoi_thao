<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use Tests\Support\HttpTestCase;

final class CsrfTest extends HttpTestCase
{
    public function test_post_without_token_is_rejected_with_419(): void
    {
        $this->send('POST', '/admin/login', ['email' => 'a@b.vn', 'password' => 'x']);

        $this->assertStatus(419);
        $this->assertSee('Phiên làm việc đã hết hạn, vui lòng tải lại trang.');
    }

    public function test_post_with_wrong_token_is_rejected_with_419(): void
    {
        $this->app->services()->csrf->token();

        $this->post('/admin/login', ['_token' => str_repeat('0', 64), 'email' => 'a@b.vn', 'password' => 'x']);

        $this->assertStatus(419);
    }
}
