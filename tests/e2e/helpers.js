// Hàm dùng chung cho các test Playwright.
// Tài khoản test do `php bin/seed.php --test` tạo (xem bin/seed.php).

const ACCOUNTS = {
  admin: { email: 'admin@test.local', password: 'Test@12345' },
  editor: { email: 'editor@test.local', password: 'Test@12345' },
};

/** Đăng nhập trang quản trị với vai trò 'admin' hoặc 'editor'. */
async function loginAs(page, role) {
  const account = ACCOUNTS[role];
  if (!account) throw new Error(`Không có tài khoản test cho vai trò: ${role}`);
  await page.goto('/admin/login');
  await page.getByLabel('Email').fill(account.email);
  await page.getByLabel('Mật khẩu').fill(account.password);
  await page.getByRole('button', { name: 'Đăng nhập' }).click();
  await page.waitForURL('**/admin');
}

module.exports = { loginAs, ACCOUNTS };
