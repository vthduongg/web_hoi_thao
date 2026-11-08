// Test trình duyệt cho menu-mobile.js, slider.js, copy-link.js (US1).
// Dữ liệu từ `php bin/seed.php --test --reset`: menu có nhóm "Conference", trang chủ có 3 slide.
const { test, expect } = require('@playwright/test');

test.describe('Menu trên điện thoại', () => {
  test.skip(({ viewport }) => viewport.width > 600, 'chỉ chạy ở khung điện thoại');

  test('nút Menu mở và đóng menu', async ({ page }) => {
    await page.goto('/call-for-papers');
    const toggle = page.locator('[data-menu-toggle]');
    const menu = page.locator('[data-main-menu]');

    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(menu).toBeHidden();

    await toggle.click();
    await expect(toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(menu).toBeVisible();

    await toggle.click();
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(menu).toBeHidden();
  });

  test('không bị cuộn ngang ở chiều rộng 360px', async ({ page }) => {
    for (const path of ['/', '/important-dates', '/call-for-papers']) {
      await page.goto(path);
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
      expect(overflow, path).toBeLessThanOrEqual(0);
    }
  });

  test('menu con mở bằng chạm và Esc đóng lại', async ({ page }) => {
    await page.goto('/');
    await page.locator('[data-menu-toggle]').click();
    const parent = page.locator('[data-submenu-toggle]', { hasText: 'Conference' });
    await parent.click();
    await expect(parent).toHaveAttribute('aria-expanded', 'true');
    await expect(page.getByRole('link', { name: 'Important Dates' }).first()).toBeVisible();

    await page.keyboard.press('Escape');
    await expect(parent).toHaveAttribute('aria-expanded', 'false');
    await expect(parent).toBeFocused();
  });
});

test.describe('Menu trên máy tính', () => {
  test.skip(({ viewport }) => viewport.width < 900, 'chỉ chạy ở khung máy tính');

  test('Tab vào mục cha mở menu con, Esc đóng và trả focus về mục cha', async ({ page }) => {
    await page.goto('/');
    const parent = page.locator('[data-submenu-toggle]', { hasText: 'Conference' });
    const child = page.locator('#' + (await parent.getAttribute('aria-controls'))).getByRole('link', { name: 'Call for Papers' });

    await parent.focus();
    await expect(parent).toHaveAttribute('aria-expanded', 'true');
    await expect(child).toBeVisible();

    await page.keyboard.press('Tab');
    await expect(child).toBeFocused();

    await page.keyboard.press('Escape');
    await expect(parent).toBeFocused();
    await expect(parent).toHaveAttribute('aria-expanded', 'false');
    await expect(child).toBeHidden();
  });

  test('Enter và Space bật/tắt menu con', async ({ page }) => {
    await page.goto('/');
    const parent = page.locator('[data-submenu-toggle]', { hasText: 'Conference' });
    await parent.focus();
    await page.keyboard.press('Escape');
    await expect(parent).toHaveAttribute('aria-expanded', 'false');

    await page.keyboard.press('Enter');
    await expect(parent).toHaveAttribute('aria-expanded', 'true');
    await page.keyboard.press('Space');
    await expect(parent).toHaveAttribute('aria-expanded', 'false');
  });
});

test.describe('Slider trang chủ', () => {
  test('tự chuyển ảnh và nút dừng làm ngưng chuyển', async ({ page }) => {
    await page.goto('/');
    const active = page.locator('[data-slide].is-active');
    const first = await active.getAttribute('data-index');

    await expect(active).not.toHaveAttribute('data-index', first, { timeout: 8000 });

    await page.locator('[data-slider-toggle]').click();
    const paused = await active.getAttribute('data-index');
    await page.waitForTimeout(6500);
    await expect(active).toHaveAttribute('data-index', paused);
  });

  test('không tự chuyển khi người dùng giảm chuyển động', async ({ browser }) => {
    const context = await browser.newContext({ reducedMotion: 'reduce' });
    const page = await context.newPage();
    await page.goto('http://127.0.0.1:8123/');
    const active = page.locator('[data-slide].is-active');
    const first = await active.getAttribute('data-index');

    await page.waitForTimeout(6500);
    await expect(active).toHaveAttribute('data-index', first);
    await context.close();
  });
});

test('Copy link ghi URL trang vào clipboard', async ({ page, context, baseURL }) => {
  await context.grantPermissions(['clipboard-read', 'clipboard-write'], { origin: baseURL });
  await page.goto('/call-for-papers');

  await page.locator('[data-copy-link]').click();

  await expect(page.locator('[data-copy-status]')).toHaveText('Link copied');
  const copied = await page.evaluate(() => navigator.clipboard.readText());
  expect(copied).toBe(`${baseURL}/call-for-papers`);
});
