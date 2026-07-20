import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.BASE_URL || 'http://127.0.0.1:8900';
const isCI = !!process.env.CI;

export default defineConfig({
  testDir: './workflows',
  timeout: 60_000,
  expect: { timeout: 10_000 },
  fullyParallel: false,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: [
    ['list'],
    ['html', { outputFolder: 'reports/html', open: 'never' }],
    ['json', { outputFile: 'reports/results.json' }],
  ],
  use: {
    baseURL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'off',
  },
  projects: [
    {
      name: isCI ? 'chromium' : 'chrome',
      use: {
        ...devices['Desktop Chrome'],
        ...(isCI ? {} : { channel: 'chrome' }),
      },
    },
  ],
});
