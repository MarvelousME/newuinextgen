/**
 * Header/footer migration assertions — reference nav verbatim, no Get Started.
 */
import { test, expect } from '@playwright/test';

const BASE = process.env.BASE_URL || 'http://localhost:8890';

const EXPECTED_TOP = [
  'Find a Tutor',
  'Pricing',
  'Become a Tutor',
  'About',
  'Contact',
  'Compliance',
  'Blog',
];

const EXPECTED_COMPLIANCE = [
  'Safety Guide',
  'Terms & Conditions',
  'Privacy Policy',
  'Tutor Vetting',
  '1st Lesson Guarantee',
];

test.describe('Reference theme header/footer migration', () => {
  test('desktop primary menu order and no Get Started', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });

    const nav = page.locator('nav.ngt-nav, .ngt-nav').first();
    await expect(nav).toBeVisible();

    const topLabels = await nav.locator('.ngt-nav__menu > li > a.ngt-nav__link, .ngt-nav__menu > li > .ngt-nav__dropdown-trigger, .ngt-nav__menu > .menu-item > a, .ngt-nav__menu > .menu-item > button').allTextContents();
    const cleaned = topLabels.map((t) => t.replace(/\s+/g, ' ').trim()).filter(Boolean);

    for (const label of EXPECTED_TOP) {
      expect(cleaned.some((t) => t.includes(label)), `missing top nav: ${label}`).toBeTruthy();
    }

    // Order check among discovered labels that match expected
    const orderIdx = EXPECTED_TOP.map((label) =>
      cleaned.findIndex((t) => t.includes(label))
    ).filter((i) => i >= 0);
    for (let i = 1; i < orderIdx.length; i++) {
      expect(orderIdx[i], `order broken near ${EXPECTED_TOP[i]}`).toBeGreaterThan(orderIdx[i - 1]);
    }

    await expect(page.getByRole('link', { name: /^Get Started$/i })).toHaveCount(0);
    await expect(page.getByRole('button', { name: /^Get Started$/i })).toHaveCount(0);
    await expect(page.getByText(/Get Started Today/i)).toHaveCount(0);

    await expect(page.getByTestId('ngt-nav-signin')).toBeAttached();
    await expect(page.getByTestId('ngt-nav-signin')).toBeVisible();
  });

  test('compliance submenu labels present', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });

    const compliance = page.locator('.ngt-nav__menu .menu-item-has-children').filter({ hasText: 'Compliance' }).first();
    await compliance.hover();
    await compliance.locator('> a, > button').first().click({ force: true });

    for (const label of EXPECTED_COMPLIANCE) {
      await expect(compliance.getByRole('link', { name: label }).first()).toBeVisible({ timeout: 5000 });
    }
  });

  test('footer Explore / Company / Get In Touch migrated', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });

    const footer = page.locator('footer.ngt-footer');
    await expect(footer).toBeVisible();
    await expect(footer.getByRole('heading', { name: 'Explore' })).toBeVisible();
    await expect(footer.getByRole('heading', { name: 'Company' })).toBeVisible();
    await expect(footer.getByRole('heading', { name: 'Get In Touch' })).toBeVisible();
    await expect(footer.getByRole('link', { name: 'POPIA Compliance' })).toBeVisible();
  });

  test('mobile: no Get Started', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
    const toggle = page.locator('.ngt-nav__toggle').first();
    if (await toggle.isVisible()) {
      await toggle.click();
    }
    await expect(page.getByText(/Get Started/i)).toHaveCount(0);
  });
});
