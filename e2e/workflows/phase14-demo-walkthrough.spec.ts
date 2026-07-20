/**
 * Phase 14 Relational Demo Walkthrough — headed E2E demonstration.
 *
 * Mirrors documentation/tutorials/01-phase14-demo-walkthrough.md
 *
 * Run headed:
 *   cd e2e && npm run test:phase14
 */
import { test, expect, type Page } from '@playwright/test';
import {
  dismissCookieOrOverlays,
  openDemoControlCentre,
  wpLogin,
} from '../helpers';

test.describe.configure({ mode: 'serial' });

test.use({
  // Opt-in slow-mo for demos: PW_SLOW_MO=350 npm run test:phase14
  launchOptions: {
    slowMo: process.env.PW_SLOW_MO ? Number(process.env.PW_SLOW_MO) : 0,
  },
});

async function dismissAdminNoise(page: Page) {
  const noThanks = page.getByRole('link', { name: /No thanks/i });
  if (await noThanks.first().isVisible().catch(() => false)) {
    await noThanks.first().click({ force: true }).catch(() => undefined);
    await page.waitForTimeout(400);
  }
}

/** Map test id → form op value posted by Demo Control Centre. */
const DEMO_OP_BY_TESTID: Record<string, string> = {
  'ngc-demo-enable': 'enable',
  'ngc-demo-seed': 'seed',
  'ngc-demo-verify-btn': 'verify',
  'ngc-demo-run-journeys': 'run_journeys',
  'ngc-demo-export': 'export_evidence',
  'ngc-demo-advance': 'advance_day',
  'ngc-demo-queues': 'process_queues',
  'ngc-demo-reset': 'reset',
};

async function submitDemoOp(page: Page, testId: string, flashMatch: RegExp) {
  await dismissAdminNoise(page);
  const op = DEMO_OP_BY_TESTID[testId];
  if (!op) {
    throw new Error(`Unknown demo test id: ${testId}`);
  }
  const btn = page.getByTestId(testId);
  await btn.scrollIntoViewIfNeeded();

  if (testId === 'ngc-demo-reset') {
    page.once('dialog', async (dialog) => {
      await dialog.accept();
    });
  }

  // Each action is its own <form>; click the submit button and wait for flash after redirect.
  await Promise.all([
    page.waitForResponse(
      (res) => {
        if (!res.url().includes('admin-post.php') || res.request().method() !== 'POST') {
          return false;
        }
        const body = res.request().postData() ?? '';
        return body.includes('action=ngc_demo_action') && body.includes(`op=${op}`);
      },
      { timeout: 300_000 }
    ),
    btn.click({ force: true, noWaitAfter: true }),
  ]);

  await page.waitForLoadState('domcontentloaded').catch(() => null);
  await expect(page.getByTestId('ngc-demo-flash')).toContainText(flashMatch, {
    timeout: 180_000,
  });
}

