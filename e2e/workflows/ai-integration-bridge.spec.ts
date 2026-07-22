/**
 * AI Integration bridge — Companion event → NGTAI outbox → signed callback → recommendation surface.
 *
 * Coverage:
 *  1. Public health endpoint shape (no secrets leaked).
 *  2. Signed agent-result callback: accepted once, idempotent on replay, rejected when tampered.
 *  3. Admin AI Integration pages render with data-testid hooks.
 *
 * The signed-callback tests need the shared HMAC secret used by the local stack.
 * Provide it via env (never commit real values):
 *   NGTAI_TEST_KEY_ID=local-dev NGTAI_TEST_SECRET=... npm run test:ai-bridge
 * Without those env vars the callback tests are skipped, not faked.
 */
import { createHash, createHmac, randomUUID } from 'node:crypto';
import { test, expect, request as pwRequest, type APIRequestContext } from '@playwright/test';
import { wpLogin } from '../helpers';

const BASE_URL = process.env.BASE_URL || 'http://localhost:8900';
const KEY_ID = process.env.NGTAI_TEST_KEY_ID || '';
const SECRET = process.env.NGTAI_TEST_SECRET || '';
const CALLBACK_PATH = '/wp-json/ngtai/v1/callbacks/agent-result';

function signedHeaders(method: string, path: string, rawBody: string, overrides: Record<string, string> = {}) {
  const timestamp = String(Math.floor(Date.now() / 1000));
  const nonce = randomUUID();
  const bodySha = createHash('sha256').update(rawBody, 'utf8').digest('hex');
  const canonical = [timestamp, nonce, method.toUpperCase(), path, bodySha].join('\n');
  const signature = createHmac('sha256', SECRET).update(canonical, 'utf8').digest('hex');
  return {
    'Content-Type': 'application/json',
    'X-NGT-Timestamp': timestamp,
    'X-NGT-Nonce': nonce,
    'X-NGT-Signature': `v1=${signature}`,
    'X-NGT-Key-Id': KEY_ID,
    'X-NGT-Correlation-ID': randomUUID(),
    'X-NGT-Request-ID': randomUUID(),
    'Idempotency-Key': randomUUID(),
    ...overrides,
  };
}

function agentResultBody(runId: string) {
  return JSON.stringify({
    agent_run_id: runId,
    result_version: 1,
    event_id: `evt-e2e-${runId}`,
    correlation_id: randomUUID(),
    agent_name: 'match-ranker',
    action_name: 'match.recommendation',
    status: 'succeeded',
    policy_decision: 'ALLOW',
    approval_id: null,
    result: {
      ranked_candidates: [
        { tutor_id: 2001, score: 0.93, verified: true, eligible: true },
        { tutor_id: 2002, score: 0.81, verified: true, eligible: true },
      ],
      explanation: 'Subject overlap and availability fit (e2e fixture).',
    },
    error: null,
    completed_at: new Date().toISOString(),
  });
}

test.describe('AI Integration bridge', () => {
  let api: APIRequestContext;

  test.beforeAll(async () => {
    api = await pwRequest.newContext({ baseURL: BASE_URL });
  });

  test.afterAll(async () => {
    await api.dispose();
  });

  test('public health endpoint exposes status without secrets', async () => {
    const res = await api.get('/wp-json/ngtai/v1/health');
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body).toHaveProperty('status');
    expect(body).toHaveProperty('version');
    expect(body).toHaveProperty('companion');
    expect(body).toHaveProperty('outbox');
    expect(body.outbox).toHaveProperty('pending');
    const raw = JSON.stringify(body).toLowerCase();
    expect(raw).not.toContain('secret');
    expect(raw).not.toContain('hmac');
  });

  test.describe('signed agent-result callback', () => {
    test.skip(!KEY_ID || !SECRET, 'NGTAI_TEST_KEY_ID / NGTAI_TEST_SECRET not provided');

    test('accepts a correctly signed callback, replays idempotently, rejects tampering', async () => {
      const runId = `e2e-${Date.now()}`;
      const body = agentResultBody(runId);
      const headers = signedHeaders('POST', CALLBACK_PATH, body);

      const first = await api.post(CALLBACK_PATH, { headers, data: body });
      expect(first.status(), await first.text()).toBe(200);
      const firstJson = await first.json();
      expect(firstJson.success).toBe(true);

      // Exact replay (same nonce + signature) must be acknowledged idempotently.
      const replay = await api.post(CALLBACK_PATH, { headers, data: body });
      expect(replay.status()).toBe(200);
      const replayJson = await replay.json();
      expect(replayJson.idempotent).toBe(true);

      // Tampered body with the old signature must be rejected.
      const tampered = body.replace('"score":0.93', '"score":0.99');
      const bad = await api.post(CALLBACK_PATH, {
        headers: signedHeaders('POST', CALLBACK_PATH, body), // signed over ORIGINAL body
        data: tampered,
      });
      expect([400, 401, 403]).toContain(bad.status());
    });
  });

  test('admin AI Integration screens render', async ({ page }) => {
    await wpLogin(page);
    for (const slug of ['ngtai-settings', 'ngtai-health', 'ngtai-events', 'ngtai-approvals']) {
      const res = await page.goto(`${BASE_URL}/wp-admin/admin.php?page=${slug}`, {
        waitUntil: 'domcontentloaded',
      });
      expect(res?.status(), `page=${slug}`).toBeLessThan(500);
      // Page must not be a permissions error for admin.
      await expect(page.locator('body')).not.toContainText('You need a higher level of permission');
    }
  });
});
