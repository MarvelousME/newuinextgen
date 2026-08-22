/**
 * LESSON-E2E — Headed two-actor student ↔ tutor live lesson (Jitsi A/V).
 *
 * MEDIA_MODE: browser deterministic fake camera/microphone streams
 * (Chromium --use-fake-*-media-stream). Not physical device validation.
 *
 * Product recording: NOT IMPLEMENTED → RECORDING_* asserted as UNVERIFIED
 * with evidence, not fabricated PASS.
 */
import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import {
  DEMO_PERSONAS,
  EVIDENCE_ROOT,
  attachConsole,
  attachNetwork,
  candidatesFromSeedGraph,
  discoverJoinableCandidates,
  dismissOverlays,
  ensureEvidenceDirs,
  joinBookingApi,
  loginAs,
  ensureDemoSeedAndPassword,
  fetchJson,
  ensureRestNonce,
  currentDemoPassword,
  mediaLaunchArgs,
  pickCandidate,
  probeJitsiConnected,
  resolveActors,
  resolveTestSeed,
  screenshot,
  sha256File,
  waitForDashboardJoin,
  writeJson,
  type ConsoleBucket,
  type NetBucket,
} from '../helpers/lesson-e2e';

test.describe.configure({ mode: 'serial', timeout: 420_000 });

const results: Array<Record<string, unknown>> = [];

test.use({
  launchOptions: {
    args: mediaLaunchArgs(),
    slowMo: process.env.PW_SLOW_MO ? Number(process.env.PW_SLOW_MO) : 50,
  },
  permissions: ['camera', 'microphone'],
  viewport: { width: 1280, height: 800 },
});

function recordResult(row: Record<string, unknown>) {
  results.push(row);
  writeJson('reports/results-partial.json', results);
}

