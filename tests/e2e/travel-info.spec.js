// Test trình duyệt cho Travel Info và Contact (US4): nút "Điểm tham quan", link YouTube/Google Maps dán vào nội dung,
// khung nhúng co giãn trên điện thoại, liên kết tel:/mailto:.
const { test, expect } = require('@playwright/test');
const { loginAs } = require('./helpers');

test('chèn điểm tham quan, dán link YouTube và bản đồ rồi lưu → trang công khai có khung nhúng', async ({ page, viewport }) => {
  test.skip(viewport.width < 900, 'soạn thảo kiểm tra ở khung máy tính');
  await loginAs(page, 'editor');
  await page.goto('/admin/pages');
  await page.getByRole('link', { name: 'Travel Info' }).click();
  await expect(page.locator('.tox-tinymce')).toBeVisible();

  // Nút "Điểm tham quan": chọn ảnh từ thư viện → chèn thêm một khối ảnh + tiêu đề + mô tả
  const editorBody = page.frameLocator('iframe.tox-edit-area__iframe').locator('body');
  const before = await editorBody.locator('.attraction-card').count();
  const [popup] = await Promise.all([
    page.waitForEvent('popup'),
    page.getByRole('button', { name: 'Điểm tham quan' }).click(),
  ]);
  const closed = popup.waitForEvent('close');
  await popup.locator('[data-media-item]', { hasText: 'slide-2.jpg' }).getByRole('button', { name: 'Chọn' }).click();
  await closed;
  await expect(editorBody.locator('.attraction-card')).toHaveCount(before + 1);
  const inserted = editorBody.locator('.attraction-card', { hasText: 'Tên điểm tham quan' });
  await expect(inserted.locator('img')).toHaveAttribute('src', /slide-2\.jpg$/);
  await expect(inserted.locator('p')).toHaveText('Mô tả ngắn về điểm tham quan.');

  // Dán link YouTube và link bản đồ thành các đoạn riêng
  await page.evaluate(() => {
    window.tinymce.activeEditor.insertContent('<p>https://www.youtube.com/watch?v=e2eVideo01</p><p>https://www.google.com/maps/embed?pb=!1m18!e2e</p>');
  });
  await page.getByRole('button', { name: 'Lưu' }).click();
  await expect(page.locator('.flash--success')).toBeVisible();

  await page.goto('/travel-info');
  await expect(page.locator('.embed-responsive iframe[src="https://www.youtube-nocookie.com/embed/e2eVideo01"]')).toHaveCount(1);
  await expect(page.locator('.embed-responsive iframe[src^="https://www.google.com/maps/embed"]').first()).toBeAttached();
  await expect(page.locator('.attraction-card').first()).toBeVisible();
});

test('trên điện thoại 360px khung nhúng co giãn và trang không cuộn ngang', async ({ page, viewport }) => {
  test.skip(viewport.width > 600, 'chỉ chạy ở khung điện thoại');
  await page.goto('/travel-info');

  const frames = page.locator('.embed-responsive iframe');
  expect(await frames.count()).toBeGreaterThan(0);
  const width = await frames.first().evaluate((el) => el.getBoundingClientRect().width);
  expect(width).toBeLessThanOrEqual(360);
  expect(width).toBeGreaterThan(250);
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
  expect(overflow).toBeLessThanOrEqual(0);
});

test('trang Contact có liên kết gọi điện và gửi thư đúng giá trị', async ({ page }) => {
  await page.goto('/contact');

  await expect(page.locator('.contact-card a[href="tel:+84912345678"]')).toHaveText('+84 912 345 678');
  await expect(page.locator('.contact-card a[href="mailto:contact1@example.edu.vn"]')).toBeVisible();
});
