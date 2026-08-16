/**
 * Shared helpers for headed student ↔ tutor live lesson E2E.
 */
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { expect, type Browser, type BrowserContext, type Page } from '@playwright/test';
import { openDemoControlCentre, wpLogin } from '../helpers';

export const EVIDENCE_ROOT = path.resolve(
  __dirname,
  '..',
  '..',
  'delivery',
  'evidence',
  'lesson-e2e'
);

export const demoPassword =
  process.env.NGC_DEMO_PASSWORD || process.env.DEMO_PASSWORD || 'NgtDemo!09a2b917';

export const DEMO_PERSONAS = {
  parent: { email: 'demo.parent@nextgen.local', role: 'parent', path: '/parent-dashboard/' },
  studentAdult: {
    email: 'demo.student.adult@nextgen.local',
    role: 'student',
    path: '/student-dashboard/',
  },
  childA: { email: 'demo.child.a@nextgen.local', role: 'child_learner', path: '/student-dashboard/' },
  tutorApproved: {
    email: 'demo.tutor.approved@nextgen.local',
    role: 'tutor',
    path: '/tutor-dashboard/',
  },
  tutorOnline: {
    email: 'demo.tutor.online@nextgen.local',
    role: 'tutor',
    path: '/tutor-dashboard/',
  },
} as const;

/** Deterministic PRNG (mulberry32) from integer seed. */
export function mulberry32(seed: number) {
  let t = seed >>> 0;
  return () => {
    t += 0x6d2b79f5;
    let r = Math.imul(t ^ (t >>> 15), 1 | t);
    r ^= r + Math.imul(r ^ (r >>> 7), 61 | r);
    return ((r ^ (r >>> 14)) >>> 0) / 4294967296;
  };
}

export function resolveTestSeed(): number {
  if (process.env.TEST_RANDOM_SEED) {
    return Number(process.env.TEST_RANDOM_SEED) >>> 0;
  }
  return (Date.now() ^ (process.hrtime.bigint() & 0xffffffffn ? Number(process.hrtime.bigint() & 0xffffffffn) : 0)) >>> 0;
}

export function ensureEvidenceDirs() {
  const dirs = [
    'inventory',
    'database/before',
    'database/during',
    'database/after',
    'screenshots',
    'videos',
    'traces',
    'network',
    'console',
    'webrtc',
    'recordings',
    'api',
    'reports',
    'manifests',
  ];
  for (const d of dirs) {
    fs.mkdirSync(path.join(EVIDENCE_ROOT, d), { recursive: true });
  }
}

export function writeJson(rel: string, data: unknown) {
  const full = path.join(EVIDENCE_ROOT, rel);
  fs.mkdirSync(path.dirname(full), { recursive: true });
  fs.writeFileSync(full, JSON.stringify(data, null, 2), 'utf8');
  return full;
}

export function sha256File(filePath: string): string | null {
  try {
    const buf = fs.readFileSync(filePath);
    return crypto.createHash('sha256').update(buf).digest('hex');
  } catch {
    return null;
  }
}

