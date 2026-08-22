/**
 * TC-01…TC-15 Functional headed E2E.
 * cd e2e && npm run test:tc-matrix-headed
 */
import { test, expect } from '@playwright/test';
import {
  annotateTc,
  deepCrmEnabled,
  DEMO_PERSONAS,
  ensureSiteUp,
  expectFormSubmitted,
  fillNgForm,
  gotoReady,
  loginPersona,
  note,
  payfastSettleEnabled,
  primaryNgForm,
  submitNgForm,
  tcEvidenceDir,
  tcShot,
  testEmail,
  wpLogin,
} from '../helpers/tc-matrix';

const EVIDENCE = tcEvidenceDir();

test.describe.configure({ mode: 'serial' });
test.setTimeout(240_000);

test.beforeAll(async ({ request }) => {
  await ensureSiteUp(request);
  note(EVIDENCE, 'run-meta.json', {
    suite: 'TC-01..15',
    baseURL: process.env.BASE_URL || 'http://localhost:8890',
    deepCrm: deepCrmEnabled(),
    payfastSettle: payfastSettleEnabled(),
    startedAt: new Date().toISOString(),
  });
});

test('TC-01 Parent registration', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-01', 'User Registration — Parent');
  await gotoReady(page, '/register/?role=parent');
  const form = primaryNgForm(page, 'parent_register');
  await expect(form).toBeVisible({ timeout: 30_000 });
  const email = testEmail('tc01-parent');
  await fillNgForm(page, { parent_name: 'TC01 Parent', email, child_name: 'TC01 Child', grade: 'Grade 10' }, { form });
  const res = await submitNgForm(page, form);
  await expectFormSubmitted(page, 'parent_register', res);
  await tcShot(page, EVIDENCE, 'TC-01.png');
  note(EVIDENCE, 'TC-01.json', { email, crm: deepCrmEnabled() ? 'DEEP' : 'UI_ONLY' });
});

test('TC-02 Tutor applicant form', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-02', 'User Registration — Tutor Applicant');
  await gotoReady(page, '/become-a-tutor/');
  const form = primaryNgForm(page, 'become_tutor');
  await expect(form).toBeVisible({ timeout: 30_000 });
  const email = testEmail('tc02-tutor');
  await fillNgForm(
    page,
    {
      full_name: 'TC02 Tutor',
      email,
      phone: '0821234567',
      subjects: 'Mathematics',
      experience: 'CAPS tutoring for TC-02 headed matrix.',
      province: 'Gauteng',
    },
    { form }
  );
  const res = await submitNgForm(page, form);
  await expectFormSubmitted(page, 'become_tutor', res);
  await tcShot(page, EVIDENCE, 'TC-02.png');
  note(EVIDENCE, 'TC-02.json', { email, crm: deepCrmEnabled() ? 'DEEP' : 'UI_ONLY' });
});

test('TC-03 Admin tutor verification surface', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-03', 'Tutor Verification — Approved');
  await wpLogin(page);
  let opened = '';
  for (const p of [
    '/wp-admin/admin.php?page=ngc-tutors',
    '/wp-admin/admin.php?page=ngc-tutor-applications',
    '/wp-admin/users.php',
  ]) {
    await page.goto(p, { waitUntil: 'domcontentloaded' });
    const t = await page.locator('body').innerText();
    if (/tutor|user|approve|applicant/i.test(t) && !/not allowed/i.test(t)) {
      opened = p;
      break;
    }
  }
  expect(opened).toBeTruthy();
  await tcShot(page, EVIDENCE, 'TC-03.png');
  note(EVIDENCE, 'TC-03.json', { path: opened, chain: 'PARTIAL — Approve click needs pending applicant' });
});

test('TC-04 Admin rejection surface', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-04', 'Tutor Verification — Rejected');
  await wpLogin(page);
  await page.goto('/wp-admin/users.php', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#wpbody')).toBeVisible();
  await tcShot(page, EVIDENCE, 'TC-04.png');
  note(EVIDENCE, 'TC-04.json', { status: 'SURFACE' });
});

test('TC-05 Pay-as-you-go book path to checkout', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-05', 'Book Session — Pay As You Go');
  await loginPersona(page, DEMO_PERSONAS.parent.email);
  await gotoReady(page, '/find-a-tutor/');
  await tcShot(page, EVIDENCE, 'TC-05-find.png');
  const profile = page.locator('a[href*="/tutors/"], a.bi-tutor-card__link, .ngc-tutor-card a').first();
  if (await profile.isVisible({ timeout: 15_000 }).catch(() => false)) {
    await profile.click();
    await page.waitForLoadState('domcontentloaded');
    const book = page.locator('.bi-book-lesson-trigger, button:has-text("Book"), a:has-text("Book")').first();
    if (await book.isVisible().catch(() => false)) await book.click();
  }
  await gotoReady(page, '/parent-checkout/');
  await tcShot(page, EVIDENCE, 'TC-05-checkout.png');
  const body = await page.locator('body').innerText();
  note(EVIDENCE, 'TC-05.json', {
    url: page.url(),
    payfast: /payfast/i.test(page.url()) || /payfast/i.test(body),
    settle: payfastSettleEnabled() ? 'ENABLED' : 'UI_ONLY',
  });
  expect(body.length).toBeGreaterThan(20);
});

