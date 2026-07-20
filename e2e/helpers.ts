import { expect, type Locator, type Page } from '@playwright/test';

export const testEmail = (tag: string) =>
  `e2e.${tag}.${Date.now()}@nextgentutors.test`;

/** Primary POST intake form — excludes sidebar match widgets. */
export function primaryNgForm(page: Page, formId?: string): Locator {
  let form = page
    .locator('form.bi-ngc-form, form.ngc-form')
    .filter({ has: page.locator('input[name="ngc_form_id"]') })
    .filter({ hasNot: page.locator('.ngc-match-form') });

  if (formId) {
    form = form.filter({
      has: page.locator(`input[name="ngc_form_id"][value="${formId}"]`),
    });
  }

  return form.first();
}

export async function dismissCookieOrOverlays(page: Page) {
  const accept = page.getByRole('button', { name: /^Accept$/i });
  if (await accept.isVisible().catch(() => false)) {
    await accept.click();
  }
}

export async function fillNgForm(
  page: Page,
  fields: Record<string, string>,
  options?: { select?: Record<string, string>; form?: Locator }
) {
  const form = options?.form ?? primaryNgForm(page);
  await form.scrollIntoViewIfNeeded();

  for (const [name, value] of Object.entries(options?.select ?? {})) {
    await form.locator(`select[name="${name}"]`).selectOption(value);
  }

  for (const [name, value] of Object.entries(fields)) {
    const locator = form.locator(`[name="${name}"]`);
    const tag = await locator.evaluate((el) => el.tagName.toLowerCase()).catch(() => 'input');
    if (tag === 'select') {
      await locator.selectOption(value);
    } else {
      await locator.fill(value);
    }
  }
}

export async function submitNgForm(page: Page, form?: Locator) {
  const target = form ?? primaryNgForm(page);
  await target.locator('button[type="submit"]').click();
}

export async function expectFormSubmitted(page: Page, formId: string) {
  await page.waitForURL(new RegExp(`ngc_submitted=${formId}`), { timeout: 15_000 });
  await expect(page.url()).toContain(`ngc_submitted=${formId}`);
}

export async function openHomeBookingModal(page: Page) {
  await page.goto('/');
  await dismissCookieOrOverlays(page);
  const cta = page.locator('[data-ngi-open]:not(.ngi-sticky)').first();
  await cta.scrollIntoViewIfNeeded();
  await cta.click({ force: true });
  await expect(page.locator('#ngiBookingModal')).toBeVisible();
}
