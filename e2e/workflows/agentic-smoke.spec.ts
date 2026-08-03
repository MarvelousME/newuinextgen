/**
 * Headed smoke: agentic admin screens + education routes.
 * Run from e2e/: BASE_URL=http://localhost:8890 npx playwright test workflows/agentic-smoke.spec.ts --headed --workers=1
 */
import { test, expect } from '@playwright/test';

const ADMIN_USER = process.env.WP_ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASSWORD || 'NextGenAdmin!2026';

async function wpLogin(page: import('@playwright/test').Page) {
  await page.goto('/wp-login.php', { waitUntil: 'domcontentloaded' });
  await page.fill('#user_login', ADMIN_USER);
  await page.fill('#user_pass', ADMIN_PASS);
  await page.click('#wp-submit');
  await page.waitForURL(/wp-admin/, { timeout: 60_000 });
}

const PAGES = [
  'ngc-agentic-hub',
  'ngc-mcp-servers',
  'ngc-a2a-agents',
  'ngc-social-connections',
  'ngc-content-studio',
  'ngc-content-calendar',
  'ngc-tutor-leads',
  'ngc-lead-sources',
  'ngt-edu-students',
  'ngt-edu-parents',
  'ngt-edu-subjects',
];

test.describe('Agentic + education admin smoke', () => {
  test('admin pages render without fatal / placeholder shell', async ({ page }) => {
    test.setTimeout(600_000);
    await wpLogin(page);
    const results: Array<{ page: string; status: string }> = [];
    for (const slug of PAGES) {
      const res = await page.goto(`/wp-admin/admin.php?page=${slug}`, { waitUntil: 'domcontentloaded', timeout: 90_000 });
      const status = res?.status() ?? 0;
      const body = await page.locator('body').innerText();
      expect(status, slug).toBeLessThan(500);
      expect(body.toLowerCase()).not.toContain('fatal error');
      expect(body.toLowerCase()).not.toContain('placeholder — no data operations yet');
      results.push({ page: slug, status: String(status) });
    }
    // eslint-disable-next-line no-console
    console.log('AGENTIC_SMOKE', JSON.stringify(results));
  });
});
