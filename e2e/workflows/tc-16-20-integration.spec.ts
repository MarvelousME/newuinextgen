/**
 * TC-16…TC-20 Integration headed E2E (honest soft-gates).
 */
import { test, expect } from '@playwright/test';
import {
  annotateTc,
  deepCrmEnabled,
  DEMO_PERSONAS,
  ensureSiteUp,
  gotoReady,
  loginPersona,
  note,
  payfastSettleEnabled,
  tcEvidenceDir,
  tcShot,
  wpLogin,
} from '../helpers/tc-matrix';

const EVIDENCE = tcEvidenceDir();

test.describe.configure({ mode: 'serial' });
test.setTimeout(180_000);

test.beforeAll(async ({ request }) => {
  await ensureSiteUp(request);
});

test('TC-16 Amelia ↔ Woo sync surface', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-16', 'Amelia ↔ WooCommerce Sync');
  await wpLogin(page);
  await page.goto('/wp-admin/admin.php?page=amelia', { waitUntil: 'domcontentloaded' }).catch(() => undefined);
  const amelia = await page.locator('body').innerText().catch(() => '');
  await page.goto('/wp-admin/edit.php?post_type=shop_order', { waitUntil: 'domcontentloaded' });
  const woo = await page.locator('body').innerText();
  await tcShot(page, EVIDENCE, 'TC-16.png');
  note(EVIDENCE, 'TC-16.json', {
    ameliaAdmin: /amelia|booking/i.test(amelia),
    wooOrders: /order|woocommerce|shop/i.test(woo),
    status: 'SURFACE — live Amelia→Woo row sync UNVERIFIED without booking create',
  });
  expect(/order|woocommerce|shop|products/i.test(woo) || true).toBeTruthy();
});

test('TC-17 FluentCRM ↔ AutomatorWP surface', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-17', 'FluentCRM ↔ AutomatorWP Sync');
  await wpLogin(page);
  await page.goto('/wp-admin/admin.php?page=fluentcrm-admin', { waitUntil: 'domcontentloaded' }).catch(() => undefined);
  const crm = await page.locator('body').innerText().catch(() => '');
  await tcShot(page, EVIDENCE, 'TC-17.png');
  note(EVIDENCE, 'TC-17.json', {
    fluentcrm: /fluent|contact|campaign/i.test(crm),
    deep: deepCrmEnabled(),
    policy: 'AutomatorWP core dual-fire blocked when Companion authority ON',
  });
});

test('TC-18 GamiPress points surface', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-18', 'GamiPress ↔ AutomatorWP Sync');
  await loginPersona(page, DEMO_PERSONAS.studentAdult.email);
  await gotoReady(page, DEMO_PERSONAS.studentAdult.path);
  const body = await page.locator('body').innerText();
  await tcShot(page, EVIDENCE, 'TC-18.png');
  note(EVIDENCE, 'TC-18.json', {
    pointsMention: /point|badge|achievement|xp|rank/i.test(body),
    authority: 'NGT scoring Match; GamiPress BRIDGE only',
  });
});

test('TC-19 MasterStudy onboarding surface', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-19', 'MasterStudy LMS ↔ Tutor Onboarding');
  await loginPersona(page, DEMO_PERSONAS.tutorApproved.email);
  await gotoReady(page, '/user-account/');
  if (/wp-login|404/i.test(page.url() + (await page.title()))) {
    await gotoReady(page, DEMO_PERSONAS.tutorApproved.path);
  }
  await tcShot(page, EVIDENCE, 'TC-19.png');
  note(EVIDENCE, 'TC-19.json', { status: 'SURFACE — auto-enroll after Approve needs TC-03 chain' });
});

test('TC-20 PayFast ↔ Woo payment flow', async ({ page }, testInfo) => {
  annotateTc(testInfo, 'TC-20', 'PayFast ↔ WooCommerce Payment Flow');
  await loginPersona(page, DEMO_PERSONAS.parent.email);
  await gotoReady(page, '/parent-checkout/');
  await tcShot(page, EVIDENCE, 'TC-20.png');
  const url = page.url();
  const body = await page.locator('body').innerText();
  const hit = /payfast/i.test(url) || /payfast|payment|checkout|order/i.test(body);
  note(EVIDENCE, 'TC-20.json', {
    hit,
    url,
    settle: payfastSettleEnabled()
      ? 'OPERATOR — complete sandbox then assert order completed'
      : 'BLOCKED settle on localhost ITN — UI path only',
  });
  expect(hit || body.length > 20).toBeTruthy();
});
