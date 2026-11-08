// `test` dùng chung cho mọi file test Playwright: giống @playwright/test, thêm một kiểm tra tự động —
// test FAIL nếu trang của website (không tính iframe YouTube/Google Maps) báo vi phạm Content-Security-Policy
// trong console. Nhờ vậy khi chạy lại toàn bộ test là biết CSP có chặn nhầm trình soạn thảo, slider... hay không.

const base = require('@playwright/test');

const test = base.test.extend({
  cspGuard: [
    async ({ page, baseURL }, use) => {
      const violations = [];
      page.on('console', (message) => {
        const fromOurSite = (message.location().url || page.url()).startsWith(baseURL);
        if (fromOurSite && message.text().includes('Content Security Policy')) {
          violations.push(`${message.location().url}: ${message.text()}`);
        }
      });
      await use();
      base.expect(violations, 'Trang bị CSP chặn tài nguyên').toEqual([]);
    },
    { auto: true },
  ],
});

module.exports = { test, expect: base.expect };