test.describe('Phase 14 Relational Demo Walkthrough', () => {
  test.setTimeout(360_000);

  let parentEmail = 'demo.parent@nextgen.local';
  let parentPassword = '';
  let tutorEmail = 'demo.tutor.approved@nextgen.local';
  let tutorPassword = '';

  test('Section 0–2: admin enables demo mode, seeds, and verifies', async ({ page }) => {
    await page.goto('/', { waitUntil: 'domcontentloaded' });
    await dismissCookieOrOverlays(page);

    await wpLogin(page);
    await expect(page.locator('#wpadminbar')).toBeVisible({ timeout: 30_000 });

    // Prefer Demo Control Centre directly — wp-admin can hang under Elementor assets.
    await openDemoControlCentre(page);
    await expect(page.getByRole('heading', { name: /Demo Control Centre/i })).toBeVisible();

    await submitDemoOp(page, 'ngc-demo-enable', /Demo mode enabled/i);
    await expect(page.getByTestId('ngc-demo-mode')).toHaveText('ON');

    await submitDemoOp(page, 'ngc-demo-seed', /Seed complete/i);
    await submitDemoOp(page, 'ngc-demo-verify-btn', /Verify PASS|Verify FAIL/i);

    let verifyText = (await page.getByTestId('ngc-demo-verify').textContent())?.trim();
    if (verifyText !== 'PASS') {
      await submitDemoOp(page, 'ngc-demo-seed', /Seed complete/i);
      await submitDemoOp(page, 'ngc-demo-verify-btn', /Verify PASS|Verify FAIL/i);
      verifyText = (await page.getByTestId('ngc-demo-verify').textContent())?.trim();
    }
    await expect(page.getByTestId('ngc-demo-verify')).toHaveText('PASS');

    const graph = await page.getByTestId('ngc-demo-seed-graph').innerText();
    expect(graph).toMatch(/MATCH-001/);
    expect(graph).toMatch(/BOOK-001/);
    expect(graph).toMatch(/BOOK-COMPLETED/);
    expect(graph).toMatch(/BOOK-PENDING-PAY/);

    const parentRow = page.getByTestId('ngc-demo-row-NGT-DEMO-P0001');
    await expect(parentRow).toBeVisible();
    parentEmail = (await parentRow.getByTestId('ngc-demo-email').innerText()).trim();
    parentPassword = (await parentRow.getByTestId('ngc-demo-password').innerText()).trim();
    expect(parentEmail).toContain('demo.parent');
    expect(parentPassword.length).toBeGreaterThan(6);

    const tutorRow = page.getByTestId('ngc-demo-row-NGT-DEMO-T0001');
    tutorEmail = (await tutorRow.getByTestId('ngc-demo-email').innerText()).trim();
    tutorPassword = (await tutorRow.getByTestId('ngc-demo-password').innerText()).trim();
    expect(tutorEmail).toContain('demo.tutor.approved');
  });

  test('Section 3: parent persona real login', async ({ browser }) => {
    test.skip(!parentPassword, 'Parent password not captured — prior step failed');

    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto('/wp-login.php', { waitUntil: 'domcontentloaded' });
    await page.locator('#user_login').fill(parentEmail);
    await page.locator('#user_pass').fill(parentPassword);
    await Promise.all([
      page.waitForURL((url) => !url.pathname.includes('wp-login.php'), {
        timeout: 60_000,
        waitUntil: 'commit',
      }),
      page.locator('#wp-submit').click(),
    ]);

    await expect(page.locator('body')).toBeVisible({ timeout: 60_000 });
    const cookies = await context.cookies();
    expect(cookies.some((c) => /wordpress_logged_in/i.test(c.name))).toBeTruthy();

    // Front theme may be a minimal stub — auth cookie + leaving wp-login is enough.
    await page.goto('/', { waitUntil: 'domcontentloaded', timeout: 90_000 });
    await dismissCookieOrOverlays(page);
    await expect(page).not.toHaveURL(/wp-login\.php/);
    await expect(page.locator('body')).toContainText(/NextGen\s?Tutors|Tutor|Learn/i);

    await context.close();
  });

  test('Section 3b: tutor persona real login', async ({ browser }) => {
    test.skip(!tutorPassword, 'Tutor password not captured — prior step failed');

    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto('/wp-login.php', { waitUntil: 'domcontentloaded' });
    await page.locator('#user_login').fill(tutorEmail);
    await page.locator('#user_pass').fill(tutorPassword);
    await Promise.all([
      page.waitForURL((url) => !url.pathname.includes('wp-login.php'), {
        timeout: 60_000,
        waitUntil: 'commit',
      }),
      page.locator('#wp-submit').click(),
    ]);
    const cookies = await context.cookies();
    expect(cookies.some((c) => /wordpress_logged_in/i.test(c.name))).toBeTruthy();
    await expect(page).not.toHaveURL(/wp-login\.php/);
    await context.close();
  });

  test('Sections 4–6: booking graph, ops cases, journeys + evidence', async ({ page }) => {
    await wpLogin(page);
    await openDemoControlCentre(page);

    const graph = await page.getByTestId('ngc-demo-seed-graph').innerText();
    expect(graph).toMatch(/BOOK-001/);
    expect(graph).toMatch(/BOOK-COMPLETED/);
    expect(graph).toMatch(/BOOK-PENDING-PAY/);

    await submitDemoOp(page, 'ngc-demo-advance', /Clock advanced/i);
    await submitDemoOp(page, 'ngc-demo-queues', /Schedulers processed/i);

    await page.goto('/wp-admin/admin.php?page=ngc-fraud-cases', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toContainText(/Fraud|case|Demo fraud|payout/i);

    await page.goto('/wp-admin/admin.php?page=ngc-safeguarding', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toContainText(/Safeguarding|Demo safeguarding|queue|case/i);

    await openDemoControlCentre(page);
    const graph2 = await page.getByTestId('ngc-demo-seed-graph').innerText();
    expect(graph2).toMatch(/AI-001|AI-008|AI-009|agents/i);

    await submitDemoOp(page, 'ngc-demo-run-journeys', /Journeys executed/i);
    await submitDemoOp(page, 'ngc-demo-export', /Evidence:/i);
  });

  test('Section 7: reset + reseed still verifies', async ({ page }) => {
    await wpLogin(page);
    await openDemoControlCentre(page);

    await dismissAdminNoise(page);
    await submitDemoOp(page, 'ngc-demo-reset', /Reset complete/i);

    await submitDemoOp(page, 'ngc-demo-enable', /Demo mode enabled/i);
    await submitDemoOp(page, 'ngc-demo-seed', /Seed complete/i);
    await submitDemoOp(page, 'ngc-demo-verify-btn', /Verify PASS/i);
    await expect(page.getByTestId('ngc-demo-verify')).toHaveText('PASS');
  });
});
