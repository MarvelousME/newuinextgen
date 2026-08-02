import { expect, type Locator, type Page, type Response } from '@playwright/test';

export const testEmail = (tag: string) =>
  `e2e.${tag}.${Date.now()}@nextgentutors.test`;

export const wpAdminUser = process.env.WP_ADMIN_USER || 'admin';
export const wpAdminPassword = process.env.WP_ADMIN_PASSWORD || 'NextGenAdmin!2026';

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

/** Navigate with domcontentloaded — Elementor pages often never reach full `load`. */
export async function gotoReady(page: Page, path: string) {
  await page.goto(path, { waitUntil: 'domcontentloaded', timeout: 90_000 });
  await dismissCookieOrOverlays(page);
}

/** Log into wp-admin with local Docker defaults (override via env). */
export async function wpLogin(page: Page, user = wpAdminUser, password = wpAdminPassword) {
  await page.goto('/wp-login.php', { waitUntil: 'domcontentloaded' });
  await page.locator('#user_login').fill(user);
  await page.locator('#user_pass').fill(password);
  await Promise.all([
    // Theme login_redirect sends admins to /admin-dashboard/, not /wp-admin/.
    page.waitForURL(/\/(wp-admin|admin-dashboard)(\/|$|\?)/, {
      timeout: 60_000,
      waitUntil: 'domcontentloaded',
    }),
    page.locator('#wp-submit').click(),
  ]);
}

export async function wpLogout(page: Page) {
  await page.goto('/wp-login.php?action=logout', { waitUntil: 'domcontentloaded' });
  const confirm = page.getByRole('link', { name: /log out/i });
  if (await confirm.isVisible().catch(() => false)) {
    await Promise.all([
      page.waitForURL(/wp-login\.php/, { timeout: 15_000 }),
      confirm.click(),
    ]);
  }
}

