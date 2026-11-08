// Test trình duyệt cho editor.js (TinyMCE) và media-picker.js (hộp chọn tệp) — US2.
// Dữ liệu seed --test có: trang paper-submission, ảnh "no-alt.jpg" (chưa có mô tả), tài liệu "Guidelines.pdf".
const { test, expect } = require('@playwright/test');
const { loginAs } = require('./helpers');

test.skip(({ viewport }) => viewport.width < 900, 'trang quản trị kiểm tra ở khung máy tính');

async function pickFromLibrary(page, search, itemName) {
  const [popup] = await Promise.all([
    page.waitForEvent('popup'),
    page.getByRole('button', { name: 'Chèn từ thư viện' }).click(),
  ]);
  await popup.getByLabel('Tìm theo tên tệp').fill(search);
  await popup.getByRole('button', { name: 'Tìm' }).click();
  // Cửa sổ tự đóng ngay khi bấm "Chọn" nên phải bắt đầu chờ sự kiện đóng trước khi bấm
  const closed = popup.waitForEvent('close');
  await popup.locator('[data-media-item]', { hasText: itemName }).getByRole('button', { name: 'Chọn' }).click();
  await closed;
}

test('soạn trang bằng trình soạn thảo, chèn ảnh và tài liệu từ thư viện rồi xuất bản', async ({ page }) => {
  await loginAs(page, 'editor');
  await page.goto('/admin/pages');
  await page.getByRole('link', { name: 'Paper Submission' }).click();

  // TinyMCE tải xong, giao diện tiếng Việt
  await expect(page.locator('.tox-tinymce')).toBeVisible();
  await expect(page.locator('button[aria-label="In đậm"]')).toBeVisible();

  // Gõ một đoạn văn mới ở cuối nội dung
  const body = page.frameLocator('iframe.tox-edit-area__iframe').locator('body');
  await body.click();
  await page.keyboard.press('Control+End');
  await page.keyboard.press('Enter');
  await page.keyboard.type('Đoạn văn kiểm thử E2E');

  // Chèn bảng 2x2
  await page.evaluate(() => window.tinymce.activeEditor.execCommand('mceInsertTable', false, { rows: 2, columns: 2 }));

  // Chèn ảnh (chưa có mô tả) và liên kết tới PDF từ thư viện
  await pickFromLibrary(page, 'no-alt', 'no-alt.jpg');
  await pickFromLibrary(page, 'Guidelines', 'Guidelines.pdf');

  // Lưu với trạng thái "Xuất bản": bị chặn vì ảnh thiếu mô tả
  await page.getByLabel('Trạng thái').selectOption('published');
  await page.getByRole('button', { name: 'Lưu' }).click();
  await expect(page.locator('[data-alt-warning]')).toContainText('chưa có mô tả');
  await expect(page).toHaveURL(/\/admin\/pages\/\d+$/);

  // Người dùng thêm mô tả cho ảnh rồi lưu lại
  await page.evaluate(() => {
    const editor = window.tinymce.activeEditor;
    editor.dom.select('img').forEach((img) => editor.dom.setAttrib(img, 'alt', 'Sơ đồ nộp bài'));
  });
  await page.getByRole('button', { name: 'Lưu' }).click();
  await expect(page.locator('.flash--success')).toBeVisible();

  // Trang công khai hiển thị nội dung mới
  await page.goto('/paper-submission');
  await expect(page.getByText('Đoạn văn kiểm thử E2E')).toBeVisible();
  await expect(page.locator('.page-content table').first()).toBeVisible();
  await expect(page.locator('.page-content img[alt="Sơ đồ nộp bài"]')).toBeVisible();
  await expect(page.locator('.page-content a', { hasText: 'Guidelines.pdf' })).toHaveAttribute('href', /\/uploads\/.+\.pdf$/);
});

test('ô ảnh đại diện chọn ảnh từ thư viện', async ({ page }) => {
  await loginAs(page, 'editor');
  await page.goto('/admin/pages');
  await page.getByRole('link', { name: 'Call for Papers' }).click();

  const [popup] = await Promise.all([
    page.waitForEvent('popup'),
    page.locator('[data-media-field="featured_media_id"] button', { hasText: 'Chọn ảnh' }).click(),
  ]);
  const closed = popup.waitForEvent('close');
  await popup.locator('[data-media-item]', { hasText: 'no-alt.jpg' }).getByRole('button', { name: 'Chọn' }).click();
  await closed;

  await expect(page.locator('input[name="featured_media_id"]')).not.toHaveValue('');
  await expect(page.locator('[data-media-field="featured_media_id"] img')).toBeVisible();
});
