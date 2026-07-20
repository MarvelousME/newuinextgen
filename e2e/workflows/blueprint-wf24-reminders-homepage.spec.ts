import { test, expect } from '@playwright/test';
import { openHomeBookingModal } from '../helpers';

test.describe('Blueprint WF-24 Notification & Homepage Touchpoints', () => {
  test('kinetic hero renders CMS-driven headline', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('.ngi-hero .ngi-title')).toBeVisible();
    await expect(page.locator('.ngi-hero .ngi-btn-primary').first()).toBeVisible();
  });

  test('FAQ accordion — click expands answer', async ({ page }) => {
    await page.goto('/');
    const faq = page.locator('.ngi-faq-item').first();
    await faq.scrollIntoViewIfNeeded();
    await faq.locator('.ngi-faq-q').click();
    await expect(faq.locator('.ngi-faq-a')).toBeVisible();
  });

  test('subject explorer tabs — switch tab updates panel', async ({ page }) => {
    await page.goto('/');
    const tabs = page.locator('.ngi-subject-tabs .ngi-tab');
    const count = await tabs.count();
    test.skip(count < 2, 'Need at least 2 subject tabs');
    const second = tabs.nth(1);
    const title = await second.getAttribute('data-title');
    await second.click();
    await expect(page.locator('#ngiSubjectTitle')).toContainText(title || '');
  });

  test('pricing section visible with plan cards', async ({ page }) => {
    await page.goto('/#pricing');
    await expect(page.locator('#pricing .ngi-pricing-grid .ngi-price').first()).toBeVisible();
  });

  test('booking modal contains form or fallback fields', async ({ page }) => {
    await openHomeBookingModal(page);
    const form = page.locator('#ngiBookingModal form');
    await expect(form).toBeVisible();
  });
});
