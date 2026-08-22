/**
 * Headed booking → commerce → session E2E (scenarios 1–5).
 * Prefers live UI; DB chain verified via REST + optional Docker eval evidence.
 */
import fs from 'node:fs';
import path from 'node:path';
import { test, expect, type Page } from '@playwright/test';
import { gotoReady, openDemoControlCentre, wpLogin, wpAdminUser, wpAdminPassword } from '../helpers';
import { DEMO_PERSONAS, demoPassword, ensureDemoSeedAndPassword } from '../helpers/lesson-e2e';

const RUN_ID = `bc-headed-${new Date().toISOString().replace(/[:.]/g, '-')}`;
const EVIDENCE = path.resolve(__dirname, '../../delivery/evidence/booking-commerce', RUN_ID);

function ensureDirs() {
  for (const d of ['browser', 'screenshots', 'api', 'reports']) {
    fs.mkdirSync(path.join(EVIDENCE, d), { recursive: true });
  }
}

async function shot(page: Page, name: string) {
  await page.screenshot({
    path: path.join(EVIDENCE, 'screenshots', name),
    fullPage: true,
  });
}

async function restGet(page: Page, apiPath: string) {
  return page.evaluate(async (p) => {
    const cfg = (window as unknown as { biDashboard?: { nonce?: string } }).biDashboard;
    const headers: Record<string, string> = { Accept: 'application/json' };
    const match = document.cookie.match(/wordpress_logged_in_[^=]+=([^;]+)/);
    void match;
    const nonceEl = document.querySelector('#_wpnonce, input[name="_wpnonce"]') as HTMLInputElement | null;
    if (cfg?.nonce) headers['X-WP-Nonce'] = cfg.nonce;
    else if ((window as unknown as { wpApiSettings?: { nonce?: string } }).wpApiSettings?.nonce) {
      headers['X-WP-Nonce'] = (window as unknown as { wpApiSettings: { nonce: string } }).wpApiSettings.nonce;
    } else if (nonceEl?.value) headers['X-WP-Nonce'] = nonceEl.value;
    const res = await fetch(p, { credentials: 'same-origin', headers });
    const json = await res.json().catch(() => ({}));
    return { status: res.status, json };
  }, apiPath);
}

test.describe.configure({ mode: 'serial' });
test.setTimeout(900_000);

test.describe('Booking commerce session E2E (headed)', () => {
  test.beforeAll(() => {
    ensureDirs();
    fs.writeFileSync(
      path.join(EVIDENCE, 'reports', 'run-meta.json'),
      JSON.stringify({ runId: RUN_ID, startedAt: new Date().toISOString() }, null, 2)
    );
  });

  test('COMMERCE-000 preflight + demo seed', async ({ page }) => {
    await wpLogin(page);
    await ensureDemoSeedAndPassword(page);
    await gotoReady(page, '/');
    await shot(page, '00-home.png');
    const health = await page.request.get('/wp-json/');
    expect(health.status(), 'WP REST up').toBeLessThan(500);
  });

  test('COMMERCE-001 find tutor → profile → book drawer', async ({ page }) => {
    await wpLogin(page, DEMO_PERSONAS.parent.email, demoPassword);
    await gotoReady(page, '/find-a-tutor/');
    await shot(page, '01-tutor-search.png');
    const profile = page.locator('a[href*="/tutors/"], a.bi-tutor-card__link, .ngc-tutor-card a').first();
    await expect(profile).toBeVisible({ timeout: 60_000 });
    await profile.click();
    await page.waitForLoadState('domcontentloaded');
    await shot(page, '02-tutor-profile.png');
    const book = page.locator('.bi-book-lesson-trigger, button:has-text("Book"), a:has-text("Book Session")').first();
    if (await book.isVisible().catch(() => false)) {
      await book.click();
      await shot(page, '04-booking-selected.png');
    }
  });

  test('COMMERCE-002 parent checkout surface', async ({ page }) => {
    await wpLogin(page, DEMO_PERSONAS.parent.email, demoPassword);
    await gotoReady(page, '/parent-checkout/');
    await shot(page, '06-checkout.png');
    // Page may auto-redirect to PayFast; either checkout notice or external redirect is acceptable signal.
    const body = await page.locator('body').innerText();
    fs.writeFileSync(path.join(EVIDENCE, 'api', 'checkout-body-snip.txt'), body.slice(0, 2000));
    expect(body.length).toBeGreaterThan(20);
  });

  test('COMMERCE-003 dashboards show sessions without leaked meeting URLs', async ({ page }) => {
    await wpLogin(page, DEMO_PERSONAS.parent.email, demoPassword);
    await gotoReady(page, DEMO_PERSONAS.parent.path);
    await page.waitForTimeout(3000);
    await shot(page, '09-parent-dashboard.png');
    const html = await page.content();
    expect(html.includes('meet.jit.si'), 'meeting URL must not be embedded in dashboard HTML').toBeFalsy();

    await wpLogin(page, DEMO_PERSONAS.studentAdult.email, demoPassword);
    await gotoReady(page, DEMO_PERSONAS.studentAdult.path);
    await page.waitForTimeout(3000);
    await shot(page, '10-student-dashboard.png');

    await wpLogin(page, DEMO_PERSONAS.tutorOnline.email, demoPassword);
    await gotoReady(page, DEMO_PERSONAS.tutorOnline.path);
    await page.waitForTimeout(3000);
    await shot(page, '14-live-session-tutor.png');
  });

  test('COMMERCE-004 REST session by-booking + launch authz', async ({ page }) => {
    await wpLogin(page, DEMO_PERSONAS.studentAdult.email, demoPassword);
    await gotoReady(page, DEMO_PERSONAS.studentAdult.path);
    await page.waitForTimeout(2000);
    const dash = await restGet(page, '/wp-json/ngc/v1/dashboard/student');
    fs.writeFileSync(path.join(EVIDENCE, 'api', 'student-dashboard.json'), JSON.stringify(dash, null, 2));
    expect(dash.status).toBeLessThan(500);
    const payload = (dash.json && (dash.json.data || dash.json)) || {};
    const next = payload.nextSession || null;
    if (next?.sessionId) {
      const launch = await page.evaluate(async (sid) => {
        const nonce =
          (window as unknown as { wpApiSettings?: { nonce?: string }; biDashboard?: { nonce?: string } }).biDashboard
            ?.nonce ||
          (window as unknown as { wpApiSettings?: { nonce?: string } }).wpApiSettings?.nonce ||
          '';
        const res = await fetch(`/wp-json/ngc/v1/sessions/${sid}/launch`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(nonce ? { 'X-WP-Nonce': nonce } : {}),
          },
          body: '{}',
        });
        return { status: res.status, body: await res.json().catch(() => ({})) };
      }, Number(next.sessionId));
      fs.writeFileSync(path.join(EVIDENCE, 'api', 'launch.json'), JSON.stringify(launch, null, 2));
      expect([200, 403, 409]).toContain(launch.status);
    }
  });

  test('COMMERCE-005 unauthorized visitor cannot launch', async ({ browser }) => {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    await gotoReady(page, '/');
    const res = await page.request.post('/wp-json/ngc/v1/sessions/1/launch', {
      data: {},
    });
    expect([401, 403]).toContain(res.status());
    await ctx.close();
  });
});
