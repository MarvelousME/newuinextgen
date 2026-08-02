/**
 * Mission Control — headed smoke for master panel configure / overrides / control map.
 *
 *   cd e2e
 *   $env:BASE_URL='http://localhost:8900'
 *   npm run test:mission-control-headed
 */
import { test, expect } from '@playwright/test';
import { wpLogin } from '../helpers';

test.describe('Mission Control master panel', () => {
  test.setTimeout(180_000);

  test('status tab shows orchestrator shell and plugin matrix', async ({ page }) => {
    await wpLogin(page);
    await page.goto('/wp-admin/admin.php?page=ngtmc-mission-control', {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await expect(page.getByTestId('ngtmc-mission-control')).toBeVisible({ timeout: 30_000 });
    await expect(page.getByTestId('ngtmc-orchestrator-state')).toBeVisible();
    await expect(page.getByTestId('ngtmc-status')).toBeVisible();
    await expect(page.getByTestId('ngtmc-plugin-matrix')).toBeVisible();
    await expect(page.getByTestId('ngtmc-companion-version')).toBeVisible();
  });

  test('configure tab can run identity configure', async ({ page }) => {
    await wpLogin(page);
    await page.goto('/wp-admin/admin.php?page=ngtmc-mission-control&tab=configure', {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await expect(page.getByTestId('ngtmc-configure')).toBeVisible({ timeout: 30_000 });
    await page.getByTestId('ngtmc-op-configure').click();
    await page.waitForURL(/ngtmc-mission-control/, { timeout: 180_000 });
    await expect(page.getByTestId('ngtmc-flash')).toContainText(/configure/i, { timeout: 60_000 });
  });

  test('overrides tab saves maintenance + contact force fields', async ({ page }) => {
    await wpLogin(page);
    await page.goto('/wp-admin/admin.php?page=ngtmc-mission-control&tab=overrides', {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await expect(page.getByTestId('ngtmc-overrides')).toBeVisible({ timeout: 30_000 });

    // Ensure maintenance is off after test.
    const maint = page.getByTestId('ngtmc-override-maintenance');
    if (await maint.isChecked()) {
      await maint.uncheck();
    }
    await page.getByTestId('ngtmc-override-email').fill('support@nextgentutors.co.za');
    await page.getByTestId('ngtmc-override-phone').fill('0813340625');
    await page.getByTestId('ngtmc-overrides-save').click();
    await page.waitForURL(/tab=overrides/, { timeout: 60_000 });
    await expect(page.getByTestId('ngtmc-flash')).toContainText(/overrides/i);
    await expect(page.getByTestId('ngtmc-override-email')).toHaveValue('support@nextgentutors.co.za');
  });

  test('control map links to specialist consoles', async ({ page }) => {
    await wpLogin(page);
    await page.goto('/wp-admin/admin.php?page=ngtmc-mission-control&tab=map', {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await expect(page.getByTestId('ngtmc-control-map')).toBeVisible({ timeout: 30_000 });
    await expect(page.getByTestId('ngtmc-link-business')).toBeVisible();
    await expect(page.getByTestId('ngtmc-link-demo')).toBeVisible();
    await expect(page.getByTestId('ngtmc-link-ngcpm')).toBeVisible();
    await expect(page.getByTestId('ngtmc-link-hub')).toBeVisible();
  });

  test('intelligence tab loads live dashboard shell', async ({ page }) => {
    await wpLogin(page);
    await page.goto('/wp-admin/admin.php?page=ngtmc-mission-control&tab=intelligence', {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await expect(page.getByTestId('ngtmc-tab-intelligence')).toBeVisible({ timeout: 30_000 });
    await expect(page.getByTestId('ngtmc-intelligence')).toBeVisible();
    await expect(page.getByTestId('ngtmc-intel-live')).toBeVisible();
    await expect(page.getByTestId('ngtmc-intel-widgets')).toBeVisible();
    const kpis = page.getByTestId('ngtmc-intel-kpis');
    if (await kpis.isVisible()) {
      await expect(kpis).toBeVisible();
      await page.waitForFunction(
        () => document.querySelector('#ngtmc-intel-kpis')?.children.length > 0,
        { timeout: 30_000 },
      );
    }
    await expect(page.getByTestId('ngtmc-chart-sankey')).toBeVisible();
  });
});