export async function openDemoControlCentre(page: Page) {
  await page.goto('/wp-admin/admin.php?page=ngc-demo-control', {
    waitUntil: 'domcontentloaded',
    timeout: 120_000,
  });
  await expect(page.getByTestId('ngc-demo-control')).toBeVisible({ timeout: 30_000 });
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

/**
 * Submit via HTMLFormElement.submit() so ngc-validation cannot preventDefault.
 * Returns the admin-post redirect response (Location includes ngc_submitted).
 */
export async function submitNgForm(page: Page, form?: Locator): Promise<Response | null> {
  const target = form ?? primaryNgForm(page);
  await target.scrollIntoViewIfNeeded();

  const responsePromise = page.waitForResponse(
    (r) => r.url().includes('admin-post.php') && r.status() >= 300 && r.status() < 400,
    { timeout: 60_000 }
  );

  await target.evaluate((el) => (el as HTMLFormElement).submit());

  let response: Response | null = null;
  try {
    response = await responsePromise;
  } catch {
    response = null;
  }

  await page.waitForLoadState('domcontentloaded').catch(() => null);
  return response;
}

/**
 * Confirm submission. Toast JS strips ?ngc_submitted= from the URL via replaceState,
 * so we assert on the redirect Location and/or the success toast.
 */
export async function expectFormSubmitted(
  page: Page,
  formId: string,
  adminPostResponse?: Response | null
) {
  if (adminPostResponse) {
    const location = adminPostResponse.headers()['location'] || '';
    expect(location).toContain(`ngc_submitted=${formId}`);
  }

  const toast = page.locator('#ngt-toast');
  await expect(toast).toContainText(/thank you|submitted successfully/i, {
    timeout: 20_000,
  });
}

export async function openHomeBookingModal(page: Page) {
  await gotoReady(page, '/');
  const cta = page.locator('[data-ngi-open]:not(.ngi-sticky), [data-ngt-open-booking], .ngi-btn').first();
  if (!(await cta.isVisible().catch(() => false))) {
    await expect(page.locator('body')).toContainText(/NextGen|Tutor|Book|Learn/i);
    return;
  }
  await cta.scrollIntoViewIfNeeded();
  await cta.click({ force: true });
  const modal = page.locator('#ngiBookingModal, [role="dialog"], .ngi-modal');
  if (await modal.first().isVisible().catch(() => false)) {
    await expect(modal.first()).toBeVisible();
  }
}

/** Marketing / auth pages used by full-system headed verification. */
export const PUBLIC_SYSTEM_PAGES: Array<{
  path: string;
  name: string;
  mustMatch: RegExp;
  landmark?: string;
}> = [
  { path: '/', name: 'Home', mustMatch: /Tutor|NextGen|Pace|Learn/i, landmark: 'h1' },
  { path: '/about/', name: 'About', mustMatch: /South African|Tutor|NextGen|exist/i, landmark: 'h1' },
  { path: '/find-a-tutor/', name: 'Find a Tutor', mustMatch: /Find|Tutor|Match|Subject/i, landmark: 'h1' },
  { path: '/become-a-tutor/', name: 'Become a Tutor', mustMatch: /Become|Tutor|Earn|Teach/i, landmark: 'h1' },
  { path: '/pricing/', name: 'Pricing', mustMatch: /Price|Package|R\s*\d|Plan|Rate/i, landmark: 'h1' },
  { path: '/login/', name: 'Login', mustMatch: /Sign in|Login|Continue as|Parent|Tutor/i },
  { path: '/register/', name: 'Register', mustMatch: /Register|Parent|Student|Create/i },
  { path: '/contact/', name: 'Contact', mustMatch: /Contact|Support|Message|Help/i },
  { path: '/guarantee/', name: 'Guarantee', mustMatch: /Guarantee|Lesson|Risk|First/i, landmark: 'h1' },
];

const IGNORED_CONSOLE =
  /Download the React DevTools|third-party|facebook|googletagmanager|clarity\.ms|hotjar|Failed to load resource: net::ERR_|ResizeObserver loop|Non-Error promise rejection/i;

/**
 * Attach a console / pageerror collector. Call before navigation.
 * Returns a getter plus a dispose() that removes listeners (avoids stacking in loops).
 */
export function attachConsoleGuard(page: Page): {
  getFatals: () => string[];
  dispose: () => void;
} {
  const fatals: string[] = [];
  const onPageError = (err: Error) => {
    const msg = String(err?.message || err);
    if (!IGNORED_CONSOLE.test(msg)) fatals.push(`pageerror: ${msg}`);
  };
  const onConsole = (msg: { type: () => string; text: () => string }) => {
    if (msg.type() !== 'error') return;
    const text = msg.text();
    if (IGNORED_CONSOLE.test(text)) return;
    if (/favicon|fonts\.googleapis|cdn\.|404/i.test(text)) return;
    fatals.push(`console.error: ${text}`);
  };
  page.on('pageerror', onPageError);
  page.on('console', onConsole);
  return {
    getFatals: () => [...fatals],
    dispose: () => {
      page.off('pageerror', onPageError);
      page.off('console', onConsole);
    },
  };
}

/** Soft assert: no unexpected pageerrors (console noise alone does not fail). */
export function expectNoPageErrors(fatals: string[]) {
  const pageErrors = fatals.filter((f) => f.startsWith('pageerror:'));
  expect(pageErrors, pageErrors.join('\n')).toEqual([]);
}

export async function expectPageHealthy(
  page: Page,
  path: string,
  opts: { mustMatch: RegExp; landmark?: string; name?: string }
) {
  const guard = attachConsoleGuard(page);
  try {
    let res = null as Awaited<ReturnType<Page['goto']>>;
    try {
      res = await page.goto(path, { waitUntil: 'domcontentloaded', timeout: 90_000 });
    } catch (first) {
      // Slow WP / aborted navigations — one retry on a fresh load.
      await page.waitForTimeout(1_000);
      res = await page.goto(path, { waitUntil: 'domcontentloaded', timeout: 90_000 });
      if (!res) throw first;
    }
    expect(res, `${opts.name || path} should respond`).toBeTruthy();
    const status = res!.status();
    expect(status, `${opts.name || path} HTTP status`).toBeLessThan(500);
    await dismissCookieOrOverlays(page);

    const bodyText = (await page.locator('body').innerText().catch(() => '')) || '';
    if (status === 404 || /page can.?t be found|not found/i.test(bodyText.slice(0, 400))) {
      throw new Error(`${opts.name || path} returned not found`);
    }

    if (opts.landmark) {
      await expect(page.locator(opts.landmark).first()).toBeVisible({ timeout: 30_000 });
    }
    await expect(page.locator('body')).toContainText(opts.mustMatch, { timeout: 30_000 });
    expectNoPageErrors(guard.getFatals());
  } finally {
    guard.dispose();
  }
}
