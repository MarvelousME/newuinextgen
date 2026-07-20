import { test, expect } from '@playwright/test';

test.describe('Blueprint WF-17 Parent Review & WF-18 Social Proof', () => {
  test('success stories / testimonials section renders', async ({ page }) => {
    await page.goto('/');
    const section = page.locator('#reviews, .ngi-section--testimonials');
    await section.scrollIntoViewIfNeeded();
    await expect(section).toBeVisible();
    await expect(section.locator('.ngi-heading, h2')).toContainText(/success|stories|achievement|testimonial/i);

    const cards = section.locator('.ngi-testimonial');
    if ((await cards.count()) > 0) {
      await expect(cards.first()).toBeVisible();
    } else {
      await expect(section.getByRole('link', { name: /find a tutor/i })).toBeVisible();
    }
  });

  test('trust / proof section interactive slider', async ({ page }) => {
    await page.goto('/');
    const proof = page.locator('#learning-proof');
    await proof.scrollIntoViewIfNeeded();
    const range = proof.locator('#ngiBaRange');
    if (await range.isVisible().catch(() => false)) {
      await range.fill('80');
      await expect(range).toHaveValue('80');
    }
  });
});

test.describe('Blueprint WF-16 Payouts & Pricing Surface', () => {
  test('pricing page loads plan information', async ({ page }) => {
    await page.goto('/pricing/');
    await expect(page.locator('body')).toContainText(/pricing|plan|hour/i);
  });

  test('REST ngt/v1 sections alias returns homepage CMS', async ({ request }) => {
    const res = await request.get('/wp-json/ngt/v1/sections/home');
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body.hero).toBeDefined();
    expect(body.pricing).toBeDefined();
  });
});
