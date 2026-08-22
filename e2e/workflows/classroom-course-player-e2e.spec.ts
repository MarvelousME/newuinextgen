/**
 * Headed classroom bridge: Course Player shell → live meeting CTA.
 * Uses seeded join-window session from seed-classroom-join-window.php.
 */
import fs from 'node:fs';
import path from 'node:path';
import { test, expect } from '@playwright/test';
import { gotoReady, wpLogin } from '../helpers';
import { DEMO_PERSONAS, demoPassword, ensureDemoSeedAndPassword } from '../helpers/lesson-e2e';

const RUN_ID = `classroom-${new Date().toISOString().replace(/[:.]/g, '-')}`;
const EVIDENCE = path.resolve(__dirname, '../../delivery/evidence/booking-commerce', RUN_ID);
const SEED = path.resolve(
  __dirname,
  '../../NextGenTutors-Companion/evidence/booking-commerce/classroom-seed/latest.json'
);

function ensureDirs() {
  fs.mkdirSync(path.join(EVIDENCE, 'screenshots'), { recursive: true });
  fs.mkdirSync(path.join(EVIDENCE, 'api'), { recursive: true });
}

test.describe.configure({ mode: 'serial' });
test.setTimeout(600_000);

test.describe('Classroom Course Player → live meeting (headed)', () => {
  test.beforeAll(() => {
    ensureDirs();
  });

  test('CLASSROOM-001 seed present + student classroom', async ({ page, context }) => {
    expect(fs.existsSync(SEED), 'classroom seed json').toBeTruthy();
    const seed = JSON.parse(fs.readFileSync(SEED, 'utf8'));
    fs.writeFileSync(path.join(EVIDENCE, 'api', 'seed.json'), JSON.stringify(seed, null, 2));
    expect(seed.session_id).toBeGreaterThan(0);
    expect(seed.window?.allowed).toBeTruthy();
    expect(String(seed.classroom_url || '')).toContain('ngt_classroom');

    await wpLogin(page);
    await ensureDemoSeedAndPassword(page);
    await wpLogin(page, DEMO_PERSONAS.parent.email, demoPassword);

    const classroomPath = `/?ngt_classroom=${seed.session_id}`;
    await gotoReady(page, classroomPath);
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(EVIDENCE, 'screenshots', '12-course-player.png'), fullPage: true });

    const body = page.locator('.ngt-classroom');
    await expect(body).toBeVisible({ timeout: 60_000 });
    await expect(page.locator('.ngt-classroom__brand')).toBeVisible();
    const live = page.locator('#ngt-enter-live-meeting, a.bi-dash-join-live');
    await expect(live).toBeVisible({ timeout: 30_000 });
    const href = await live.getAttribute('href');
    expect(href || '').toMatch(/meet\.jit\.si|jitsi/i);
    fs.writeFileSync(path.join(EVIDENCE, 'api', 'meeting-href.txt'), href || '');

    const [popup] = await Promise.all([
      context.waitForEvent('page', { timeout: 60_000 }).catch(() => null),
      live.click(),
    ]);
    if (popup) {
      await popup.waitForLoadState('domcontentloaded').catch(() => undefined);
      await popup.screenshot({ path: path.join(EVIDENCE, 'screenshots', '13-live-session-student.png') }).catch(() => undefined);
      await popup.close().catch(() => undefined);
    }
  });

  test('CLASSROOM-002 tutor classroom same session', async ({ page, context }) => {
    const seed = JSON.parse(fs.readFileSync(SEED, 'utf8'));
    await wpLogin(page, DEMO_PERSONAS.tutorOnline.email, demoPassword);
    await gotoReady(page, `/?ngt_classroom=${seed.session_id}`);
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(EVIDENCE, 'screenshots', '14-live-session-tutor.png'), fullPage: true });
    await expect(page.locator('.ngt-classroom')).toBeVisible({ timeout: 60_000 });
    const live = page.locator('#ngt-enter-live-meeting');
    await expect(live).toBeVisible();
    const href = await live.getAttribute('href');
    expect(href || '').toContain(String(seed.meeting_id || 'NextGenTutors-Lesson'));
    const [popup] = await Promise.all([
      context.waitForEvent('page', { timeout: 60_000 }).catch(() => null),
      live.click(),
    ]);
    if (popup) {
      await popup.waitForTimeout(3000);
      await popup.screenshot({ path: path.join(EVIDENCE, 'screenshots', '07-audio-video-active.png') }).catch(() => undefined);
      await popup.close().catch(() => undefined);
    }
  });

  test('CLASSROOM-003 REST launch returns classroom_url', async ({ page }) => {
    const seed = JSON.parse(fs.readFileSync(SEED, 'utf8'));
    await wpLogin(page, DEMO_PERSONAS.tutorOnline.email, demoPassword);
    await gotoReady(page, DEMO_PERSONAS.tutorOnline.path);
    const launch = await page.evaluate(async (sid) => {
      const nonce =
        (window as unknown as { biDashboard?: { nonce?: string }; wpApiSettings?: { nonce?: string } }).biDashboard
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
    }, Number(seed.session_id));
    fs.writeFileSync(path.join(EVIDENCE, 'api', 'launch.json'), JSON.stringify(launch, null, 2));
    expect(launch.status).toBe(200);
    const body = launch.body as Record<string, string>;
    expect(String(body.classroom_url || body.launch_url || '')).toContain('ngt_classroom');
    expect(String(body.meeting_url || '')).toMatch(/meet\.jit\.si/i);
  });
});
