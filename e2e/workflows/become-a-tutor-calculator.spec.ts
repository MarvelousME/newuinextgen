import { test, expect } from '@playwright/test';
import { dismissCookieOrOverlays } from '../helpers';

test.describe('Become a Tutor — income calculator', () => {
  test('renders shortcode calculator and recalculates on input', async ({ page }) => {
    await page.goto('/become-a-tutor/');
    await dismissCookieOrOverlays(page);

    const calc = page.locator('[data-ngt-income-calculator]').first();
    await expect(calc).toBeVisible({ timeout: 15_000 });

    const monthly = calc.locator('[data-ngt-ic-monthly]');
    const before = (await monthly.textContent())?.trim() ?? '';

    const rate = calc.locator('[data-ngt-ic-rate]');
    await rate.fill('400');
    await rate.dispatchEvent('input');

    await expect(monthly).not.toHaveText(before, { timeout: 5_000 });

    const hours = calc.locator('[data-ngt-ic-hours]');
    await hours.fill('20');
    await hours.dispatchEvent('input');

    const after = (await monthly.textContent())?.trim() ?? '';
    expect(after).toMatch(/^R\s[\d,]+$/);
  });
});