export async function loginAs(page: Page, email: string, password = currentDemoPassword()) {
  await page.goto('/wp-login.php', { waitUntil: 'domcontentloaded', timeout: 120_000 });
  await page.locator('#user_login').waitFor({ state: 'visible', timeout: 30_000 });
  await page.evaluate(
    ({ user, pass }) => {
      const u = document.querySelector<HTMLInputElement>('#user_login');
      const p = document.querySelector<HTMLInputElement>('#user_pass');
      if (!u || !p) throw new Error('wp-login fields missing');
      u.value = user;
      p.value = pass;
      u.dispatchEvent(new Event('input', { bubbles: true }));
      p.dispatchEvent(new Event('input', { bubbles: true }));
    },
    { user: email, pass: password }
  );
  await expect(page.locator('#user_login')).toHaveValue(email);
  await expect(page.locator('#user_pass')).toHaveValue(password);
  await Promise.all([
    page.waitForURL((url) => !url.pathname.includes('wp-login.php'), {
      timeout: 90_000,
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

export function currentDemoPassword(): string {
  const fromGlobal = (globalThis as { __NGT_DEMO_PASSWORD?: string }).__NGT_DEMO_PASSWORD;
  if (fromGlobal) return fromGlobal;
  try {
    const pwFile = path.join(EVIDENCE_ROOT, 'inventory', '.demo-password');
    if (fs.existsSync(pwFile)) {
      const pw = fs.readFileSync(pwFile, 'utf8').trim();
      if (pw) return pw;
    }
  } catch {
    /* ignore */
  }
  return process.env.NGC_DEMO_PASSWORD || process.env.DEMO_PASSWORD || demoPassword;
}

/**
 * Ensure demo mode + seed, return live demo password from Control Centre.
 * Password is written only to evidence inventory (redact in reports if needed).
 */
export async function ensureDemoSeedAndPassword(page: Page): Promise<string> {
  await wpLogin(page);
  await openDemoControlCentre(page);

  async function submitOp(op: string, testId: string) {
    const btn = page.getByTestId(testId);
    if (!(await btn.isVisible().catch(() => false))) return;
    await Promise.all([
      page.waitForResponse(
        (res) => {
          if (!res.url().includes('admin-post.php') || res.request().method() !== 'POST') return false;
          const body = res.request().postData() ?? '';
          return body.includes('action=ngc_demo_action') && body.includes(`op=${op}`);
        },
        { timeout: 300_000 }
      ).catch(() => null),
      btn.click({ force: true, noWaitAfter: true }),
    ]);
    await page.waitForLoadState('domcontentloaded').catch(() => null);
    await openDemoControlCentre(page);
  }

  const mode = (await page.getByTestId('ngc-demo-mode').innerText().catch(() => '')).trim();
  if (mode !== 'ON') {
    await submitOp('enable', 'ngc-demo-enable');
  }

  // Skip re-seed when graph already has bookings (idempotent + faster E2E loops).
  const existingGraph = path.join(EVIDENCE_ROOT, 'inventory', 'demo-seed-graph.json');
  let needSeed = true;
  if (fs.existsSync(existingGraph)) {
    try {
      const g = JSON.parse(fs.readFileSync(existingGraph, 'utf8')) as { bookings?: Record<string, number> };
      if (g.bookings && (g.bookings['BOOK-ADULT'] || g.bookings['BOOK-001'])) {
        needSeed = false;
      }
    } catch {
      needSeed = true;
    }
  }
  if (needSeed) {
    await submitOp('seed', 'ngc-demo-seed');
  } else {
    await openDemoControlCentre(page);
  }
  const pwCell = page.getByTestId('ngc-demo-password').first();
  await expect(pwCell).toBeVisible({ timeout: 60_000 });
  const password = (await pwCell.innerText()).trim();
  if (!password || /hidden/i.test(password)) {
    throw new Error('Demo password unavailable — enable demo mode and re-seed');
  }
  (globalThis as { __NGT_DEMO_PASSWORD?: string }).__NGT_DEMO_PASSWORD = password;
  fs.mkdirSync(path.join(EVIDENCE_ROOT, 'inventory'), { recursive: true });
  fs.writeFileSync(path.join(EVIDENCE_ROOT, 'inventory', '.demo-password'), password, 'utf8');

  const graphText = await page.getByTestId('ngc-demo-seed-graph').innerText().catch(() => '{}');
  let graphObj: Record<string, unknown> = {};
  try {
    graphObj = JSON.parse(graphText) as Record<string, unknown>;
  } catch {
    graphObj = {};
  }
  writeJson('inventory/demo-seed-graph.json', {
    rawLength: graphText.length,
    preview: graphText.slice(0, 8000),
    bookings: (graphObj.bookings as Record<string, number>) || {},
    users: (graphObj.users as Record<string, number>) || {},
  });
  writeJson('inventory/demo-password-present.json', { present: true, length: password.length });

  return password;
}

export async function dismissOverlays(page: Page) {
  const accept = page.getByRole('button', { name: /^Accept$/i });
  if (await accept.isVisible().catch(() => false)) {
    await accept.click().catch(() => undefined);
  }
}

export async function screenshot(page: Page, name: string) {
  const dest = path.join(EVIDENCE_ROOT, 'screenshots', `${name}.png`);
  await page.screenshot({ path: dest, fullPage: true }).catch(async () => {
    await page.screenshot({ path: dest });
  });
  return dest;
}

export type ConsoleBucket = { type: string; text: string; url?: string }[];

export function attachConsole(page: Page, bucket: ConsoleBucket) {
  page.on('console', (msg) => {
    bucket.push({ type: msg.type(), text: msg.text(), url: page.url() });
  });
  page.on('pageerror', (err) => {
    bucket.push({ type: 'pageerror', text: String(err), url: page.url() });
  });
}

export type NetBucket = {
  method: string;
  url: string;
  status: number;
  durationMs?: number;
}[];

export function attachNetwork(page: Page, bucket: NetBucket) {
  page.on('response', (res) => {
    const url = res.url();
    if (!/ngc\/v1|meet\.jit\.si|jitsi|booking|join|meeting/i.test(url)) return;
    bucket.push({
      method: res.request().method(),
      url: redactUrl(url),
      status: res.status(),
    });
  });
}

export function redactUrl(url: string): string {
  return url
    .replace(/([?&](token|jwt|key|password|auth)=)[^&]+/gi, '$1[REDACTED]')
    .replace(/Bearer\s+\S+/gi, 'Bearer [REDACTED]');
}

export function mediaLaunchArgs(): string[] {
  return [
    '--use-fake-ui-for-media-stream',
    '--use-fake-device-for-media-stream',
    '--autoplay-policy=no-user-gesture-required',
  ];
}

export async function newAuthedContext(
  browser: Browser,
  email: string,
  options?: { recordVideo?: boolean }
): Promise<{ context: BrowserContext; page: Page }> {
  const context = await browser.newContext({
    permissions: ['camera', 'microphone'],
    recordVideo: options?.recordVideo
      ? { dir: path.join(EVIDENCE_ROOT, 'videos'), size: { width: 1280, height: 720 } }
      : undefined,
    viewport: { width: 1280, height: 800 },
  });
  const page = await context.newPage();
  await loginAs(page, email);
  return { context, page };
}

export async function fetchJson(page: Page, apiPath: string) {
  return page.evaluate(async (p) => {
    const w = window as unknown as {
      biDashboard?: { nonce?: string };
      wpApiSettings?: { nonce?: string };
    };
    const nonce = w.biDashboard?.nonce || w.wpApiSettings?.nonce || '';
    const headers: Record<string, string> = { Accept: 'application/json' };
    if (nonce) headers['X-WP-Nonce'] = nonce;
    const res = await fetch(p, {
      credentials: 'same-origin',
      headers,
    });
    const body = await res.json().catch(() => null);
    return { status: res.status, body, hadNonce: Boolean(nonce) };
  }, apiPath);
}

/** Resolve wp_rest nonce after login (dashboard or wp-admin). */
export async function ensureRestNonce(page: Page): Promise<string> {
  const read = () =>
    page.evaluate(() => {
      const w = window as unknown as {
        biDashboard?: { nonce?: string };
        wpApiSettings?: { nonce?: string };
      };
      return w.biDashboard?.nonce || w.wpApiSettings?.nonce || '';
    });

  let nonce = await read();
  if (nonce) return nonce;

  await page.waitForFunction(
    () => {
      const w = window as unknown as {
        biDashboard?: { nonce?: string };
        wpApiSettings?: { nonce?: string };
      };
      return Boolean(w.biDashboard?.nonce || w.wpApiSettings?.nonce);
    },
    { timeout: 45_000 }
  ).catch(() => null);

  nonce = await read();
  if (nonce) return nonce;

  await page.goto('/wp-admin/profile.php', { waitUntil: 'domcontentloaded', timeout: 60_000 }).catch(() => null);
  await page.waitForFunction(
    () => Boolean((window as unknown as { wpApiSettings?: { nonce?: string } }).wpApiSettings?.nonce),
    { timeout: 20_000 }
  ).catch(() => null);
  return read();
}

/** Build joinable candidates from the demo seed graph (fast path). */
export function candidatesFromSeedGraph(): JoinableCandidate[] {
  const graphPath = path.join(EVIDENCE_ROOT, 'inventory', 'demo-seed-graph.json');
  if (!fs.existsSync(graphPath)) return [];
  const graphWrap = JSON.parse(fs.readFileSync(graphPath, 'utf8')) as {
    preview?: string;
    bookings?: Record<string, number>;
  };
  const bookings =
    graphWrap.bookings && Object.keys(graphWrap.bookings).length
      ? graphWrap.bookings
      : ((JSON.parse(graphWrap.preview || '{}') as { bookings?: Record<string, number> }).bookings || {});

  const known: JoinableCandidate[] = [];
  const adultId = Number(bookings['BOOK-ADULT'] || 0);
  const book001 = Number(bookings['BOOK-001'] || 0);
  if (adultId) {
    known.push({
      bookingId: adultId,
      status: 'confirmed',
      subject: 'English',
      studentEmail: DEMO_PERSONAS.studentAdult.email,
      tutorEmail: DEMO_PERSONAS.tutorOnline.email,
      studentPath: DEMO_PERSONAS.studentAdult.path,
      tutorPath: DEMO_PERSONAS.tutorOnline.path,
      source: 'seed-graph:BOOK-ADULT',
      canJoin: true,
    });
  }
  if (book001) {
    known.push({
      bookingId: book001,
      status: 'confirmed',
      subject: 'Mathematics',
      studentEmail: DEMO_PERSONAS.parent.email,
      tutorEmail: DEMO_PERSONAS.tutorApproved.email,
      studentPath: DEMO_PERSONAS.parent.path,
      tutorPath: DEMO_PERSONAS.tutorApproved.path,
      source: 'seed-graph:BOOK-001',
      canJoin: true,
    });
  }
  return known;
}

export async function joinBookingApi(page: Page, bookingId: number) {
  await ensureRestNonce(page);
  const nonce = await page.evaluate(() => {
    const w = window as unknown as { biDashboard?: { nonce?: string }; wpApiSettings?: { nonce?: string } };
    return w.biDashboard?.nonce || w.wpApiSettings?.nonce || '';
  });
  return page.evaluate(
    async ({ id, nonce: n }) => {
      const headers: Record<string, string> = { Accept: 'application/json' };
      if (n) headers['X-WP-Nonce'] = n;
      const res = await fetch(`/wp-json/ngc/v1/bookings/${id}/join`, {
        method: 'POST',
        credentials: 'same-origin',
        headers,
      });
      const body = await res.json().catch(() => null);
      return { status: res.status, body };
    },
    { id: bookingId, nonce }
  );
}

export type JoinableCandidate = {
  bookingId: number;
  status: string;
  subject: string;
  scheduledAt?: string;
  studentEmail: string;
  tutorEmail: string;
  studentPath: string;
  tutorPath: string;
  source: string;
  joinUrl?: string;
  room?: string;
  canJoin?: boolean;
};

/** Inventory joinable sessions from parent, adult student, and tutor dashboards. */
export async function discoverJoinableCandidates(browser: Browser): Promise<{
  candidates: JoinableCandidate[];
  raw: unknown;
}> {
  const candidates: JoinableCandidate[] = [];
  const raw: Record<string, unknown> = {};

  const probes: Array<{
    email: string;
    dashPath: string;
    api: string;
    kind: 'student' | 'parent' | 'tutor';
  }> = [
    {
      email: DEMO_PERSONAS.studentAdult.email,
      dashPath: DEMO_PERSONAS.studentAdult.path,
      api: '/wp-json/ngc/v1/dashboard/student',
      kind: 'student',
    },
    {
      email: DEMO_PERSONAS.parent.email,
      dashPath: DEMO_PERSONAS.parent.path,
      api: '/wp-json/ngc/v1/dashboard/parent',
      kind: 'parent',
    },
    {
      email: DEMO_PERSONAS.tutorApproved.email,
      dashPath: DEMO_PERSONAS.tutorApproved.path,
      api: '/wp-json/ngc/v1/dashboard/tutor',
      kind: 'tutor',
    },
    {
      email: DEMO_PERSONAS.tutorOnline.email,
      dashPath: DEMO_PERSONAS.tutorOnline.path,
      api: '/wp-json/ngc/v1/dashboard/tutor',
      kind: 'tutor',
    },
  ];

  for (const probe of probes) {
    const context = await browser.newContext();
    const page = await context.newPage();
    try {
      await loginAs(page, probe.email, currentDemoPassword());
      await page.goto(probe.dashPath, { waitUntil: 'domcontentloaded', timeout: 90_000 });
      await dismissOverlays(page);
      await ensureRestNonce(page);
      const dash = await fetchJson(page, probe.api);
      raw[probe.email] = { status: dash.status, dashboard: dash.body, hadNonce: dash.hadNonce };
      const payload =
        (dash.body as { data?: { recentSessions?: unknown[]; nextSession?: unknown } })?.data ||
        (dash.body as { recentSessions?: unknown[]; nextSession?: unknown }) ||
        {};
      const sessions = [
        ...(payload.nextSession ? [payload.nextSession] : []),
        ...((payload.recentSessions as unknown[]) || []),
      ];
      const bookingsApi = await fetchJson(page, '/wp-json/ngc/v1/bookings?limit=50');
      raw[`${probe.email}:bookings`] = bookingsApi;

      for (const s of sessions) {
        const row = s as Record<string, unknown>;
        const id = Number(row.bookingId || row.id || 0);
        if (!id) continue;
        const status = String(row.status || '');
        if (!['confirmed', 'requested'].includes(status)) continue;
        candidates.push({
          bookingId: id,
          status,
          subject: String(row.subject || ''),
          scheduledAt: String(row.createdAt || ''),
          studentEmail:
            probe.kind === 'tutor'
              ? ''
              : probe.kind === 'parent'
                ? DEMO_PERSONAS.parent.email
                : probe.email,
          tutorEmail: probe.kind === 'tutor' ? probe.email : '',
          studentPath:
            probe.kind === 'parent'
              ? DEMO_PERSONAS.parent.path
              : DEMO_PERSONAS.studentAdult.path,
          tutorPath: DEMO_PERSONAS.tutorApproved.path,
          source: `${probe.kind}:${probe.email}`,
          joinUrl: String(row.joinUrl || row.join_url || ''),
          room: '',
          canJoin: Boolean(row.canJoin),
        });
      }

      const bookingList =
        (bookingsApi.body as { bookings?: Array<Record<string, unknown>> })?.bookings || [];
      for (const b of bookingList) {
        const id = Number(b.id || 0);
        const status = String(b.status || '');
        if (!id || !['confirmed', 'requested'].includes(status)) continue;
        if (candidates.some((c) => c.bookingId === id && c.source.startsWith(probe.kind))) continue;
        candidates.push({
          bookingId: id,
          status,
          subject: String(b.subject || ''),
          scheduledAt: String(b.scheduled_at || ''),
          studentEmail: probe.kind === 'student' ? probe.email : probe.kind === 'parent' ? DEMO_PERSONAS.parent.email : '',
          tutorEmail: probe.kind === 'tutor' ? probe.email : '',
          studentPath:
            probe.kind === 'parent' ? DEMO_PERSONAS.parent.path : DEMO_PERSONAS.studentAdult.path,
          tutorPath: probe.kind === 'tutor' ? probe.dashPath : DEMO_PERSONAS.tutorApproved.path,
          source: `bookings-api:${probe.email}`,
          canJoin: true,
        });
      }
    } catch (e) {
      raw[`${probe.email}:error`] = String(e);
    } finally {
      await context.close();
    }
  }

  // Deduplicate by bookingId preferring rows with joinUrl/canJoin.
  const byId = new Map<number, JoinableCandidate>();
  for (const c of candidates) {
    const prev = byId.get(c.bookingId);
    if (!prev || (c.canJoin && !prev.canJoin) || (c.joinUrl && !prev.joinUrl)) {
      byId.set(c.bookingId, { ...prev, ...c, studentEmail: c.studentEmail || prev?.studentEmail || '', tutorEmail: c.tutorEmail || prev?.tutorEmail || '' });
    } else if (prev) {
      byId.set(c.bookingId, {
        ...prev,
        studentEmail: prev.studentEmail || c.studentEmail,
        tutorEmail: prev.tutorEmail || c.tutorEmail,
      });
    }
  }

    try {
      const graphPath = path.join(EVIDENCE_ROOT, 'inventory', 'demo-seed-graph.json');
      if (fs.existsSync(graphPath)) {
        const graphWrap = JSON.parse(fs.readFileSync(graphPath, 'utf8')) as {
          preview?: string;
          bookings?: Record<string, number>;
        };
        const bookings =
          graphWrap.bookings && Object.keys(graphWrap.bookings).length
            ? graphWrap.bookings
            : ((JSON.parse(graphWrap.preview || '{}') as { bookings?: Record<string, number> }).bookings ||
              {});
        const known: Array<{
          key: string;
          id: number;
          subject: string;
          studentEmail: string;
          tutorEmail: string;
          studentPath: string;
          tutorPath: string;
        }> = [
          {
            key: 'BOOK-ADULT',
            id: Number(bookings['BOOK-ADULT'] || 0),
            subject: 'English',
            studentEmail: DEMO_PERSONAS.studentAdult.email,
            tutorEmail: DEMO_PERSONAS.tutorOnline.email,
            studentPath: DEMO_PERSONAS.studentAdult.path,
            tutorPath: DEMO_PERSONAS.tutorOnline.path,
          },
          {
            key: 'BOOK-001',
            id: Number(bookings['BOOK-001'] || 0),
            subject: 'Mathematics',
            studentEmail: DEMO_PERSONAS.parent.email,
            tutorEmail: DEMO_PERSONAS.tutorApproved.email,
            studentPath: DEMO_PERSONAS.parent.path,
            tutorPath: DEMO_PERSONAS.tutorApproved.path,
          },
        ];
        for (const k of known) {
          if (!k.id) continue;
          if ([...byId.keys()].includes(k.id)) continue;
          byId.set(k.id, {
            bookingId: k.id,
            status: 'confirmed',
            subject: k.subject,
            studentEmail: k.studentEmail,
            tutorEmail: k.tutorEmail,
            studentPath: k.studentPath,
            tutorPath: k.tutorPath,
            source: `seed-graph:${k.key}`,
            canJoin: true,
          });
        }
        raw['seedGraphFallback'] = known;
      }
    } catch (e) {
      raw['seedGraphFallbackError'] = String(e);
    }

  return { candidates: [...byId.values()], raw };
}

export function pickCandidate(
  candidates: JoinableCandidate[],
  seed: number,
  prefer?: { studentEmail?: string; tutorEmail?: string }
): { selected: JoinableCandidate | null; rejected: Array<{ id: number; reason: string }> } {
  const rand = mulberry32(seed);
  const rejected: Array<{ id: number; reason: string }> = [];
  let pool = candidates.filter((c) => ['confirmed', 'requested'].includes(c.status));
  if (!pool.length) {
    return { selected: null, rejected: [{ id: 0, reason: 'empty_candidate_pool' }] };
  }

  // Prefer pairs where we know both actors (adult student ↔ tutor.online OR parent ↔ tutor.approved).
  const scored = pool.map((c) => {
    let score = 1;
    if (c.canJoin) score += 2;
    if (c.joinUrl) score += 1;
    if (prefer?.studentEmail && c.studentEmail === prefer.studentEmail) score += 3;
    if (prefer?.tutorEmail && c.tutorEmail === prefer.tutorEmail) score += 3;
    if (c.subject) score += 1;
    return { c, score };
  });
  scored.sort((a, b) => b.score - a.score);
  const topScore = scored[0]?.score ?? 0;
  const top = scored.filter((s) => s.score >= topScore - 1).map((s) => s.c);
  const idx = Math.floor(rand() * top.length);
  const selected = top[idx] || null;
  for (const c of pool) {
    if (!selected || c.bookingId !== selected.bookingId) {
      rejected.push({ id: c.bookingId, reason: 'not_selected_by_seeded_rng' });
    }
  }
  return { selected, rejected };
}

/** Resolve tutor/student emails for a booking via join + dashboard correlation heuristics. */
export function resolveActors(selected: JoinableCandidate): {
  studentLogin: string;
  studentPath: string;
  tutorLogin: string;
  tutorPath: string;
} {
  // Known seed mappings when emails missing from inventory.
  if (selected.subject === 'English' || selected.source.includes('demo.student.adult')) {
    return {
      studentLogin: DEMO_PERSONAS.studentAdult.email,
      studentPath: DEMO_PERSONAS.studentAdult.path,
      tutorLogin: selected.tutorEmail || DEMO_PERSONAS.tutorOnline.email,
      tutorPath: DEMO_PERSONAS.tutorOnline.path,
    };
  }
  if (selected.subject === 'Mathematics' || selected.source.includes('demo.parent')) {
    return {
      studentLogin: DEMO_PERSONAS.parent.email,
      studentPath: DEMO_PERSONAS.parent.path,
      tutorLogin: selected.tutorEmail || DEMO_PERSONAS.tutorApproved.email,
      tutorPath: DEMO_PERSONAS.tutorApproved.path,
    };
  }
  return {
    studentLogin: selected.studentEmail || DEMO_PERSONAS.studentAdult.email,
    studentPath: selected.studentPath || DEMO_PERSONAS.studentAdult.path,
    tutorLogin: selected.tutorEmail || DEMO_PERSONAS.tutorApproved.email,
    tutorPath: selected.tutorPath || DEMO_PERSONAS.tutorApproved.path,
  };
}

export async function waitForDashboardJoin(page: Page) {
  const join = page.locator('a.bi-dash-hero__join, a.bi-dash-session__join').first();
  await expect(join).toBeVisible({ timeout: 60_000 });
  return join;
}

export async function collectMediaProof(page: Page) {
  return page.evaluate(async () => {
    const videos = [...document.querySelectorAll('video')].map((v) => ({
      paused: v.paused,
      muted: v.muted,
      w: v.videoWidth,
      h: v.videoHeight,
      readyState: v.readyState,
      srcObject: Boolean(v.srcObject),
    }));
    const pcs = (window as unknown as { __ngtPcs?: RTCPeerConnection[] }).__ngtPcs || [];
    // Best-effort: peek at chrome WebRTC internals via getStats on any known PC — often empty on third-party.
    let remoteAudio = 0;
    let remoteVideo = 0;
    let localAudio = 0;
    let localVideo = 0;
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: true });
      localAudio = stream.getAudioTracks().filter((t) => t.readyState === 'live').length;
      localVideo = stream.getVideoTracks().filter((t) => t.readyState === 'live').length;
      stream.getTracks().forEach((t) => t.stop());
    } catch (e) {
      return { error: String(e), videos, pcs: pcs.length, localAudio, localVideo, remoteAudio, remoteVideo };
    }
    for (const v of videos) {
      if (v.srcObject && v.w > 0 && v.h > 0) remoteVideo += 1;
    }
    return {
      videos,
      videoCount: videos.length,
      liveVideoTiles: videos.filter((v) => v.w > 0 && v.h > 0).length,
      localAudio,
      localVideo,
      remoteAudio,
      remoteVideo,
      href: location.href,
      title: document.title,
    };
  });
}

export async function probeJitsiConnected(page: Page, timeoutMs = 90_000) {
  const started = Date.now();
  let last: unknown = null;
  while (Date.now() - started < timeoutMs) {
    last = await collectMediaProof(page);
    const proof = last as {
      localAudio?: number;
      localVideo?: number;
      liveVideoTiles?: number;
      videoCount?: number;
      error?: string;
    };
    if (
      !proof.error &&
      (proof.localAudio || 0) > 0 &&
      (proof.localVideo || 0) > 0 &&
      ((proof.liveVideoTiles || 0) > 0 || (proof.videoCount || 0) > 0)
    ) {
      return { ok: true, proof };
    }
    // Jitsi prejoin: try to click join if present.
    const joinBtn = page.getByRole('button', { name: /join meeting|i am the host|join now|ask to join/i }).first();
    if (await joinBtn.isVisible().catch(() => false)) {
      await joinBtn.click().catch(() => undefined);
    }
    const nameInput = page.locator('input[name="displayName"], input[placeholder*="name" i]').first();
    if (await nameInput.isVisible().catch(() => false)) {
      await nameInput.fill('NGT E2E').catch(() => undefined);
    }
    await page.waitForTimeout(2000);
  }
  return { ok: false, proof: last };
}
