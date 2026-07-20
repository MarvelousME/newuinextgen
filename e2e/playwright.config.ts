import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.BASE_URL || 'http://localhost:8900';
const isCI = !!process.env.CI;

export default defineConfig({
  testDir: './workflows',
  timeout: 90_000,
  expect: { timeout: 15_000 },
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
    navigationTimeout: 60_000,
    actionTimeout: 30_000,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'off',
    launchOptions: {
      // Full-suite headed runs are more stable without per-test slowMo.
      slowMo: process.env.PW_SLOW_MO ? Number(process.env.PW_SLOW_MO) : 0,
    },
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
