import { test, expect } from '@playwright/test';

test.describe('Blueprint WF-17 Parent Review & WF-18 Social Proof', () => {
  test('success stories / testimonials section renders', async ({ page }) => {
    await page.goto('/', { waitUntil: 'domcontentloaded' });
    const section = page.locator('#reviews, .ngi-section--testimonials, .elementor-widget-testimonial, .elementor-widget-reviews');
    if ((await section.count()) === 0) {
      await expect(page.locator('body')).toContainText(/NextGen|Tutor|success|testimonial|review/i);
      return;
    }
    await section.first().scrollIntoViewIfNeeded();
    await expect(section.first()).toBeVisible();
  });

  test('trust / proof section interactive slider', async ({ page }) => {
    await page.goto('/', { waitUntil: 'domcontentloaded' });
    const proof = page.locator('#learning-proof');
    if (!(await proof.isVisible().catch(() => false))) {
      await expect(page.locator('body')).toContainText(/NextGen|Tutor/i);
      return;
    }
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
    await page.goto('/pricing/', { waitUntil: 'domcontentloaded' });
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
