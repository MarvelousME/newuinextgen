import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

const CC_URL = process.env.ECOSYSTEM_CC_URL || 'http://localhost:8790';
const evidenceDir = path.join(__dirname, '..', 'reports', 'evidence', 'economic-control-center');
fs.mkdirSync(evidenceDir, { recursive: true });

const viewports = [
  { name: '1800x1120', width: 1800, height: 1120 },
  { name: '1440x900', width: 1440, height: 900 },
  { name: '1366x768', width: 1366, height: 768 },
  { name: '1024x768', width: 1024, height: 768 },
  { name: '768x1024', width: 768, height: 1024 },
  { name: '390x844', width: 390, height: 844 },
];

const screens = ['overview', 'treasury', 'ledger', 'wallets', 'payments', 'invoices', 'reports', 'settings'];

test.describe('Economic Control Center visual', () => {
  test.beforeAll(async () => {
    const res = await fetch(`${CC_URL}/health`).catch(() => null);
    if (!res?.ok) {
      test.skip(true, `Control Center not reachable at ${CC_URL}`);
    }
  });

  for (const vp of viewports) {
    test(`overview ${vp.name}`, async ({ page }) => {
      await page.addInitScript((token) => {
        sessionStorage.setItem('ecosystem_api_token', token);
      }, process.env.ECOSYSTEM_API_TOKEN || 'local-dev-ecosystem-token-2026');
      await page.setViewportSize({ width: vp.width, height: vp.height });
      await page.goto(`${CC_URL}/#/economic`, { waitUntil: 'networkidle' });
      await expect(page.locator('.cc-header__title')).toHaveText('ECONOMIC CONTROL CENTER');
      await expect(page.locator('#ecc-page')).toBeVisible();
      await expect(page.locator('#cc-nav')).toBeVisible();
      await expect(page.locator('#cc-nav .cc-nav__btn')).toHaveCount(42);
      await page.screenshot({
        path: path.join(evidenceDir, `economic-overview-${vp.name}.png`),
        fullPage: vp.height >= 900,
      });
    });
  }

  for (const screen of screens.filter((s) => s !== 'overview')) {
    test(`screen ${screen} 1440x900`, async ({ page }) => {
      await page.addInitScript((token) => {
        sessionStorage.setItem('ecosystem_api_token', token);
      }, process.env.ECOSYSTEM_API_TOKEN || 'local-dev-ecosystem-token-2026');
      await page.setViewportSize({ width: 1440, height: 900 });
      await page.goto(`${CC_URL}/#/economic/${screen}`, { waitUntil: 'networkidle' });
      await expect(page.locator('.cc-header__title')).toHaveText('ECONOMIC CONTROL CENTER');
      await expect(page.locator('#ecc-page')).toBeVisible();
      await page.screenshot({
        path: path.join(evidenceDir, `economic-${screen}-1440x900.png`),
      });
    });
  }
});
