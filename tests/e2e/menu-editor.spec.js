// Test trình duyệt cho cây menu (sortable-list.js chế độ lồng cấp, tối đa 2 cấp) — US6.
// Menu seed: Conference (5 mục con), Program, Paper Submission, Sponsor, Contact, Travel Info.
const { test, expect } = require('./fixtures');
const { loginAs } = require('./helpers');

test.skip(({ viewport }) => viewport.width < 900, 'trang quản trị kiểm tra ở khung máy tính');

const topItems = '[data-sortable] > [data-sortable-item]';
// Mục menu (thẻ <li> gần nhất) chứa nhãn — không lấy nhầm mục cha có mục con trùng nhãn
const itemByLabel = (page, label) => page.locator(`[data-item-label]:text-is("${label}")`).locator('xpath=ancestor::li[@data-sortable-item][1]');

async function waitSaved(page) {
  await expect(page.locator('[data-sortable-status]')).toHaveText('Đã lưu thứ tự');
}

test('kéo "Program" lên đầu và đưa một mục thành mục con → menu công khai cập nhật', async ({ page }) => {
  await loginAs(page, 'admin');
  await page.goto('/admin/menu');

  await itemByLabel(page, 'Program').locator('xpath=./span[contains(@class, "crud-list__main")]').dragTo(page.locator(topItems).first(), { targetPosition: { x: 20, y: 2 } });
  await waitSaved(page);
  await expect(page.locator(`${topItems} [data-item-label]`).first()).toHaveText('Program');

  // "Travel Info" thành mục con của mục đứng trước nó (nút →)
  await itemByLabel(page, 'Travel Info').locator('xpath=./span/button[@data-move="in"]').click();
  await waitSaved(page);

  await page.goto('/');
  const firstTop = page.locator('.main-menu__list > .main-menu__item').first();
  await expect(firstTop).toContainText('Program');
  await expect(page.locator('.main-menu__submenu a', { hasText: 'Travel Info' })).toHaveCount(1);
});

test('không cho đưa mục xuống cấp 3', async ({ page }) => {
  await loginAs(page, 'admin');
  await page.goto('/admin/menu');

  // "Important Dates" đang là mục con (cấp 2) của "Conference"; thụt thêm sẽ thành cấp 3
  await itemByLabel(page, 'Important Dates').locator('xpath=./span/button[@data-move="in"]').click();

  await expect(page.locator('[data-sortable-status]')).toHaveText('Menu tối đa 2 cấp.');
  await page.reload();
  const conference = itemByLabel(page, 'Conference');
  await expect(conference.locator('[data-sortable-children] > [data-sortable-item] [data-item-label]', { hasText: 'Important Dates' })).toHaveCount(1);
});

test('chỉ dùng bàn phím: ▲ và ← thao tác được', async ({ page }) => {
  await loginAs(page, 'admin');
  await page.goto('/admin/menu');

  // Đưa "Keynote Speakers" (mục con cuối của Conference) ra cấp 1 bằng nút ←
  const keynoteOut = itemByLabel(page, 'Keynote Speakers').locator('xpath=./span/button[@data-move="out"]');
  await keynoteOut.focus();
  await page.keyboard.press('Enter');
  await waitSaved(page);
  await page.reload();
  await expect(page.locator(`${topItems} > .crud-list__main [data-item-label]`, { hasText: 'Keynote Speakers' })).toHaveCount(1);

  // Đưa nó lên một bậc bằng nút ▲
  const labelsBefore = await page.locator(`${topItems} > .crud-list__main [data-item-label]`).allInnerTexts();
  const index = labelsBefore.indexOf('Keynote Speakers');
  await itemByLabel(page, 'Keynote Speakers').locator('xpath=./span/button[@data-move="up"]').focus();
  await page.keyboard.press('Enter');
  await waitSaved(page);
  const labelsAfter = await page.locator(`${topItems} > .crud-list__main [data-item-label]`).allInnerTexts();
  expect(labelsAfter[index - 1]).toBe('Keynote Speakers');
});
