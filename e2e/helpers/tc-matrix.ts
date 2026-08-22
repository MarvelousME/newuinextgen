/**
 * TC matrix helpers for headed E2E (BASE_URL default :8890).
 */
import fs from 'node:fs';
import path from 'node:path';
import { expect, test, type APIRequestContext, type Page, type TestInfo } from '@playwright/test';
import {
  dismissCookieOrOverlays,
  expectFormSubmitted,
  fillNgForm,
  gotoReady,
  primaryNgForm,
  submitNgForm,
  testEmail,
  wpAdminPassword,
  wpAdminUser,
  wpLogin,
} from '../helpers';
import { DEMO_PERSONAS, demoPassword } from './lesson-e2e';

export const TC_EVIDENCE_ROOT = path.resolve(__dirname, '../../delivery/evidence/tc-matrix');

export function tcEvidenceDir(): string {
  const id = process.env.TC_RUN_ID || new Date().toISOString().replace(/[:.]/g, '-');
  const dir = path.join(TC_EVIDENCE_ROOT, id);
  fs.mkdirSync(path.join(dir, 'screenshots'), { recursive: true });
  fs.mkdirSync(path.join(dir, 'notes'), { recursive: true });
  return dir;
}

export async function tcShot(page: Page, dir: string, name: string) {
  await page.screenshot({ path: path.join(dir, 'screenshots', name), fullPage: true }).catch(() => undefined);
}

export function annotateTc(testInfo: TestInfo, id: string, title: string) {
  testInfo.annotations.push({ type: 'tc', description: `${id}: ${title}` });
}

export function deepCrmEnabled() {
  return process.env.E2E_DEEP_CRM === '1';
}

export function payfastSettleEnabled() {
  return process.env.E2E_PAYFAST_SETTLE === '1';
}

export async function loginPersona(page: Page, email: string, password = demoPassword) {
  await page.goto('/wp-login.php', { waitUntil: 'domcontentloaded', timeout: 120_000 });
  await page.locator('#user_login').fill(email);
  await page.locator('#user_pass').fill(password);
  await Promise.all([
    page.waitForURL((u) => !u.pathname.includes('wp-login.php'), { timeout: 90_000, waitUntil: 'commit' }),
    page.locator('#wp-submit').click(),
  ]);
  const cookies = await page.context().cookies();
  expect(cookies.some((c) => /wordpress_logged_in/i.test(c.name)), `login ${email}`).toBeTruthy();
}

export async function ensureSiteUp(request: APIRequestContext) {
  // Prefer localhost — WP often 301s 127.0.0.1 → localhost.
  // Soft-skip when the stack is down so the matrix stays runnable offline.
  const bases = [
    (process.env.BASE_URL || 'http://localhost:8890').replace(/127\.0\.0\.1/g, 'localhost'),
    'http://localhost:8890',
  ];
  for (const base of [...new Set(bases)]) {
    try {
      const res = await request.get(base.replace(/\/$/, '') + '/', {
        maxRedirects: 0,
        timeout: 12_000,
      });
      // 200 OK or WP 301 to canonical host both count as "up".
      if (res.status() > 0 && res.status() < 500) {
        return;
      }
    } catch {
      // try next
    }
  }
  test.skip(true, `WordPress not reachable on ${bases[0]} — start docker stack, then re-run headed TC matrix`);
}

export function note(dir: string, file: string, body: unknown) {
  fs.writeFileSync(path.join(dir, 'notes', file), typeof body === 'string' ? body : JSON.stringify(body, null, 2));
}

export {
  DEMO_PERSONAS,
  demoPassword,
  dismissCookieOrOverlays,
  expectFormSubmitted,
  fillNgForm,
  gotoReady,
  primaryNgForm,
  submitNgForm,
  testEmail,
  wpAdminPassword,
  wpAdminUser,
  wpLogin,
};
