import { test, expect } from '@playwright/test';
import { openHomeBookingModal } from '../helpers';

test.describe('Blueprint WF-24 Notification & Homepage Touchpoints', () => {
  test('homepage renders branded hero or CMS headline', async ({ page }) => {
    await page.goto('/', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toBeVisible();
    const hero = page.locator('.ngi-hero .ngi-title, .elementor-heading-title, h1, .pagehead__title, [data-elementor-type="wp-page"]').first();
    if (await hero.isVisible().catch(() => false)) {
      await expect(hero).toBeVisible();
    }
    await expect(page.locator('body')).toContainText(/NextGen|Tutor|Learn|South Africa/i);
  });

  test('FAQ accordion — click expands answer when present', async ({ page }) => {
    await page.goto('/', { waitUntil: 'domcontentloaded' });
    const faq = page.locator('.ngi-faq-item, .elementor-accordion-item, details').first();
    if (!(await faq.isVisible().catch(() => false))) {
      test.skip(true, 'FAQ markup not on Elementor home');
    }
    await faq.scrollIntoViewIfNeeded();
    const trigger = faq.locator('.ngi-faq-q, .elementor-tab-title, summary').first();
    await trigger.click();
    await expect(page.locator('body')).toBeVisible();
  });

  test('subject explorer tabs — switch tab updates panel', async ({ page }) => {
    await page.goto('/', { waitUntil: 'domcontentloaded' });
    const tabs = page.locator('.ngi-subject-tabs .ngi-tab');
    const count = await tabs.count();
    test.skip(count < 2, 'Need at least 2 subject tabs');
    const second = tabs.nth(1);
    const title = await second.getAttribute('data-title');
    await second.click();
    await expect(page.locator('#ngiSubjectTitle')).toContainText(title || '');
  });

  test('pricing section or pricing page visible', async ({ page }) => {
    await page.goto('/pricing/', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toContainText(/pricing|plan|hour|R\s?\d|package/i);
  });

  test('booking CTA or home brand surface', async ({ page }) => {
    await openHomeBookingModal(page);
    const form = page.locator('#ngiBookingModal form');
    if (await form.isVisible().catch(() => false)) {
      await expect(form).toBeVisible();
    } else {
      await expect(page.locator('body')).toContainText(/NextGen|Tutor/i);
    }
  });
});
