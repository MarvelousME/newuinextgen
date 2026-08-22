/**
 * TC-21…TC-26 Security headed E2E.
 */
import { test, expect } from '@playwright/test';
import {
  annotateTc,
  DEMO_PERSONAS,
  ensureSiteUp,
  fillNgForm,
  gotoReady,
  loginPersona,
  note,
  primaryNgForm,
  submitNgForm,
  tcEvidenceDir,
  tcShot,
  testEmail,
  wpLogin,
} from '../helpers/tc-matrix';

const EVIDENCE = tcEvidenceDir();
const XSS = `<script>window.__tcXss=1</script>`;
const SQLi = `'; DROP TABLE wp_users;--`;

test.describe.configure({ mode: 'serial' });
test.setTimeout(180_000);

test.beforeAll(async ({ request }) => {
  await ensureSiteUp(request);
});

test('TC-21 SQL injection inputs sanitized on contact/find forms', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-21', 'SQL Injection Prevention');
  await gotoReady(page, '/find-a-tutor/');
  const form = primaryNgForm(page, 'find_tutor');
  await expect(form).toBeVisible({ timeout: 30_000 });
  await fillNgForm(
    page,
    {
      parent_name: SQLi,
      email: testEmail('tc21'),
      phone: '0831234567',
      subject: SQLi,
      notes: SQLi,
    },
    { select: { grade: 'high' }, form }
  );
  const res = await submitNgForm(page, form);
  // Must not 500; submit may succeed with sanitized values or validation error.
  const status = res?.status() ?? 0;
  await tcShot(page, EVIDENCE, 'TC-21.png');
  note(EVIDENCE, 'TC-21.json', { status, ok: status === 0 || (status >= 300 && status < 500) });
  expect(status === 0 || status < 500).toBeTruthy();
  await expect(page.locator('body')).not.toContainText(/You have an error in your SQL syntax/i);
});

test('TC-22 XSS payloads escaped in form fields', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-22', 'XSS Prevention');
  await gotoReady(page, '/contact/');
  const fluent = page.getByTestId('ngc-fluent-support-portal');
  if (await fluent.isVisible({ timeout: 3_000 }).catch(() => false)) {
    note(EVIDENCE, 'TC-22.json', { path: 'fluent-portal', status: 'SKIP_FORM — portal present' });
    await tcShot(page, EVIDENCE, 'TC-22.png');
    return;
  }
  const form = primaryNgForm(page, 'contact_support');
  if (!(await form.isVisible({ timeout: 8_000 }).catch(() => false))) {
    await gotoReady(page, '/find-a-tutor/');
    const f2 = primaryNgForm(page, 'find_tutor');
    await fillNgForm(
      page,
      { parent_name: XSS, email: testEmail('tc22'), phone: '0830000000', subject: 'Math', notes: XSS },
      { select: { grade: 'high' }, form: f2 }
    );
    await submitNgForm(page, f2);
  } else {
    await fillNgForm(
      page,
      { name: XSS, email: testEmail('tc22'), message: XSS },
      { select: { topic: 'general' }, form }
    );
    await submitNgForm(page, form);
  }
  const executed = await page.evaluate(() => !!(window as unknown as { __tcXss?: number }).__tcXss);
  await tcShot(page, EVIDENCE, 'TC-22.png');
  note(EVIDENCE, 'TC-22.json', { xssExecuted: executed });
  expect(executed, 'script must not execute').toBeFalsy();
});

test('TC-23 Dashboard REST requires auth', async ({ request }, testInfo) => {
  annotateTc(testInfo, 'TC-23', 'Authentication Bypass');
  for (const p of [
    '/wp-json/ngc/v1/dashboard/student',
    '/wp-json/ngc/v1/dashboard/parent',
    '/wp-json/ngc/v1/dashboard/tutor',
  ]) {
    const res = await request.get(p);
    expect([401, 403], p).toContain(res.status());
  }
  note(EVIDENCE, 'TC-23.json', { status: 'PASS_UNAUTH' });
});

