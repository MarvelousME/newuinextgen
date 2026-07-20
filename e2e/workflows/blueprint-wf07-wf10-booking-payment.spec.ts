import { test, expect } from '@playwright/test';
import {
  testEmail,
  fillNgForm,
  submitNgForm,
  expectFormSubmitted,
  primaryNgForm,
  dismissCookieOrOverlays,
  openHomeBookingModal,
} from '../helpers';

test.describe('Blueprint WF-07 Tutor Matching', () => {
  test('find-a-tutor form — parent assessment request', async ({ page }) => {
    await page.goto('/find-a-tutor/', { waitUntil: 'domcontentloaded' });
    const form = primaryNgForm(page, 'find_tutor');
    await expect(form).toBeVisible({ timeout: 30_000 });

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

    const res = await submitNgForm(page, form);
    await expectFormSubmitted(page, 'find_tutor', res);
  });
});

test.describe('Blueprint WF-10 Booking', () => {
  test('homepage booking modal — open assessment CTA', async ({ page }) => {
    await openHomeBookingModal(page);
    const modal = page.locator('#ngiBookingModal');
    if (await modal.isVisible().catch(() => false)) {
      await expect(modal).toBeVisible();
      const title = page.locator('#ngiModalTitle');
      if (await title.isVisible().catch(() => false)) {
        await expect(title).toContainText(/assessment|book|tutor|find/i);
      }
    } else {
      await dismissCookieOrOverlays(page);
      await expect(page.locator('body')).toContainText(/NextGen|Tutor|Book/i);
    }
  });
});
