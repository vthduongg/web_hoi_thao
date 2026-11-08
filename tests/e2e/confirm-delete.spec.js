// Test trình duyệt cho confirm-delete.js — US2.
// Dữ liệu seed --test có trang "E2E Delete Me" chỉ dùng cho test này.
const { test, expect } = require('./fixtures');
const { loginAs } = require('./helpers');

test.skip(({ viewport }) => viewport.width < 900, 'trang quản trị kiểm tra ở khung máy tính');

test('nút Xoá hỏi xác nhận: Huỷ thì giữ, Đồng ý thì xoá', async ({ page }) => {
  await loginAs(page, 'editor');
  await page.goto('/admin/pages');
  const row = page.locator('tr', { hasText: 'E2E Delete Me' });

  page.once('dialog', (dialog) => {
    expect(dialog.type()).toBe('confirm');
    dialog.dismiss();
  });
  await row.getByRole('button', { name: 'Xoá' }).click();
  await expect(row).toBeVisible();

  page.once('dialog', (dialog) => dialog.accept());
  await row.getByRole('button', { name: 'Xoá' }).click();
  await expect(page.locator('.flash--success')).toBeVisible();
  await expect(page.locator('tr', { hasText: 'E2E Delete Me' })).toHaveCount(0);

  const response = await page.goto('/e2e-delete-me');
  expect(response.status()).toBe(404);

  // Trang vừa xoá nằm trong Thùng rác và có nút Khôi phục
  await page.goto('/admin/trash');
  const trashRow = page.locator('tr', { hasText: 'E2E Delete Me' });
  await expect(trashRow).toBeVisible();
  await expect(trashRow.getByRole('button', { name: 'Khôi phục' })).toBeVisible();
});
