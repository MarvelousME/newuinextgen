import { test, expect } from '@playwright/test';
import { dismissCookieOrOverlays } from '../helpers';

test.describe('NGT UI Demo page', () => {
  test('renders catalog components with data-ngt-ui markers', async ({ page }) => {
    await page.goto('/ngt-ui-demo/');
    await dismissCookieOrOverlays(page);

    await expect(page.locator('[data-ngt-ui="magic-card"]').first()).toBeVisible({ timeout: 15_000 });
    await expect(page.locator('[data-ngt-ui="aurora-text"]').first()).toBeVisible();
    await expect(page.locator('[data-ngt-ui="bento-grid"]').first()).toBeVisible();

    const components = page.locator('[data-ngt-ui]');
    const count = await components.count();
    expect(count).toBeGreaterThanOrEqual(5);
  });

  test('interactive globe canvas is present', async ({ page }) => {
    await page.goto('/ngt-ui-demo/');
    await dismissCookieOrOverlays(page);

    const globe = page.locator('[data-ngt-ui="globe"] [data-ngt-canvas="globe"]').first();
    await globe.scrollIntoViewIfNeeded();
    await expect(globe).toBeVisible({ timeout: 10_000 });
  });
});
