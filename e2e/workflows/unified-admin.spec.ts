/**
 * Unified NEXT GEN TUTORS admin shell — headed e2e against Docker.
 *
 *   cd e2e
 *   $env:BASE_URL='http://localhost:8900'
 *   npx playwright test workflows/unified-admin.spec.ts --headed --workers=1
 */
import { test, expect } from '@playwright/test';
import { wpLogin } from '../helpers';

test.describe('Unified NEXT GEN TUTORS admin', () => {
  test.setTimeout(180_000);

  test.beforeEach(async ({ page }) => {
    await wpLogin(page);
  });

  test('sidebar has single NEXT GEN TUTORS parent and no legacy top-levels', async ({ page }) => {
    await page.goto('/wp-admin/', { waitUntil: 'domcontentloaded', timeout: 120_000 });

    const ngt = page.locator('#adminmenu a.menu-top').filter({ hasText: /NEXT GEN TUTORS/i });
    await expect(ngt.first()).toBeVisible({ timeout: 30_000 });

    // Legacy top-level menus must not appear.
    for (const label of [
      'Mission Control',
      'Platform',
      'Workflows',
      'Automation Studio',
      'NextGen Hub',
      'NextGenTutors Plugins',
    ]) {
      const top = page.locator('#adminmenu > li.menu-top > a.menu-top').filter({ hasText: new RegExp(`^\\s*${label}\\s*$`, 'i') });
      await expect(top).toHaveCount(0);
    }
  });

  test('Mission Control opens under unified shell with breadcrumbs + search', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=ngt-admin', {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await expect(page.locator('.ngt-admin-chrome')).toBeVisible({ timeout: 30_000 });
    await expect(page.getByTestId('ngt-admin-search')).toBeVisible();
    await expect(page.getByTestId('ngtmc-mission-control')).toBeVisible({ timeout: 30_000 });
  });

  test('admin search finds Mission Control', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=ngt-admin', {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    const search = page.getByTestId('ngt-admin-search');
    await expect(search).toBeVisible({ timeout: 30_000 });
    await search.fill('mission');
    await expect(page.locator('#ngt-admin-search-results a').first()).toBeVisible({ timeout: 15_000 });
  });

  test('legacy page slugs still resolve under shell', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=ngc-matches', {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await expect(page.locator('.ngt-admin-chrome')).toBeVisible({ timeout: 30_000 });
    await expect(page.locator('body')).toContainText(/match/i);
  });
});
