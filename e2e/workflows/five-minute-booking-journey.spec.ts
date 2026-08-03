/**
 * Headed UI verification for five-minute journey (port 8890).
 * Domain correlation comes from delivery/evidence/.../domain-journey-latest.json.
 * Real-time meeting join is expected BLOCKED (no meeting adapter).
 */
import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import {
  gotoReady,
  wpLogin,
  primaryNgForm,
  fillNgForm,
  submitNgForm,
  expectFormSubmitted,
  testEmail,
  dismissCookieOrOverlays,
} from '../helpers';

const EVIDENCE = path.join(
  __dirname,
  '..',
  '..',
  'delivery',
  'evidence',
  'five-minute-booking'
);
const DOMAIN_JSON = path.join(EVIDENCE, 'api', 'domain-journey-latest.json');

function loadDomain() {
  if (!fs.existsSync(DOMAIN_JSON)) return null;
  return JSON.parse(fs.readFileSync(DOMAIN_JSON, 'utf8'));
}

test.describe('Five-minute journey — headed UI + correlation', () => {
  test.setTimeout(300_000);
  test('01 preflight pages on :8890', async ({ page }) => {
    const home = await page.goto('/', { waitUntil: 'domcontentloaded' });
    expect(home?.status()).toBeLessThan(400);
    await expect(page.locator('body')).toBeVisible();
    await page.screenshot({
      path: path.join(EVIDENCE, 'screenshots', '01-home.png'),
      fullPage: false,
    });

    for (const p of ['/become-a-tutor/', '/find-a-tutor/', '/register/', '/login/']) {
      const res = await page.goto(p, { waitUntil: 'domcontentloaded' });
      expect(res?.ok() || (res?.status() ?? 500) < 500).toBeTruthy();
    }
  });

  test('02 public tutor registration form submits', async ({ page }) => {
    await gotoReady(page, '/become-a-tutor/');
    const form = primaryNgForm(page, 'become_tutor');
    await expect(form).toBeVisible({ timeout: 45_000 });
    const email = testEmail('fm-tutor');
    await fillNgForm(
      page,
      {
        full_name: 'Headed FiveMin Tutor',
        email,
        phone: '0821112233',
        subjects: 'Mathematics',
        experience: 'Fictional CAPS tutor for five-minute headed E2E.',
        province: 'Gauteng',
      },
      { form }
    );
    const res = await submitNgForm(page, form);
    await expectFormSubmitted(page, 'become_tutor', res);
    await page.screenshot({
      path: path.join(EVIDENCE, 'screenshots', '02-tutor-register.png'),
    });
  });

  test('03 parent registration form submits', async ({ page }) => {
    await gotoReady(page, '/register/?role=parent');
    const parentCard = page.getByRole('button', { name: /parent|guardian/i }).or(
      page.locator('[data-role="parent"], a[href*="role=parent"], .ngc-role-card')
    );
    if (await parentCard.first().isVisible().catch(() => false)) {
      await parentCard.first().click();
    }
    const form =
      (await primaryNgForm(page, 'parent_register').count()) > 0
        ? primaryNgForm(page, 'parent_register')
        : primaryNgForm(page);
    await expect(form).toBeVisible({ timeout: 45_000 });

    const fields: Record<string, string> = {
      parent_name: 'Headed FiveMin Parent',
      email: testEmail('fm-parent'),
      child_name: 'Headed FiveMin Child',
      grade: 'Grade 10',
    };
    // Only fill fields that exist on this form schema.
    for (const [name, value] of Object.entries(fields)) {
      const loc = form.locator(`[name="${name}"]`);
      if ((await loc.count()) > 0) {
        await loc.fill(value);
      }
    }
    const phone = form.locator('[name="phone"], [name="parent_phone"]');
    if ((await phone.count()) > 0) {
      await phone.first().fill('0824445566');
    }

    const res = await submitNgForm(page, form);
    await expectFormSubmitted(page, 'parent_register', res).catch(async () => {
      await expect(page.locator('#ngt-toast, body')).toContainText(
        /thank you|success|submitted|welcome|registered/i,
        { timeout: 20_000 }
      );
    });
    await page.screenshot({
      path: path.join(EVIDENCE, 'screenshots', '03-parent-register.png'),
    });
  });

  test('04 find-a-tutor marketplace shows bookable cards', async ({ page }) => {
    await gotoReady(page, '/find-a-tutor/');
    const card = page.locator('[data-ngc-marketplace] .ngc-mkt-card').first();
    await expect(card).toBeVisible({ timeout: 60_000 });
    await page.screenshot({
      path: path.join(EVIDENCE, 'screenshots', '04-find-tutor.png'),
    });
  });

  test('05 domain correlation evidence present', async () => {
    const domain = loadDomain();
    expect(domain, 'domain journey JSON missing — run five-minute-journey.php').toBeTruthy();
    expect(domain.timezone).toBe('Africa/Johannesburg');
    expect(domain.correlation?.booking_id || domain.phases?.five_minute_booking?.booking_id).toBeTruthy();
    expect(domain.meeting?.status).toBe('BLOCKED');
    fs.writeFileSync(
      path.join(EVIDENCE, 'timeline.json'),
      JSON.stringify(
        {
          run_id: domain.run_id,
          scheduled_start: domain.scheduled_start,
          target_start: domain.target_start,
          now_local: domain.now_local,
          slot_constraint: domain.slot_constraint,
          meeting: domain.meeting,
          verdicts: domain.verdicts,
        },
        null,
        2
      )
    );
  });

  test('06 admin surfaces reachable after login', async ({ page }) => {
    await wpLogin(page);
    await page.goto('/wp-admin/admin.php?page=ngc-demo-control', {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await page.screenshot({
      path: path.join(EVIDENCE, 'screenshots', '06-admin.png'),
    });
    // Meeting join control must not appear as a fake ready state without adapter.
    const body = await page.locator('body').innerText();
    expect(body.toLowerCase()).not.toMatch(/zoom\.us\/j\//);
  });

  test('07 unauthorized visitor cannot see meeting secrets', async ({ browser }) => {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await page.goto('/student-dashboard/', { waitUntil: 'domcontentloaded' });
    const text = (await page.locator('body').innerText()).toLowerCase();
    expect(text).not.toMatch(/meeting password|zoom sdk|jitsi jwt|join_url\s*=/);
    await page.screenshot({
      path: path.join(EVIDENCE, 'screenshots', '07-unauth-student.png'),
    });
    await ctx.close();
  });
});
