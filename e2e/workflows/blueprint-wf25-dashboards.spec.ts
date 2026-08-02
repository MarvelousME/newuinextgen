/**
 * WF-25 Dashboards — auth gates + seeded demo persona shells (headed).
 *
 * Prefers NGC_DEMO_PASSWORD (or fetches from env). After `wp ngc demo_seed`:
 *   parent: demo.parent@nextgen.local
 *   tutor:  demo.tutor.approved@nextgen.local
 *   student: demo.student.adult@nextgen.local
 */
import { test, expect, type Page } from '@playwright/test';
import {
  dismissCookieOrOverlays,
  wpAdminPassword,
  wpAdminUser,
  wpLogin,
  wpLogout,
} from '../helpers';

const demoPassword = process.env.NGC_DEMO_PASSWORD || process.env.DEMO_PASSWORD || 'NgtDemo!09a2b917';

const personas = {
  parent: { email: 'demo.parent@nextgen.local', path: '/parent-dashboard/', match: /parent|dashboard|booking|child|learner|session|NextGen/i },
  tutor: { email: 'demo.tutor.approved@nextgen.local', path: '/tutor-dashboard/', match: /tutor|dashboard|earning|session|schedule|NextGen/i },
  student: { email: 'demo.student.adult@nextgen.local', path: '/student-dashboard/', match: /student|dashboard|lesson|progress|NextGen/i },
} as const;

async function loginAs(page: Page, email: string, password: string) {
  await page.goto('/wp-login.php', { waitUntil: 'domcontentloaded' });
  await page.locator('#user_login').fill(email);
  await page.locator('#user_pass').fill(password);
  await Promise.all([
    page.waitForURL((url) => !url.pathname.includes('wp-login.php'), {
      timeout: 60_000,
      waitUntil: 'commit',
    }),
    page.locator('#wp-submit').click(),
  ]);
  const cookies = await page.context().cookies();
  expect(
    cookies.some((c) => /wordpress_logged_in/i.test(c.name)),
    `login cookie for ${email}`
  ).toBeTruthy();
}

async function expectDashboardShell(page: Page, path: string, match: RegExp) {
  await page.goto(path, { waitUntil: 'domcontentloaded', timeout: 90_000 });
  await dismissCookieOrOverlays(page);
  await expect(page).not.toHaveURL(/wp-login\.php/);
  await expect(page.locator('body')).toBeVisible();
  const text = await page.locator('body').innerText();
  expect(match.test(text), `dashboard shell at ${path}`).toBeTruthy();
  // Prefer real dashboard landmarks when present.
  const shell = page.locator(
    '.bi-dashboard, .ngt-dashboard, [data-dashboard], .bi-admin-dashboard, main, .bi-theme-content'
  ).first();
  await expect(shell).toBeVisible({ timeout: 30_000 });
}

test.describe('Blueprint WF-25 Dashboard Workflow', () => {
  test('login page exposes dashboard entry route', async ({ page }) => {
    await page.goto('/login/', { waitUntil: 'domcontentloaded' });
    await dismissCookieOrOverlays(page);
    await expect(page.locator('#bi-login-role-parent, form.bi-ngc-form, form.ngc-form, #loginform').first()).toBeVisible();
    await expect(page.locator('body')).toContainText(/log in|sign in|dashboard|Parent|Tutor/i);
  });

  test('REST student dashboard requires authentication', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/dashboard/student');
    expect([401, 403]).toContain(res.status());
  });

  test('REST parent dashboard requires authentication', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/dashboard/parent');
    expect([401, 403]).toContain(res.status());
  });

  test('REST tutor dashboard requires authentication', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/dashboard/tutor');
    expect([401, 403]).toContain(res.status());
  });

  test('REST admin dashboard requires admin', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/dashboard/admin');
    expect([401, 403]).toContain(res.status());
  });

  test('parent-dashboard route is reachable (auth gate or shell)', async ({ page }) => {
    await page.goto('/parent-dashboard/', { waitUntil: 'domcontentloaded', timeout: 90_000 });
    await dismissCookieOrOverlays(page);
    await expect(page.locator('body')).toBeVisible();
    const text = await page.locator('body').innerText();
    expect(/dashboard|log in|sign in|parent|NextGen|tutor/i.test(text)).toBeTruthy();
  });
});

test.describe('Seeded demo dashboards (headed)', () => {
  test.setTimeout(120_000);

  test('demo parent lands on parent-dashboard with live shell', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    await loginAs(page, personas.parent.email, demoPassword);
    await expectDashboardShell(page, personas.parent.path, personas.parent.match);
    await context.close();
  });

  test('demo tutor lands on tutor-dashboard with live shell', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    await loginAs(page, personas.tutor.email, demoPassword);
    await expectDashboardShell(page, personas.tutor.path, personas.tutor.match);
    await context.close();
  });

  test('demo student lands on student-dashboard with live shell', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    await loginAs(page, personas.student.email, demoPassword);
    await expectDashboardShell(page, personas.student.path, personas.student.match);
    await context.close();
  });

  test('admin dashboard shell after wp-admin login', async ({ page }) => {
    await wpLogin(page, wpAdminUser, wpAdminPassword);
    await page.goto('/admin-dashboard/', { waitUntil: 'domcontentloaded', timeout: 90_000 });
    await dismissCookieOrOverlays(page);
    await expect(page).not.toHaveURL(/wp-login\.php/);
    await expect(page.locator('body')).toContainText(/admin|dashboard|platform|NextGen|booking|tutor/i);
    await wpLogout(page);
  });

  test('parent dashboard shows seeded booking signals', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    await loginAs(page, personas.parent.email, demoPassword);
    await expectDashboardShell(page, personas.parent.path, personas.parent.match);

    const body = await page.locator('body').innerText();
    // Seed graph includes BOOK-001 / completed / pending — surface may label sessions, bookings, or wallet.
    expect(
      /booking|session|lesson|wallet|match|tutor|child|learner|schedule|upcoming/i.test(body),
      'parent dashboard should surface operational content'
    ).toBeTruthy();
    await context.close();
  });
});
