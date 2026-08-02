/**
 * Homepage display — kinetic marketing home must render real content, not an empty shell.
 */
import { test, expect } from '@playwright/test';
import { dismissCookieOrOverlays, gotoReady } from '../helpers';

test.describe('Homepage display', () => {
  test('kinetic home shows hero, primary CTA, and landmark main', async ({ page }) => {
    await gotoReady(page, '/');
    await dismissCookieOrOverlays(page);

    await expect(page).toHaveTitle(/NextGen/i);
    await expect(page.locator('body')).toHaveClass(/bi-kinetic-home|beyondinfinity|nextgentutors/i);

    const main = page.locator('main#primary, main.site-main, .bi-theme-main, .bi-theme-content').first();
    await expect(main).toBeVisible({ timeout: 30_000 });

    const heroTitle = page.locator('h1.ngi-title, .ngi-hero h1, .bi-theme-content h1').first();
    await expect(heroTitle).toBeVisible({ timeout: 30_000 });
    await expect(heroTitle).toContainText(/Tutor|NextGen|Pace|Learn/i);

    const findCta = page
      .locator(
        'a.ngt-btn--primary[href*="find-a-tutor"], .ngi-hero a[href*="find-a-tutor"], a[data-ngi-open], a.ngi-btn'
      )
      .first();
    await expect(findCta).toBeVisible({ timeout: 20_000 });
  });

  test('homepage is not an empty header/footer shell', async ({ page }) => {
    await gotoReady(page, '/');
    const sections = page.locator(
      'main section, .ngi-hero, .ngi-section, .bi-theme-content section, [data-bi-motion]'
    );
    await expect(sections.first()).toBeVisible({ timeout: 30_000 });
    expect(await sections.count()).toBeGreaterThanOrEqual(2);
  });
});