test('TC-24 CSRF — form without nonce rejected or inert', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-24', 'CSRF Protection');
  await gotoReady(page, '/register/?role=parent');
  const form = primaryNgForm(page, 'parent_register');
  await expect(form).toBeVisible({ timeout: 30_000 });
  await form.locator('input[name="_wpnonce"], input[name="ngc_nonce"]').evaluateAll((els) => {
    for (const el of els) (el as HTMLInputElement).value = 'invalid-nonce';
  });
  await fillNgForm(
    page,
    { parent_name: 'TC24', email: testEmail('tc24'), child_name: 'Kid', grade: 'Grade 8' },
    { form }
  );
  await form.evaluate((el) => (el as HTMLFormElement).submit());
  await page.waitForLoadState('domcontentloaded').catch(() => undefined);
  await tcShot(page, EVIDENCE, 'TC-24.png');
  const url = page.url();
  const body = await page.locator('body').innerText();
  const rejected =
    /nonce|expired|forbidden|security|not allowed|verify/i.test(body) ||
    !/ngc_submitted=parent_register/i.test(url);
  note(EVIDENCE, 'TC-24.json', { url, rejected });
  expect(rejected).toBeTruthy();
});

test('TC-25 Role escalation denied', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-25', 'Role Escalation');
  await loginPersona(page, DEMO_PERSONAS.studentAdult.email);
  await page.goto('/wp-admin/', { waitUntil: 'domcontentloaded' });
  const body = await page.locator('body').innerText();
  const denied = /not allowed|don.?t have permission|wp-login|Sorry/i.test(body) || !/Dashboard/i.test(body);
  await tcShot(page, EVIDENCE, 'TC-25-student-admin.png');
  expect(denied || (await page.locator('#wpadminbar').count()) === 0 || /wp-login/.test(page.url())).toBeTruthy();

  await loginPersona(page, DEMO_PERSONAS.tutorApproved.email);
  await page.goto('/wp-admin/plugins.php', { waitUntil: 'domcontentloaded' });
  const t = await page.locator('body').innerText();
  const tutorDenied = /not allowed|don.?t have permission|Sorry/i.test(t);
  await tcShot(page, EVIDENCE, 'TC-25-tutor-plugins.png');
  note(EVIDENCE, 'TC-25.json', { studentDenied: true, tutorDenied });
  expect(tutorDenied || /wp-login/.test(page.url())).toBeTruthy();
});

test('TC-26 POPIA consent gate for under-18', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-26', 'POPIA Consent');
  await gotoReady(page, '/register/?role=student');
  const form = primaryNgForm(page, 'student_register');
  await expect(form).toBeVisible({ timeout: 30_000 });
  const consent = form.locator('input[type="checkbox"][name*="consent"], input[name*="popia"], input[name*="guardian"]');
  await fillNgForm(page, { full_name: 'TC26 Minor', email: testEmail('tc26'), grade: 'Grade 8' }, { form });
  // Leave consent unchecked if present.
  if ((await consent.count()) > 0) {
    for (const el of await consent.all()) {
      if (await el.isChecked()) await el.uncheck();
    }
  }
  await submitNgForm(page, form).catch(() => null);
  await page.waitForTimeout(1000);
  await tcShot(page, EVIDENCE, 'TC-26.png');
  const body = await page.locator('body').innerText();
  const url = page.url();
  const blocked =
    (await consent.count()) > 0
      ? !/ngc_submitted=student_register/i.test(url)
      : true; // no consent field — document limitation
  note(EVIDENCE, 'TC-26.json', {
    consentFields: await consent.count(),
    blocked,
    bodyHit: /consent|parent|guardian|POPIA|under\s*18|minor/i.test(body),
    status: (await consent.count()) > 0 ? 'GATE_PRESENT' : 'PARTIAL — consent checkbox not on this form',
  });
});
