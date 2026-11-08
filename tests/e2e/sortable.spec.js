// Test trình duyệt cho sortable-list.js trên trang quản lý mốc thời gian (US3).
// Dữ liệu seed --test có 8 mốc thời gian.
const { test, expect } = require('@playwright/test');
const { loginAs } = require('./helpers');

test.skip(({ viewport }) => viewport.width < 900, 'trang quản trị kiểm tra ở khung máy tính');

async function adminLabels(page) {
  return page.locator('[data-sortable] > [data-sortable-item] [data-item-label]').allInnerTexts();
}

async function waitSaved(page) {
  await expect(page.locator('[data-sortable-status]')).toHaveText('Đã lưu thứ tự');
}

test('kéo mốc thứ 3 lên đầu, thứ tự được lưu và áp dụng ở trang công khai', async ({ page }) => {
  await loginAs(page, 'editor');
  await page.goto('/admin/dates');
  const before = await adminLabels(page);
  const items = page.locator('[data-sortable] > [data-sortable-item]');

  await items.nth(2).dragTo(items.nth(0), { targetPosition: { x: 20, y: 2 } });
  await waitSaved(page);

  await page.reload();
  const after = await adminLabels(page);
  expect(after[0]).toBe(before[2]);
  expect(after.slice(1, 3)).toEqual(before.slice(0, 2));

  await page.goto('/important-dates');
  const publicLabels = await page.locator('.dates-table tbody tr td:first-child').allInnerTexts();
  expect(publicLabels[0]).toContain(before[2]);
});

test('chỉ dùng bàn phím: Tab tới nút ▲ rồi Enter để đưa mốc lên', async ({ page }) => {
  await loginAs(page, 'editor');
  await page.goto('/admin/dates');
  const before = await adminLabels(page);

  // Bám theo mục cụ thể (data-id), vì vị trí nth() thay đổi sau khi di chuyển
  const movedId = await page.locator('[data-sortable] > [data-sortable-item]').nth(1).getAttribute('data-id');
  const upButton = page.locator(`[data-sortable-item][data-id="${movedId}"] [data-move="up"]`);
  await upButton.focus();
  await page.keyboard.press('Enter');
  await waitSaved(page);
  await expect(upButton).toBeFocused();

  const after = await adminLabels(page);
  expect(after.slice(0, 2)).toEqual([before[1], before[0]]);
});

test('máy tính bảng 768px: chạm nút ▼ để đưa mốc xuống', async ({ browser, baseURL }) => {
  const context = await browser.newContext({ baseURL, viewport: { width: 768, height: 1024 }, hasTouch: true });
  const page = await context.newPage();
  await loginAs(page, 'editor');
  await page.goto('/admin/dates');
  const before = await adminLabels(page);

  await page.locator('[data-sortable] > [data-sortable-item]').nth(0).locator('[data-move="down"]').tap();
  await waitSaved(page);

  const after = await adminLabels(page);
  expect(after.slice(0, 2)).toEqual([before[1], before[0]]);
  await context.close();
});
