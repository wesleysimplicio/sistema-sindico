// @ts-check
const { defineConfig, devices } = require('@playwright/test');

const baseURL = process.env.BASE_URL || 'http://localhost:8000';

module.exports = defineConfig({
  testDir: './specs',
  timeout: 30_000,
  expect: { timeout: 5_000 },
  retries: process.env.CI ? 1 : 0,
  workers: process.env.CI ? 2 : undefined,
  fullyParallel: false,
  reporter: [
    ['list'],
    ['html', { outputFolder: '../../playwright-report', open: 'never' }],
    ['junit', { outputFile: '../../playwright-report/junit.xml' }],
  ],
  use: {
    baseURL,
    headless: true,
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'desktop-chromium',
      use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 800 } },
    },
    {
      name: 'mobile-chromium',
      use: { ...devices['Pixel 5'] },
    },
  ],
});
