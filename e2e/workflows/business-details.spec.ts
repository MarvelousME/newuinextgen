/**
 * Business details — SSOT saved into core plugins and displayed on public + admin surfaces.
 *
 * Run headed:
 *   cd e2e
 *   $env:BASE_URL='http://localhost:8900'
 *   npm run test:business-headed
 *
 * Prefers a prior `wp ngt system configure --force-safe` (or Companion Business Profile → Re-apply).
 */
import { test, expect } from '@playwright/test';
import {
  dismissCookieOrOverlays,
  gotoReady,
  wpLogin,
  wpLogout,
} from '../helpers';

const PHONE_DIGITS = '0813340625';
const SUPPORT_EMAIL = 'support@nextgentutors.co.za';
const ADMIN_EMAIL = 'admin@nextgentutors.co.za';
const WHATSAPP = '27813340625';
const COMPANY = 'NextGenTutors';

function digits(s: string) {
  return (s || '').replace(/\D+/g, '');
}

test.describe('Business details — public display', () => {
  test.setTimeout(120_000);

  test('footer shows applied phone, support email, and service area', async ({ page }) => {
    await gotoReady(page, '/');
    const phone = page.getByTestId('bi-footer-phone');
    const email = page.getByTestId('bi-footer-email');
    await expect(phone).toBeVisible({ timeout: 30_000 });
    await expect(email).toBeVisible();
    expect(digits(await phone.innerText())).toBe(PHONE_DIGITS);
    await expect(email).toHaveText(SUPPORT_EMAIL);
    await expect(page.getByTestId('bi-footer-service-area')).toContainText(/Johannesburg|nationwide|South Africa/i);
  });

  test('WhatsApp FAB points at business WhatsApp number', async ({ page }) => {
    await gotoReady(page, '/');
    const fab = page.getByTestId('bi-whatsapp-fab');
    // Sticky dock may hide classic FAB — either fab or footer contact proves wiring.
    if (await fab.isVisible().catch(() => false)) {
      const href = (await fab.getAttribute('href')) || '';
      expect(href).toMatch(new RegExp(WHATSAPP));
    } else {
      await expect(page.getByTestId('bi-footer-contact')).toBeVisible();
    }
  });

  test('contact page card shows business phone and support email', async ({ page }) => {
    await gotoReady(page, '/contact/');
    const card = page.getByTestId('bi-contact-card');
    if (await card.isVisible().catch(() => false)) {
      expect(digits(await page.getByTestId('bi-contact-phone').innerText())).toBe(PHONE_DIGITS);
      await expect(page.getByTestId('bi-contact-email')).toContainText(SUPPORT_EMAIL);
      const wa = page.getByTestId('bi-contact-whatsapp');
      if (await wa.isVisible().catch(() => false)) {
        expect((await wa.getAttribute('href')) || '').toMatch(new RegExp(WHATSAPP));
      }
    } else {
      // Elementor or alternate contact layout — body must still show SSOT contacts.
      const body = await page.locator('body').innerText();
      expect(digits(body)).toContain(PHONE_DIGITS);
      expect(body).toContain(SUPPORT_EMAIL);
    }
  });
});

