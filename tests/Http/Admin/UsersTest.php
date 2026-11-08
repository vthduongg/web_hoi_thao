<?php

declare(strict_types=1);

namespace Tests\Http\Admin;

use App\Repositories\UserRepository;
use Tests\Support\HttpTestCase;

/**
 * Quản lý tài khoản (chỉ quản trị viên).
 * Kiểm tra 403 cho /admin/settings, /admin/menu, /admin/activity, /admin/conferences nằm trong test của US6, US7, US8.
 */
final class UsersTest extends HttpTestCase
{
    private UserRepository $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = new UserRepository($this->db);
        // Trong CSDL test có thể còn quản trị viên từ dữ liệu seed — tắt để kiểm tra quy tắc "quản trị viên cuối cùng"
        $this->db->execute("UPDATE users SET is_active = 0 WHERE role = 'admin'");
    }

    public function test_editor_cannot_access_user_management(): void
    {
        $this->loginAs('editor');

        foreach (['/admin/users', '/admin/users/new'] as $path) {
            $this->get($path);
            $this->assertStatus(403);
            $this->assertSee('Bạn không có quyền thực hiện thao tác này.');
        }
        $this->post('/admin/users/new', ['email' => 'x@y.vn', 'full_name' => 'X', 'role' => 'admin', 'password' => 'Secret123']);
        $this->assertStatus(403);
    }

    public function test_admin_lists_and_creates_user(): void
    {
        $this->loginAs('admin');

        $this->get('/admin/users');
        $this->assertStatus(200);

        $this->post('/admin/users/new', ['email' => 'New.Editor@Example.com', 'full_name' => 'Nguyễn Văn A', 'role' => 'editor', 'password' => 'Temp12345']);

        $this->assertRedirect('/admin/users');
        $user = $this->users->findByEmail('new.editor@example.com');
        self::assertSame('Nguyễn Văn A', $user?->fullName);
        self::assertSame('editor', $user->role);
        self::assertTrue(password_verify('Temp12345', $user->passwordHash));
        self::assertSame(1, (int) $this->db->value("SELECT COUNT(*) FROM activity_logs WHERE action = 'account_change' AND entity_id = ?", [$user->id]));
    }

    public function test_create_validates_fields_and_duplicate_email(): void
    {
        $this->loginAs('admin');
        $this->users->create('taken@example.com', 'Đã có', 'Secret123', 'editor');

        $this->post('/admin/users/new', ['email' => 'TAKEN@example.com', 'full_name' => '', 'role' => 'boss', 'password' => 'short']);

        $this->assertStatus(422);
        $this->assertSee('Email này đã được dùng.');
        $this->assertSee('Trường này là bắt buộc.');
        $this->assertSee('Giá trị không hợp lệ.');
        $this->assertSee('Tối thiểu 8 ký tự.');
    }

    public function test_admin_edits_user(): void
    {
        $this->loginAs('admin');
        $id = $this->users->create('edit@example.com', 'Tên cũ', 'Secret123', 'editor');

        $this->get("/admin/users/$id");
        $this->assertStatus(200);
        $this->post("/admin/users/$id", ['email' => 'edit@example.com', 'full_name' => 'Tên mới', 'role' => 'admin', 'password' => '']);

        $this->assertRedirect('/admin/users');
        $user = $this->users->find($id);
        self::assertSame('Tên mới', $user->fullName);
        self::assertSame('admin', $user->role);
        self::assertTrue(password_verify('Secret123', $user->passwordHash), 'Để trống mật khẩu thì giữ mật khẩu cũ');
    }

    public function test_lock_ends_open_session_and_blocks_login_then_unlock(): void
    {
        $id = $this->users->create('victim@example.com', 'Bị khoá', 'Secret123', 'editor');
        $this->post('/admin/login', ['email' => 'victim@example.com', 'password' => 'Secret123']);
        $victimSession = clone $this->sessionStore;

        $this->post('/admin/logout');
        $this->loginAs('admin');
        $this->post("/admin/users/$id/lock");
        $this->assertRedirect('/admin/users');

        // Phiên đang mở của người bị khoá chấm dứt
        $this->sessionStore = $victimSession;
        $this->app = \App\Core\AppFactory::create(dirname(__DIR__, 3), $this->db, $this->sessionStore);
        $this->get('/admin');
        $this->assertRedirect('/admin/login?next=%2Fadmin');

        // Sai mật khẩu: không để lộ rằng tài khoản đang bị khoá
        $this->post('/admin/login', ['email' => 'victim@example.com', 'password' => 'WrongPass9']);
        $this->assertStatus(422);
        $this->assertSee('Email hoặc mật khẩu không đúng.');
        $this->assertDontSee('đã bị khoá');

        $this->post('/admin/login', ['email' => 'victim@example.com', 'password' => 'Secret123']);
        $this->assertStatus(422);
        $this->assertSee('Tài khoản đã bị khoá. Vui lòng liên hệ quản trị viên.');

        $this->loginAs('admin');
        $this->post("/admin/users/$id/unlock");
        $this->post('/admin/logout');
        $this->post('/admin/login', ['email' => 'victim@example.com', 'password' => 'Secret123']);
        $this->assertRedirect('/admin');
    }

    public function test_cannot_lock_or_demote_last_active_admin(): void
    {
        $adminId = $this->loginAs('admin');

        $this->post("/admin/users/$adminId/lock");
        $this->assertRedirect('/admin/users');
        self::assertTrue($this->users->find($adminId)->isActive);

        $this->post("/admin/users/$adminId", ['email' => $this->users->find($adminId)->email, 'full_name' => 'Admin', 'role' => 'editor', 'password' => '']);
        $this->assertStatus(422);
        $this->assertSee('Phải luôn còn ít nhất một quản trị viên đang hoạt động.');
        self::assertSame('admin', $this->users->find($adminId)->role);
    }
}
