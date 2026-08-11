import { test, expect } from '@playwright/test';

/**
 * Beyond Measure Control Plane — headed smoke (requires WP at baseURL).
 * Run from e2e/ with BEYOND_MEASURE=1 when Docker WP is up.
 */
test.describe('Beyond Measure Control Plane', () => {
  test.skip(!process.env.BEYOND_MEASURE, 'Set BEYOND_MEASURE=1 with Docker WP');

  test('Command Center and Talent explain', async ({ page }) => {
    const user = process.env.WP_ADMIN_USER || 'admin';
    const pass = process.env.WP_ADMIN_PASSWORD || 'NextGenAdmin!2026';
    const base = process.env.WP_URL || 'http://localhost:8890';

    await page.goto(`${base}/wp-login.php`);
    await page.fill('#user_login', user);
    await page.fill('#user_pass', pass);
    await page.click('#wp-submit');
    await page.goto(`${base}/wp-admin/admin.php?page=ngtbm-beyond-measure`);
    await expect(page.locator('#ngtbm-root')).toBeVisible({ timeout: 30000 });
    await page.waitForTimeout(1500);
    await expect(page.getByText(/Command Center|Beyond Measure|Control Plane/i).first()).toBeVisible();
    await page.evaluate(() => {
      window.location.hash = '/tutors/talent';
    });
    await page.waitForTimeout(1000);
    await expect(page.getByText(/Talent Intelligence/i).first()).toBeVisible();
    const explain = page.getByRole('button', { name: /Explain/i }).first();
    if (await explain.count()) {
      await explain.click();
      await expect(page.getByText(/HUMAN REVIEW|Suitability|Overall/i).first()).toBeVisible();
    }
    await page.evaluate(() => {
      window.location.hash = '/security/access-matrix';
    });
    await page.waitForTimeout(800);
    await expect(page.getByText(/Access Matrix/i).first()).toBeVisible();
  });
});