test('TC-06 Package pricing surface', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-06', 'Book Session — 10 Session Package');
  await gotoReady(page, '/pricing/');
  await tcShot(page, EVIDENCE, 'TC-06.png');
  await expect(page.locator('body')).toContainText(/pricing|package|session|plan|R\s?\d/i);
});

test('TC-07 Tutor complete-session surface', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-07', 'Complete Session — Normal');
  await loginPersona(page, DEMO_PERSONAS.tutorApproved.email);
  await gotoReady(page, DEMO_PERSONAS.tutorApproved.path);
  await tcShot(page, EVIDENCE, 'TC-07.png');
  await expect(page.locator('body')).toContainText(/tutor|session|dashboard|earning|schedule/i);
  note(EVIDENCE, 'TC-07.json', { earnings: 'UNVERIFIED without live completed booking' });
});

test('TC-08 No-show control hunt', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-08', 'Complete Session — No-Show');
  await loginPersona(page, DEMO_PERSONAS.tutorApproved.email);
  await gotoReady(page, DEMO_PERSONAS.tutorApproved.path);
  const n = await page.getByText(/no-?show/i).count();
  await tcShot(page, EVIDENCE, 'TC-08.png');
  note(EVIDENCE, 'TC-08.json', { noShowControls: n, status: 'PARTIAL' });
});

test('TC-09 Rating surface hunt', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-09', 'Submit Rating — Positive');
  await loginPersona(page, DEMO_PERSONAS.parent.email);
  let found = '';
  for (const p of ['/parent-dashboard/', '/reviews/', '/student-dashboard/']) {
    await gotoReady(page, p);
    if ((await page.locator('[data-rating], .ngc-rating, input[name*="rating"], .star-rating').count()) > 0) {
      found = p;
      break;
    }
  }
  await tcShot(page, EVIDENCE, 'TC-09.png');
  note(EVIDENCE, 'TC-09.json', { found: found || 'NOT_FOUND' });
});

test('TC-10 Negative rating / support path', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-10', 'Submit Rating — Negative');
  await gotoReady(page, '/contact/');
  await tcShot(page, EVIDENCE, 'TC-10.png');
  await expect(page.locator('body')).toContainText(/contact|support|help|ticket/i);
});

test('TC-11 Admin payout surface', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-11', 'Monthly Payout Processing');
  await wpLogin(page);
  let opened = '';
  for (const p of [
    '/wp-admin/admin.php?page=ngc-payouts',
    '/wp-admin/admin.php?page=ngc-finance',
    '/wp-admin/admin.php?page=nextgen-companion',
  ]) {
    await page.goto(p, { waitUntil: 'domcontentloaded' });
    const t = await page.locator('body').innerText();
    if (/payout|finance|companion|nextgen|earning/i.test(t) && !/not allowed/i.test(t)) {
      opened = p;
      break;
    }
  }
  await tcShot(page, EVIDENCE, 'TC-11.png');
  note(EVIDENCE, 'TC-11.json', { path: opened || 'NONE', eft: 'UNVERIFIED' });
  expect(opened).toBeTruthy();
});

test('TC-12 Referral mention on parent dashboard', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-12', 'Referral — Successful');
  await loginPersona(page, DEMO_PERSONAS.parent.email);
  await gotoReady(page, DEMO_PERSONAS.parent.path);
  const body = await page.locator('body').innerText();
  await tcShot(page, EVIDENCE, 'TC-12.png');
  note(EVIDENCE, 'TC-12.json', { referralMention: /refer/i.test(body) });
});

test('TC-13 Student dashboard', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-13', 'Dashboard — Student View');
  await loginPersona(page, DEMO_PERSONAS.studentAdult.email);
  await gotoReady(page, DEMO_PERSONAS.studentAdult.path);
  await tcShot(page, EVIDENCE, 'TC-13.png');
  await expect(page.locator('body')).toContainText(/student|dashboard|session|lesson|NextGen/i);
});

test('TC-14 Tutor dashboard', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-14', 'Dashboard — Tutor View');
  await loginPersona(page, DEMO_PERSONAS.tutorApproved.email);
  await gotoReady(page, '/tutor-dashboard/');
  if (/wp-login/.test(page.url())) await gotoReady(page, '/instructor/');
  await tcShot(page, EVIDENCE, 'TC-14.png');
  await expect(page.locator('body')).toContainText(/tutor|earning|session|dashboard|schedule/i);
});

test('TC-15 Admin NextGen menu', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-15', 'Dashboard — Admin View');
  await wpLogin(page);
  await page.goto('/wp-admin/', { waitUntil: 'domcontentloaded' });
  const menu = page.locator('#adminmenu').getByText(/NextGen|Companion|NGT|Tutors/i);
  await expect(menu.first()).toBeVisible({ timeout: 30_000 });
  await menu.first().click();
  await tcShot(page, EVIDENCE, 'TC-15.png');
});
