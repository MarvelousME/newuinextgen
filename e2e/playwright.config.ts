import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';

const baseURL = process.env.BASE_URL || 'http://localhost:8900';
const isCI = !!process.env.CI;
/** Full multi-browser matrix when E2E_FULL_MATRIX=1 (needs disk + browsers). */
const fullMatrix = process.env.E2E_FULL_MATRIX === '1';
/** Retain media on every test when E2E_EVIDENCE=1 (disk-heavy). */
const fullEvidence = process.env.E2E_EVIDENCE === '1';
/** Video needs Playwright ffmpeg (`npx playwright install ffmpeg`). Opt-in via E2E_VIDEO=1. */
const videoEnabled = process.env.E2E_VIDEO === '1' || fullEvidence;

const evidenceRoot = path.join(__dirname, 'reports', 'evidence');

export default defineConfig({
  testDir: './workflows',
  outputDir: path.join(__dirname, 'test-results'),
  timeout: 120_000,
  expect: { timeout: 15_000 },
  fullyParallel: false,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: [
    ['list'],
    ['html', { outputFolder: 'reports/html', open: 'never' }],
    ['json', { outputFile: 'reports/results.json' }],
    ['junit', { outputFile: 'reports/junit.xml' }],
  ],
  use: {
    baseURL,
    navigationTimeout: 60_000,
    actionTimeout: 30_000,
    trace: fullEvidence ? 'on' : 'retain-on-failure',
    screenshot: fullEvidence ? 'on' : 'only-on-failure',
    video: videoEnabled ? (fullEvidence ? 'on' : 'retain-on-failure') : 'off',
    launchOptions: {
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
    ...(fullMatrix
      ? [
          {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] },
          },
          {
            name: 'webkit',
            use: { ...devices['Desktop Safari'] },
          },
          {
            name: 'tablet',
            use: {
              ...devices['iPad Pro 11'],
              ...(isCI ? {} : { channel: 'chrome' }),
            },
          },
          {
            name: 'mobile',
            use: {
              ...devices['Pixel 7'],
              ...(isCI ? {} : { channel: 'chrome' }),
            },
          },
        ]
      : []),
  ],
  metadata: {
    evidenceRoot,
    baseURL,
    generatedAt: new Date().toISOString(),
  },
});
