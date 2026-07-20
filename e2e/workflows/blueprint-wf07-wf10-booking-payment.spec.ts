import { test, expect } from '@playwright/test';
import { testEmail, fillNgForm, submitNgForm, expectFormSubmitted, primaryNgForm, dismissCookieOrOverlays } from '../helpers';

test.describe('Blueprint WF-07 Tutor Matching', () => {
  test('find-a-tutor form — parent assessment request', async ({ page }) => {
    await page.goto('/find-a-tutor/');
    const form = primaryNgForm(page, 'find_tutor');
    await expect(form).toBeVisible();

    await fillNgForm(
      page,
      {
        parent_name: 'E2E Parent Guardian',
        email: testEmail('parent'),
        phone: '0831234567',
        subject: 'Mathematics',
        notes: 'Learner needs help preparing for June exams.',
      },
      { select: { grade: 'high' }, form }
    );

    await submitNgForm(page, form);
    await expectFormSubmitted(page, 'find_tutor');
  });
});

test.describe('Blueprint WF-10 Booking', () => {
  test('homepage booking modal — open assessment CTA', async ({ page }) => {
    await page.goto('/');
    await dismissCookieOrOverlays(page);
    const cta = page.locator('[data-ngi-open]:not(.ngi-sticky)').first();
    await cta.scrollIntoViewIfNeeded();
    await cta.click({ force: true });
    await expect(page.locator('#ngiBookingModal')).toBeVisible();
    await expect(page.locator('#ngiModalTitle')).toContainText(/assessment/i);
  });
});
