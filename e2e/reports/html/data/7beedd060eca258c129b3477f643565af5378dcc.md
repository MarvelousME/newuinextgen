# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: phase14-demo-walkthrough.spec.ts >> Phase 14 Relational Demo Walkthrough >> Sections 4–6: booking graph, ops cases, journeys + evidence
- Location: workflows\phase14-demo-walkthrough.spec.ts:182:18

# Error details

```
Test timeout of 360000ms exceeded.
```

```
Error: page.waitForResponse: Test timeout of 360000ms exceeded.
```

# Test source

```ts
  1   | /**
  2   |  * Phase 14 Relational Demo Walkthrough — headed E2E demonstration.
  3   |  *
  4   |  * Mirrors documentation/tutorials/01-phase14-demo-walkthrough.md
  5   |  *
  6   |  * Run headed:
  7   |  *   cd e2e && npm run test:phase14
  8   |  */
  9   | import { test, expect, type Page } from '@playwright/test';
  10  | import {
  11  |   dismissCookieOrOverlays,
  12  |   openDemoControlCentre,
  13  |   wpLogin,
  14  | } from '../helpers';
  15  | 
  16  | test.describe.configure({ mode: 'serial' });
  17  | 
  18  | test.use({
  19  |   // Opt-in slow-mo for demos: PW_SLOW_MO=350 npm run test:phase14
  20  |   launchOptions: {
  21  |     slowMo: process.env.PW_SLOW_MO ? Number(process.env.PW_SLOW_MO) : 0,
  22  |   },
  23  | });
  24  | 
  25  | async function dismissAdminNoise(page: Page) {
  26  |   const noThanks = page.getByRole('link', { name: /No thanks/i });
  27  |   if (await noThanks.first().isVisible().catch(() => false)) {
  28  |     await noThanks.first().click({ force: true }).catch(() => undefined);
  29  |     await page.waitForTimeout(400);
  30  |   }
  31  | }
  32  | 
  33  | /** Map test id → form op value posted by Demo Control Centre. */
  34  | const DEMO_OP_BY_TESTID: Record<string, string> = {
  35  |   'ngc-demo-enable': 'enable',
  36  |   'ngc-demo-seed': 'seed',
  37  |   'ngc-demo-verify-btn': 'verify',
  38  |   'ngc-demo-run-journeys': 'run_journeys',
  39  |   'ngc-demo-export': 'export_evidence',
  40  |   'ngc-demo-advance': 'advance_day',
  41  |   'ngc-demo-queues': 'process_queues',
  42  |   'ngc-demo-reset': 'reset',
  43  | };
  44  | 
  45  | async function submitDemoOp(page: Page, testId: string, flashMatch: RegExp) {
  46  |   await dismissAdminNoise(page);
  47  |   const op = DEMO_OP_BY_TESTID[testId];
  48  |   if (!op) {
  49  |     throw new Error(`Unknown demo test id: ${testId}`);
  50  |   }
  51  |   const btn = page.getByTestId(testId);
  52  |   await btn.scrollIntoViewIfNeeded();
  53  | 
  54  |   if (testId === 'ngc-demo-reset') {
  55  |     page.once('dialog', async (dialog) => {
  56  |       await dialog.accept();
  57  |     });
  58  |   }
  59  | 
  60  |   // Each action is its own <form>; click the submit button and wait for flash after redirect.
> 61  |   await Promise.all([
      |                           ^ Error: page.waitForResponse: Test timeout of 360000ms exceeded.
  62  |     page.waitForResponse(
  63  |       (res) => {
  64  |         if (!res.url().includes('admin-post.php') || res.request().method() !== 'POST') {
  65  |           return false;
  66  |         }
  67  |         const body = res.request().postData() ?? '';
  68  |         return body.includes('action=ngc_demo_action') && body.includes(`op=${op}`);
  69  |       },
  70  |       { timeout: 300_000 }
  71  |     ),
  72  |     btn.click({ force: true, noWaitAfter: true }),
  73  |   ]);
  74  | 
  75  |   await page.waitForLoadState('domcontentloaded').catch(() => null);
  76  |   await expect(page.getByTestId('ngc-demo-flash')).toContainText(flashMatch, {
  77  |     timeout: 180_000,
  78  |   });
  79  | }
  80  | 
  81  | test.describe('Phase 14 Relational Demo Walkthrough', () => {
  82  |   test.setTimeout(360_000);
  83  | 
  84  |   let parentEmail = 'demo.parent@nextgen.local';
  85  |   let parentPassword = '';
  86  |   let tutorEmail = 'demo.tutor.approved@nextgen.local';
  87  |   let tutorPassword = '';
  88  | 
  89  |   test('Section 0–2: admin enables demo mode, seeds, and verifies', async ({ page }) => {
  90  |     await page.goto('/', { waitUntil: 'domcontentloaded' });
  91  |     await dismissCookieOrOverlays(page);
  92  | 
  93  |     await wpLogin(page);
  94  |     await expect(page.locator('#wpadminbar')).toBeVisible({ timeout: 30_000 });
  95  | 
  96  |     // Prefer Demo Control Centre directly — wp-admin can hang under Elementor assets.
  97  |     await openDemoControlCentre(page);
  98  |     await expect(page.getByRole('heading', { name: /Demo Control Centre/i })).toBeVisible();
  99  | 
  100 |     await submitDemoOp(page, 'ngc-demo-enable', /Demo mode enabled/i);
  101 |     await expect(page.getByTestId('ngc-demo-mode')).toHaveText('ON');
  102 | 
  103 |     await submitDemoOp(page, 'ngc-demo-seed', /Seed complete/i);
  104 |     await submitDemoOp(page, 'ngc-demo-verify-btn', /Verify PASS|Verify FAIL/i);
  105 | 
  106 |     let verifyText = (await page.getByTestId('ngc-demo-verify').textContent())?.trim();
  107 |     if (verifyText !== 'PASS') {
  108 |       await submitDemoOp(page, 'ngc-demo-seed', /Seed complete/i);
  109 |       await submitDemoOp(page, 'ngc-demo-verify-btn', /Verify PASS|Verify FAIL/i);
  110 |       verifyText = (await page.getByTestId('ngc-demo-verify').textContent())?.trim();
  111 |     }
  112 |     await expect(page.getByTestId('ngc-demo-verify')).toHaveText('PASS');
  113 | 
  114 |     const graph = await page.getByTestId('ngc-demo-seed-graph').innerText();
  115 |     expect(graph).toMatch(/MATCH-001/);
  116 |     expect(graph).toMatch(/BOOK-001/);
  117 |     expect(graph).toMatch(/BOOK-COMPLETED/);
  118 |     expect(graph).toMatch(/BOOK-PENDING-PAY/);
  119 | 
  120 |     const parentRow = page.getByTestId('ngc-demo-row-NGT-DEMO-P0001');
  121 |     await expect(parentRow).toBeVisible();
  122 |     parentEmail = (await parentRow.getByTestId('ngc-demo-email').innerText()).trim();
  123 |     parentPassword = (await parentRow.getByTestId('ngc-demo-password').innerText()).trim();
  124 |     expect(parentEmail).toContain('demo.parent');
  125 |     expect(parentPassword.length).toBeGreaterThan(6);
  126 | 
  127 |     const tutorRow = page.getByTestId('ngc-demo-row-NGT-DEMO-T0001');
  128 |     tutorEmail = (await tutorRow.getByTestId('ngc-demo-email').innerText()).trim();
  129 |     tutorPassword = (await tutorRow.getByTestId('ngc-demo-password').innerText()).trim();
  130 |     expect(tutorEmail).toContain('demo.tutor.approved');
  131 |   });
  132 | 
  133 |   test('Section 3: parent persona real login', async ({ browser }) => {
  134 |     test.skip(!parentPassword, 'Parent password not captured — prior step failed');
  135 | 
  136 |     const context = await browser.newContext();
  137 |     const page = await context.newPage();
  138 |     await page.goto('/wp-login.php', { waitUntil: 'domcontentloaded' });
  139 |     await page.locator('#user_login').fill(parentEmail);
  140 |     await page.locator('#user_pass').fill(parentPassword);
  141 |     await Promise.all([
  142 |       page.waitForURL((url) => !url.pathname.includes('wp-login.php'), {
  143 |         timeout: 60_000,
  144 |         waitUntil: 'commit',
  145 |       }),
  146 |       page.locator('#wp-submit').click(),
  147 |     ]);
  148 | 
  149 |     await expect(page.locator('body')).toBeVisible({ timeout: 60_000 });
  150 |     const cookies = await context.cookies();
  151 |     expect(cookies.some((c) => /wordpress_logged_in/i.test(c.name))).toBeTruthy();
  152 | 
  153 |     // Front theme may be a minimal stub — auth cookie + leaving wp-login is enough.
  154 |     await page.goto('/', { waitUntil: 'domcontentloaded', timeout: 90_000 });
  155 |     await dismissCookieOrOverlays(page);
  156 |     await expect(page).not.toHaveURL(/wp-login\.php/);
  157 |     await expect(page.locator('body')).toContainText(/NextGen\s?Tutors|Tutor|Learn/i);
  158 | 
  159 |     await context.close();
  160 |   });
  161 | 
```