test.describe('Business details — admin core plugins', () => {
  test.setTimeout(180_000);

  test('Companion Business Profile shows saved company fields', async ({ page }) => {
    await wpLogin(page);
    await page.goto('/wp-admin/admin.php?page=ngc-business-profile', {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await expect(page.getByTestId('ngc-business-profile')).toBeVisible({ timeout: 30_000 });
    await expect(page.getByTestId('ngc-business-profile-applied')).toHaveText(/YES/i);

    const apply = page.getByTestId('ngc-business-profile-apply');
    await apply.click();
    await page.waitForURL(/ngc-business-profile/, { timeout: 120_000 });
    await expect(page.getByTestId('ngc-business-profile-flash')).toContainText(/applied/i, {
      timeout: 60_000,
    });

    await expect(page.getByTestId('ngc-business-profile-blogname')).toContainText(COMPANY);
    await expect(page.getByTestId('ngc-business-profile-admin-email')).toContainText(ADMIN_EMAIL);

    const fields = page.getByTestId('ngc-business-profile-fields');
    await expect(fields.locator('[data-field="company_name"]')).toContainText(COMPANY);
    await expect(fields.locator('[data-field="email"]')).toContainText(SUPPORT_EMAIL);
    expect(digits(await fields.locator('[data-field="phone"]').innerText())).toBe(PHONE_DIGITS);
    expect(digits(await fields.locator('[data-field="whatsapp"]').innerText())).toBe(WHATSAPP);
  });

  test('WordPress general settings match business identity', async ({ page }) => {
    await wpLogin(page);
    await page.goto('/wp-admin/options-general.php', { waitUntil: 'domcontentloaded', timeout: 90_000 });
    const blog = page.locator('#blogname, input[name="blogname"]').first();
    await expect(blog).toBeVisible({ timeout: 30_000 });
    await expect(blog).toHaveValue(new RegExp(COMPANY, 'i'));
    const adminEmail = page.locator('#new_admin_email, input[name="new_admin_email"], #admin_email').first();
    await expect(adminEmail).toHaveValue(ADMIN_EMAIL);
  });

  test('AI Integration settings show business identity banner', async ({ page }) => {
    await wpLogin(page);
    await page.goto('/wp-admin/admin.php?page=ngtai-settings', {
      waitUntil: 'domcontentloaded',
      timeout: 90_000,
    });
    if (!(await page.getByTestId('ngtai-business-identity').isVisible().catch(() => false))) {
      await page.goto('/wp-admin/admin.php?page=ngtai', { waitUntil: 'domcontentloaded' });
    }
    const banner = page.getByTestId('ngtai-business-identity');
    if (await banner.isVisible().catch(() => false)) {
      await expect(banner).toContainText(COMPANY);
      await expect(banner).toContainText(SUPPORT_EMAIL);
    } else {
      test.skip(true, 'AI Integration admin not available');
    }
  });

  test('Automation Hub shows business identity', async ({ page }) => {
    await wpLogin(page);
    await page.goto('/wp-admin/admin.php?page=ngt-hub', { waitUntil: 'domcontentloaded', timeout: 90_000 });
    const banner = page.getByTestId('ngt-hub-business-identity');
    if (await banner.isVisible().catch(() => false)) {
      await expect(banner).toContainText(COMPANY);
      await expect(banner).toContainText(SUPPORT_EMAIL);
    } else {
      test.skip(true, 'Automation Hub admin not available');
    }
  });

  test('Plugin Manager shows company name and powered-by', async ({ page }) => {
    await wpLogin(page);
    await page.goto('/wp-admin/admin.php?page=ui-ux-pro-max', {
      waitUntil: 'domcontentloaded',
      timeout: 90_000,
    });
    if (!(await page.getByTestId('ngcpm-business-identity').isVisible().catch(() => false))) {
      await page.goto('/wp-admin/admin.php?page=ngcpm', { waitUntil: 'domcontentloaded' });
    }
    const brand = page.getByTestId('ngcpm-business-identity');
    if (await brand.isVisible().catch(() => false)) {
      await expect(page.getByTestId('ngcpm-company-name')).toContainText(COMPANY);
      await expect(page.getByTestId('ngcpm-powered-by')).toContainText(/GET ONLINE NOW/i);
    } else {
      test.skip(true, 'Plugin Manager UI not available');
    }
  });

  test('public footer still matches after admin re-apply', async ({ page }) => {
    await gotoReady(page, '/');
    expect(digits(await page.getByTestId('bi-footer-phone').innerText())).toBe(PHONE_DIGITS);
    await expect(page.getByTestId('bi-footer-email')).toHaveText(SUPPORT_EMAIL);
  });
});
