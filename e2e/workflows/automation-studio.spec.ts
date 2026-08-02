/**
 * Automation Studio — import all sources + visible CRUD.
 *
 *   cd e2e
 *   $env:BASE_URL='http://localhost:8900'
 *   npx playwright test workflows/automation-studio.spec.ts --headed --workers=1
 */
import { test, expect } from '@playwright/test';
import { wpLogin } from '../helpers';

test.describe('Automation Studio workflows CRUD', () => {
  test.setTimeout(180_000);

  test.beforeEach(async ({ page }) => {
    await wpLogin(page);
  });

  test('imports sources and lists editable workflows', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=ngc-automation-studio', {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });

    await expect(page.getByTestId('ngc-studio-root')).toBeVisible({ timeout: 30_000 });
    await expect(page.getByTestId('ngc-studio-app')).toBeVisible({ timeout: 30_000 });
    await expect(page.getByTestId('ngc-studio-import')).toBeVisible();

    await page.getByTestId('ngc-studio-import').click();
    await expect(page.locator('#ngc-studio-status')).toContainText(/Import done|Synced|workflows loaded/i, {
      timeout: 60_000,
    });

    const list = page.getByTestId('ngc-studio-list');
    await expect(list.locator('.ngc-studio-list-item').first()).toBeVisible({ timeout: 30_000 });
    const count = await list.locator('.ngc-studio-list-item').count();
    expect(count).toBeGreaterThanOrEqual(10);

    await expect(page.getByTestId('ngc-studio-name')).toBeVisible();
    await expect(page.getByTestId('ngc-studio-save')).toBeVisible();
    await expect(page.getByTestId('ngc-studio-delete')).toBeVisible();
    await expect(page.getByTestId('ngc-studio-publish')).toBeVisible();
  });

  test('create, update, delete a custom workflow', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=ngc-automation-studio', {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await expect(page.getByTestId('ngc-studio-create')).toBeVisible({ timeout: 30_000 });

    page.once('dialog', async (dialog) => {
      await dialog.accept('E2E CRUD Workflow');
    });
    await page.getByTestId('ngc-studio-create').click();

    await expect(page.getByTestId('ngc-studio-name')).toHaveValue(/E2E CRUD Workflow/, { timeout: 30_000 });
    await page.getByTestId('ngc-studio-name').fill('E2E CRUD Workflow Updated');
    await page.getByTestId('ngc-studio-save').click();
    await expect(page.locator('#ngc-studio-status')).toContainText(/Saved|workflows loaded/i, { timeout: 30_000 });

    page.once('dialog', async (dialog) => {
      await dialog.accept();
    });
    await page.getByTestId('ngc-studio-delete').click();
    await expect(page.locator('#ngc-studio-status')).toContainText(/Deleted|workflows loaded/i, {
      timeout: 30_000,
    });
  });
});