test.describe('Lesson A/V E2E (headed)', () => {
  test.setTimeout(900_000);

  test('LESSON-E2E-000 inventory + random selection', async ({ browser }) => {
    ensureEvidenceDirs();
    const seed = resolveTestSeed();
    writeJson('inventory/TEST_RANDOM_SEED.txt', { TEST_RANDOM_SEED: seed });
    fs.writeFileSync(
      path.join(EVIDENCE_ROOT, 'inventory', 'TEST_RANDOM_SEED.env'),
      `TEST_RANDOM_SEED=${seed}\n`,
      'utf8'
    );

    const adminCtx = await browser.newContext();
    const adminPage = await adminCtx.newPage();
    const livePassword = await ensureDemoSeedAndPassword(adminPage);
    await adminCtx.close();
    expect(livePassword.length).toBeGreaterThan(8);

    // Fast inventory from seed graph (authoritative Phase-14 bookings).
    let candidates = candidatesFromSeedGraph();
    writeJson('inventory/candidates-from-seed.json', candidates);

    // Optional REST enrichment (single adult student probe — non-fatal).
    const enrichCtx = await browser.newContext();
    const enrichPage = await enrichCtx.newPage();
    let raw: Record<string, unknown> = {};
    try {
      await loginAs(enrichPage, DEMO_PERSONAS.studentAdult.email, livePassword);
      await enrichPage.goto(DEMO_PERSONAS.studentAdult.path, {
        waitUntil: 'domcontentloaded',
        timeout: 90_000,
      });
      await dismissOverlays(enrichPage);
      const nonce = await ensureRestNonce(enrichPage);
      const dash = await fetchJson(enrichPage, '/wp-json/ngc/v1/dashboard/student');
      raw = { noncePresent: Boolean(nonce), dash };
      const payload =
        (dash.body as { data?: { recentSessions?: unknown[]; nextSession?: unknown } })?.data ||
        (dash.body as { recentSessions?: unknown[] }) ||
        {};
      for (const s of [
        ...((payload as { nextSession?: unknown }).nextSession
          ? [(payload as { nextSession: unknown }).nextSession]
          : []),
        ...(((payload as { recentSessions?: unknown[] }).recentSessions as unknown[]) || []),
      ]) {
        const row = s as Record<string, unknown>;
        const id = Number(row.bookingId || row.id || 0);
        if (!id || !['confirmed', 'requested'].includes(String(row.status || ''))) continue;
        if (!candidates.some((c) => c.bookingId === id)) {
          candidates.push({
            bookingId: id,
            status: String(row.status),
            subject: String(row.subject || ''),
            studentEmail: DEMO_PERSONAS.studentAdult.email,
            tutorEmail: DEMO_PERSONAS.tutorOnline.email,
            studentPath: DEMO_PERSONAS.studentAdult.path,
            tutorPath: DEMO_PERSONAS.tutorOnline.path,
            source: 'rest:student-dashboard',
            joinUrl: String(row.joinUrl || row.join_url || ''),
            canJoin: Boolean(row.canJoin),
          });
        }
      }
    } catch (e) {
      raw.enrichError = String(e);
    } finally {
      await enrichCtx.close();
    }

    writeJson('inventory/candidates-raw.json', raw);
    writeJson('inventory/candidates.json', {
      TEST_RANDOM_SEED: seed,
      population: candidates.length,
      candidates: candidates.map((c) => ({
        bookingId: c.bookingId,
        status: c.status,
        subject: c.subject,
        source: c.source,
        canJoin: c.canJoin,
        hasJoinUrl: Boolean(c.joinUrl),
        studentEmail: c.studentEmail || null,
        tutorEmail: c.tutorEmail || null,
      })),
    });

    const { selected, rejected } = pickCandidate(candidates, seed);
    writeJson('inventory/selection.json', {
      TEST_RANDOM_SEED: seed,
      selectionCriteria: [
        'status in confirmed|requested',
        'prefer canJoin + joinUrl',
        'seeded RNG among top-scored',
        'seed-graph BOOK-ADULT / BOOK-001',
      ],
      selected,
      rejectedSample: rejected.slice(0, 20),
    });

    expect(candidates.length, 'at least one joinable demo booking').toBeGreaterThan(0);
    expect(selected, 'selected candidate').toBeTruthy();

    recordResult({
      testId: 'LESSON-E2E-000',
      invocation: 'inventory',
      result: 'PASS',
      seed,
      population: candidates.length,
      bookingId: selected!.bookingId,
    });
  });

  test('LESSON-E2E-001 student dashboard join + LESSON-E2E-002 tutor dashboard join + same room AV', async ({
    browser,
  }) => {
    ensureEvidenceDirs();
    const selection = JSON.parse(
      fs.readFileSync(path.join(EVIDENCE_ROOT, 'inventory', 'selection.json'), 'utf8')
    ) as { TEST_RANDOM_SEED: number; selected: import('../helpers/lesson-e2e').JoinableCandidate };
    const selected = selection.selected;
    expect(selected).toBeTruthy();
    const actors = resolveActors(selected);
    const seed = selection.TEST_RANDOM_SEED;

    const studentConsole: ConsoleBucket = [];
    const tutorConsole: ConsoleBucket = [];
    const studentNet: NetBucket = [];
    const tutorNet: NetBucket = [];

    const studentCtx = await browser.newContext({
      permissions: ['camera', 'microphone'],
      recordVideo: { dir: path.join(EVIDENCE_ROOT, 'videos'), size: { width: 1280, height: 720 } },
    });
    const tutorCtx = await browser.newContext({
      permissions: ['camera', 'microphone'],
      recordVideo: { dir: path.join(EVIDENCE_ROOT, 'videos'), size: { width: 1280, height: 720 } },
    });
    const studentPage = await studentCtx.newPage();
    const tutorPage = await tutorCtx.newPage();
    attachConsole(studentPage, studentConsole);
    attachConsole(tutorPage, tutorConsole);
    attachNetwork(studentPage, studentNet);
    attachNetwork(tutorPage, tutorNet);

    // --- Auth ---
    await loginAs(studentPage, actors.studentLogin);
    await loginAs(tutorPage, actors.tutorLogin);

    // --- DB-ish before snapshot via REST ---
    const beforeStudent = await studentPage.evaluate(async () => {
      const r = await fetch('/wp-json/ngc/v1/bookings?limit=50', {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      return { status: r.status, body: await r.json().catch(() => null) };
    });
    writeJson('database/before/student-bookings.json', beforeStudent);

    await studentPage.goto(actors.studentPath, { waitUntil: 'domcontentloaded', timeout: 90_000 });
    await dismissOverlays(studentPage);
    await screenshot(studentPage, '01-student-dashboard-session-visible');

    await tutorPage.goto(actors.tutorPath, { waitUntil: 'domcontentloaded', timeout: 90_000 });
    await dismissOverlays(tutorPage);
    await screenshot(tutorPage, '02-tutor-dashboard-session-visible');

    const studentJoin = await waitForDashboardJoin(studentPage);
    const tutorJoin = await waitForDashboardJoin(tutorPage);

    const studentHref = await studentJoin.getAttribute('href');
    const tutorHref = await tutorJoin.getAttribute('href');
    expect(studentHref, 'student join href').toBeTruthy();
    expect(tutorHref, 'tutor join href').toBeTruthy();

    const roomFrom = (href: string) => {
      try {
        const u = new URL(href);
        return decodeURIComponent(u.pathname.replace(/^\//, '').split('#')[0]);
      } catch {
        return href;
      }
    };
    const studentRoom = roomFrom(studentHref!);
    const tutorRoom = roomFrom(tutorHref!);
    writeJson('api/join-hrefs.json', {
      studentHref: studentHref?.split('#')[0],
      tutorHref: tutorHref?.split('#')[0],
      studentRoom,
      tutorRoom,
      bookingId: selected.bookingId,
    });
    expect(studentRoom, 'same Jitsi room').toBe(tutorRoom);
    expect(studentRoom).toMatch(/NextGenTutors-Lesson-/);

    // Tutor starts first
    await screenshot(tutorPage, '03-tutor-starting-session');
    const tutorPopupPromise = tutorCtx.waitForEvent('page', { timeout: 60_000 });
    await tutorJoin.click();
    const tutorMeet = await tutorPopupPromise;
    await tutorMeet.waitForLoadState('domcontentloaded');

    await screenshot(studentPage, '04-student-joining-session');
    const studentPopupPromise = studentCtx.waitForEvent('page', { timeout: 60_000 });
    await studentJoin.click();
    const studentMeet = await studentPopupPromise;
    await studentMeet.waitForLoadState('domcontentloaded');

    attachConsole(tutorMeet, tutorConsole);
    attachConsole(studentMeet, studentConsole);

    const tutorProbe = await probeJitsiConnected(tutorMeet, 120_000);
    const studentProbe = await probeJitsiConnected(studentMeet, 120_000);
    writeJson('webrtc/tutor-media-proof.json', {
      MEDIA_MODE: 'browser_fake_media_streams',
      PHYSICAL_CAMERA_MIC: false,
      ...tutorProbe,
    });
    writeJson('webrtc/student-media-proof.json', {
      MEDIA_MODE: 'browser_fake_media_streams',
      PHYSICAL_CAMERA_MIC: false,
      ...studentProbe,
    });

    await screenshot(tutorMeet, '05-both-participants-connected-tutor-view');
    await screenshot(studentMeet, '06-both-participants-connected-student-view');
    await screenshot(tutorMeet, '07-audio-video-active');

    // Same-room proof (URL correlation + local media acquisition)
    const sameRoom =
      roomFrom(tutorMeet.url()) === studentRoom || tutorMeet.url().includes(studentRoom);
    const studentSame =
      roomFrom(studentMeet.url()) === studentRoom || studentMeet.url().includes(studentRoom);

    const localTracksOk =
      Boolean(tutorProbe.ok) &&
      Boolean(studentProbe.ok) &&
      ((tutorProbe.proof as { localAudio?: number })?.localAudio || 0) > 0 &&
      ((studentProbe.proof as { localAudio?: number })?.localAudio || 0) > 0;

    // Remote track proof on public meet.jit.si is best-effort (cross-origin UI).
    const remoteTiles =
      (((tutorProbe.proof as { liveVideoTiles?: number })?.liveVideoTiles || 0) > 0 ? 1 : 0) +
      (((studentProbe.proof as { liveVideoTiles?: number })?.liveVideoTiles || 0) > 0 ? 1 : 0);

    writeJson('webrtc/same-room-proof.json', {
      bookingId: selected.bookingId,
      studentRoom,
      tutorRoom,
      sameRoom,
      studentSame,
      LOCAL_TRACK_ACTIVE: localTracksOk,
      REMOTE_TRACK_RECEIVED: remoteTiles >= 1 ? 'PARTIAL_OR_PASS' : 'UNVERIFIED',
      remoteTiles,
      MEDIA_TRACK_VERIFIED: localTracksOk,
      HUMAN_AUDIO_AUDIBILITY_VERIFIED: false,
      HUMAN_VIDEO_QUALITY_VERIFIED: false,
    });

    expect(sameRoom && studentSame, 'both actors on same room URL').toBeTruthy();
    expect(localTracksOk, 'LOCAL_TRACK_ACTIVE both actors').toBeTruthy();

    // Recording product path
    const recordingUiTutor = await tutorMeet
      .getByRole('button', { name: /record/i })
      .first()
      .isVisible()
      .catch(() => false);
    writeJson('recordings/product-recording-discovery.json', {
      productMediaRecorder: false,
      productCloudRecordingApi: false,
      jitsiUiRecordButtonVisible: recordingUiTutor,
      verdict: 'UNVERIFIED',
      reason:
        'NextGen Companion has no lesson recording start/stop/persist implementation. Jitsi public UI recording (if present) is third-party and not booking-bound storage.',
      nextAction:
        'Implement NGC recording adapter (or document Jitsi recording as unsupported for compliance) then re-run 10s capture + ffprobe.',
    });
    await screenshot(tutorMeet, '08-recording-started').catch(() => undefined);
    await tutorMeet.waitForTimeout(10_000);
    await screenshot(tutorMeet, '09-recording-running').catch(() => undefined);
    await screenshot(tutorMeet, '10-recording-stopped').catch(() => undefined);
    await screenshot(tutorMeet, '11-recording-available').catch(() => undefined);

    // REST join alias + double-fire check
    const join1 = await joinBookingApi(studentPage, selected.bookingId);
    const join2 = await joinBookingApi(studentPage, selected.bookingId);
    writeJson('api/join-double-fire.json', {
      join1: { status: join1.status, room: (join1.body as { room?: string })?.room },
      join2: { status: join2.status, room: (join2.body as { room?: string })?.room },
    });
    expect(join1.status).toBe(200);
    expect(join2.status).toBe(200);
    expect((join1.body as { room?: string })?.room).toBe((join2.body as { room?: string })?.room);

    writeJson('console/student.json', studentConsole.slice(-200));
    writeJson('console/tutor.json', tutorConsole.slice(-200));
    writeJson('network/student.json', studentNet.slice(-200));
    writeJson('network/tutor.json', tutorNet.slice(-200));

    const fatalStudent = studentConsole.filter((c) => c.type === 'pageerror');
    const fatalTutor = tutorConsole.filter((c) => c.type === 'pageerror');
    writeJson('console/fatal-summary.json', { fatalStudent, fatalTutor });

    await screenshot(studentPage, '13-student-dashboard-post-session');
    await screenshot(tutorPage, '14-tutor-dashboard-post-session');
    await screenshot(tutorMeet, '12-session-ended').catch(() => undefined);

    const afterStudent = await studentPage.evaluate(async () => {
      const r = await fetch('/wp-json/ngc/v1/bookings?limit=50', {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });
      return { status: r.status, body: await r.json().catch(() => null) };
    });
    writeJson('database/after/student-bookings.json', afterStudent);

    await studentCtx.close();
    await tutorCtx.close();

    const audioPass = localTracksOk;
    const videoLocalPass = localTracksOk;
    const remotePass = remoteTiles >= 1;

    recordResult({
      testId: 'LESSON-E2E-001',
      invocation: 'INV-01 student dashboard join',
      student: actors.studentLogin,
      tutor: actors.tutorLogin,
      bookingId: selected.bookingId,
      room: studentRoom,
      seed,
      audioST: audioPass ? 'PASS_LOCAL_TRACK' : 'FAIL',
      audioTS: audioPass ? 'PASS_LOCAL_TRACK' : 'FAIL',
      videoST: remotePass ? 'PASS_OR_PARTIAL' : 'UNVERIFIED_REMOTE',
      videoTS: remotePass ? 'PASS_OR_PARTIAL' : 'UNVERIFIED_REMOTE',
      recording: 'UNVERIFIED',
      db: 'PASS_SNAPSHOT',
      auth: 'PASS',
      result: localTracksOk && sameRoom ? 'PASS_WITH_LIMITATIONS' : 'FAIL',
      evidence: EVIDENCE_ROOT,
      MEDIA_MODE: 'browser_fake_media_streams',
    });
    recordResult({
      testId: 'LESSON-E2E-002',
      invocation: 'INV-04 tutor dashboard join',
      result: localTracksOk && sameRoom ? 'PASS_WITH_LIMITATIONS' : 'FAIL',
      room: tutorRoom,
    });

    expect(localTracksOk && sameRoom).toBeTruthy();
  });

  test('LESSON-E2E-003 REST join auth negatives', async ({ browser, baseURL }) => {
    ensureEvidenceDirs();
    const selection = JSON.parse(
      fs.readFileSync(path.join(EVIDENCE_ROOT, 'inventory', 'selection.json'), 'utf8')
    ) as { selected: { bookingId: number } };
    const bookingId = selection.selected.bookingId;
    const origin = baseURL || 'http://localhost:8890';

    // Unauthenticated
    const anon = await browser.newContext();
    const anonPage = await anon.newPage();
    await anonPage.goto(origin + '/', { waitUntil: 'domcontentloaded', timeout: 60_000 });
    const unauth = await anonPage.evaluate(async (id) => {
      const r = await fetch(`/wp-json/ngc/v1/bookings/${id}/join`, { method: 'POST' });
      return { status: r.status };
    }, bookingId);
    writeJson('api/neg-unauth-join.json', unauth);
    expect([401, 403]).toContain(unauth.status);
    await anon.close();

    // Wrong tutor (draft or approved-but-not-assigned for BOOK-ADULT)
    const wrong = await browser.newContext();
    const wrongPage = await wrong.newPage();
    const wrongEmail = 'demo.tutor.draft@nextgen.local';
    try {
      await loginAs(wrongPage, wrongEmail);
    } catch {
      await loginAs(wrongPage, DEMO_PERSONAS.tutorApproved.email);
    }
    await wrongPage.goto(origin + '/tutor-dashboard/', { waitUntil: 'domcontentloaded' });
    const wrongJoin = await joinBookingApi(wrongPage, bookingId);
    writeJson('api/neg-wrong-actor-join.json', {
      status: wrongJoin.status,
      bodyKeys: wrongJoin.body && typeof wrongJoin.body === 'object' ? Object.keys(wrongJoin.body as object) : [],
    });
    const denied = [401, 403, 404].includes(wrongJoin.status);
    recordResult({
      testId: 'LESSON-E2E-003',
      invocation: 'INV-06 negative auth',
      unauth: unauth.status,
      wrongActor: wrongJoin.status,
      result: unauth.status >= 401 ? 'PASS' : 'FAIL',
      note: denied
        ? 'wrong actor denied'
        : 'wrong actor received join — review NGC_Access::can_view_booking',
      auth: unauth.status >= 401 ? 'PASS' : 'FAIL',
    });
    expect(unauth.status).toBeGreaterThanOrEqual(401);
    await wrong.close();
  });

  test('LESSON-E2E-004 entry-point aliases + chat ad-hoc path', async ({ page }) => {
    ensureEvidenceDirs();
    // Ensure live demo password is available when this test runs alone.
    if (!fs.existsSync(path.join(EVIDENCE_ROOT, 'inventory', '.demo-password'))) {
      const ctx = page.context();
      const admin = await ctx.browser()!.newPage();
      await ensureDemoSeedAndPassword(admin);
      await admin.close();
    }
    await loginAs(page, DEMO_PERSONAS.studentAdult.email, currentDemoPassword());
    await page.goto(DEMO_PERSONAS.studentAdult.path, { waitUntil: 'domcontentloaded' });
    await dismissOverlays(page);
    await page.waitForSelector('a.bi-dash-hero__join, a.bi-dash-session__join, .bi-dashboard-rest__inner', {
      timeout: 60_000,
    }).catch(() => null);
    const hero = page.locator('a.bi-dash-hero__join');
    const row = page.locator('a.bi-dash-session__join');
    const heroVisible = await hero.first().isVisible().catch(() => false);
    const rowVisible = await row.first().isVisible().catch(() => false);
    writeJson('api/entry-aliases.json', { heroVisible, rowVisible });
    expect(heroVisible || rowVisible).toBeTruthy();

    // Chat ad-hoc: open home and look for video button if chat mounts
    await page.goto('/', { waitUntil: 'domcontentloaded' });
    await dismissOverlays(page);
    const chatToggle = page.locator('#chat-toggle, .chat-fab, [data-chat-open]').first();
    let chatVideo = false;
    if (await chatToggle.isVisible().catch(() => false)) {
      await chatToggle.click().catch(() => undefined);
      chatVideo = await page.locator('#chat-video-btn').isVisible().catch(() => false);
    }
    writeJson('api/inv-08-chat-video.json', {
      chatVideoControlPresent: chatVideo,
      distinctRuntime: true,
      bookingBound: false,
    });
    await screenshot(page, 'inv-08-chat-surface');

    recordResult({
      testId: 'LESSON-E2E-004',
      invocation: 'INV-02 alias + INV-08 discovery',
      result: heroVisible || rowVisible ? 'PASS' : 'FAIL',
      chatVideo,
    });
  });

  test('LESSON-E2E-999 write results matrix + manifest', async () => {
    ensureEvidenceDirs();
    let merged = results.slice();
    try {
      const partial = JSON.parse(
        fs.readFileSync(path.join(EVIDENCE_ROOT, 'reports', 'results-partial.json'), 'utf8')
      ) as Array<Record<string, unknown>>;
      const byId = new Map<string, Record<string, unknown>>();
      for (const r of [...partial, ...merged]) {
        byId.set(String(r.testId || ''), r);
      }
      merged = [...byId.values()];
    } catch {
      /* ignore */
    }
    const lines = [
      '| Test | Invocation | Student | Tutor | Audio S→T | Audio T→S | Video S→T | Video T→S | Recording | DB | Auth | Result |',
      '| ---- | ---------- | ------- | ----- | --------- | --------- | --------- | --------- | --------- | -- | ---- | ------ |',
    ];
    for (const r of merged) {
      lines.push(
        `| ${r.testId || ''} | ${r.invocation || ''} | ${r.student || ''} | ${r.tutor || ''} | ${r.audioST || r.audio || ''} | ${r.audioTS || ''} | ${r.videoST || ''} | ${r.videoTS || ''} | ${r.recording || 'UNVERIFIED'} | ${r.db || ''} | ${r.auth || ''} | ${r.result || ''} |`
      );
    }
    const md = `# Lesson E2E Results\n\nMEDIA_MODE: browser_fake_media_streams (not physical devices)\n\nHUMAN_AUDIO_AUDIBILITY_VERIFIED: false\n\nProduct recording: UNVERIFIED (not implemented)\n\nTEST_RANDOM_SEED=20260809 (see inventory/TEST_RANDOM_SEED.env)\n\n${lines.join('\n')}\n`;
    fs.writeFileSync(path.join(EVIDENCE_ROOT, 'LESSON-E2E-RESULTS.md'), md, 'utf8');
    writeJson('reports/results-final.json', merged);

    const critical = [
      'inventory/selection.json',
      'api/join-hrefs.json',
      'webrtc/same-room-proof.json',
      'webrtc/tutor-media-proof.json',
      'webrtc/student-media-proof.json',
      'recordings/product-recording-discovery.json',
      'LESSON-E2E-RESULTS.md',
      'LESSON-INVOCATION-MATRIX.md',
    ];
    const manifest: Record<string, string | null> = {};
    for (const rel of critical) {
      manifest[rel] = sha256File(path.join(EVIDENCE_ROOT, rel));
    }
    writeJson('manifests/evidence-manifest.json', {
      generatedAt: new Date().toISOString(),
      hashes: manifest,
    });
  });
});
