// Cấu hình test trình duyệt (constitution, Nguyên tắc II: mọi chức năng JavaScript có test tự động).
// Playwright tự chuẩn bị CSDL test, nạp dữ liệu mẫu rồi bật server PHP tích hợp.
const { defineConfig, devices } = require('@playwright/test');

const port = 8123;
const php = process.env.PHP_BIN || 'php';

module.exports = defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false, // dùng chung một CSDL test nên chạy tuần tự
  workers: 1,
  retries: 0,
  reporter: [['list']],
  use: {
    baseURL: `http://127.0.0.1:${port}`,
    trace: 'retain-on-failure',
  },
  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 800 } } },
    { name: 'mobile', use: { ...devices['Desktop Chrome'], viewport: { width: 360, height: 740 }, hasTouch: true } },
  ],
  webServer: {
    command: `${php} bin/migrate.php --test && ${php} bin/seed.php --test --reset && ${php} -S 127.0.0.1:${port} -t public public/index.php`,
    url: `http://127.0.0.1:${port}/`,
    env: { APP_ENV: 'testing', APP_USE_TEST_DB: '1' },
    reuseExistingServer: false,
    timeout: 120000,
  },
});
