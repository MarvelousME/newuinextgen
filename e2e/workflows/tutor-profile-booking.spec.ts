/**
 * WF-07 / WF-10 — "View profile" and "Book Session" must carry the chosen tutor
 * from the marketplace into the profile, the booking drawer, and the intake.
 */
import { test, expect } from '@playwright/test';
import {
  gotoReady,
  testEmail,
  fillNgForm,
  submitNgForm,
  expectFormSubmitted,
  primaryNgForm,
} from '../helpers';

const cards = '[data-ngc-marketplace] .ngc-mkt-card';

async function firstMarketplaceCard(page: import('@playwright/test').Page) {
  await gotoReady(page, '/find-a-tutor/');
  const card = page
    .locator(cards)
    .filter({ has: page.locator('.ngc-mkt-btn--book[data-tutor-id]:not([data-tutor-id=""])') })
    .first();
  await expect(card).toBeVisible({ timeout: 45_000 });
  return card;
}

test.describe('Tutor marketplace — view profile', () => {
  test('View profile opens the tutor profile with booking section', async ({ page }) => {
    const card = await firstMarketplaceCard(page);
    const name = ((await card.locator('.ngc-mkt-card__name').textContent()) || '').trim();
    expect(name.length).toBeGreaterThan(1);

    await card.locator('.ngc-mkt-btn--outline').click();
    await page.waitForURL(/\/tutors\/[^/]+\/?$/, { timeout: 45_000, waitUntil: 'domcontentloaded' });

    await expect(page.locator('h1.bi-profile-hero__name')).toContainText(name.split(' ')[0]);
    await expect(page.locator('#book')).toBeAttached();
  });
});

test.describe('Tutor marketplace — book session', () => {
  test('Book Session keeps tutor context and opens the booking drawer', async ({ page }) => {
    const card = await firstMarketplaceCard(page);
    const book = card.locator('.ngc-mkt-btn--book');
    await expect(book).toBeVisible();

    const tutorId = await book.getAttribute('data-tutor-id');
    expect(tutorId).toBeTruthy();
    expect(await book.getAttribute('href')).toContain(`ngc_tutor_id=${tutorId}`);

    await book.click();
    const drawer = page.locator('#bi-booking-drawer');
    await expect(drawer).toHaveClass(/is-open/, { timeout: 20_000 });
    await expect(drawer.locator('[data-bi-bd-title]')).toContainText(/book with/i);

    const cont = drawer.locator('[data-bi-bd-continue]');
    expect(await cont.getAttribute('href')).toContain(`ngc_tutor_id=${tutorId}`);
  });

  test('profile Book a Session leads to a tutor-scoped intake request', async ({ page }) => {
    const card = await firstMarketplaceCard(page);
    const tutorId = await card.locator('.ngc-mkt-btn--book').getAttribute('data-tutor-id');
    await card.locator('.ngc-mkt-btn--outline').click();
    await page.waitForURL(/\/tutors\//, { timeout: 45_000, waitUntil: 'domcontentloaded' });

    const heroName = ((await page.locator('h1.bi-profile-hero__name').textContent()) || '').trim();
    const hero = page.locator('.bi-profile-hero__cta .bi-book-lesson-trigger').first();
    await expect(hero).toBeVisible();
    expect(await hero.getAttribute('href')).toContain(`ngc_tutor_id=${tutorId}`);

    await hero.click();
    const cont = page.locator('#bi-booking-drawer [data-bi-bd-continue]');
    await expect(cont).toBeVisible({ timeout: 20_000 });
    await cont.click();

    await page.waitForURL(/ngc_tutor_id=/, { timeout: 45_000, waitUntil: 'domcontentloaded' });
    await expect(page.locator('[data-bi-intake-notice]').first()).toContainText(
      new RegExp(`booking request for ${heroName.split(' ')[0]}`, 'i')
    );
    await expect(page.locator('input[name="preferred_tutor_id"]').first()).toHaveValue(
      String(tutorId)
    );
  });

  test('tutor-scoped intake submits the booking request', async ({ page }) => {
    const card = await firstMarketplaceCard(page);
    const tutorId = await card.locator('.ngc-mkt-btn--book').getAttribute('data-tutor-id');

    await gotoReady(page, `/find-a-tutor/?ngc_tutor_id=${tutorId}`);
    const form = primaryNgForm(page, 'find_tutor');
    await expect(form).toBeVisible({ timeout: 30_000 });
    await expect(form.locator('input[name="preferred_tutor_id"]')).toHaveValue(String(tutorId));

    await fillNgForm(
      page,
      {
        parent_name: 'E2E Booking Parent',
        email: testEmail('booking'),
        phone: '0839876543',
        subject: 'Mathematics',
      },
      { select: { grade: 'matric' }, form }
    );

    const res = await submitNgForm(page, form);
    await expectFormSubmitted(page, 'find_tutor', res);
  });
});
