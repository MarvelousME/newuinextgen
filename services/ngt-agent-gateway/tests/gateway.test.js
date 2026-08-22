import { test } from 'node:test';
import assert from 'node:assert/strict';
import { assertSafeUrl, assertSafeUrlNoRedirect } from '../src/ssrf.js';
import { createFirstPartyExecutor } from '../src/first-party-agent.js';
import { createTaskStore } from '../src/task-store.js';
import { createMcpClient } from '../src/mcp-client.js';
import { signRequest, verifyWpSignature } from '../src/auth.js';

test('SSRF blocks metadata IP', () => {
  const r = assertSafeUrl('http://169.254.169.254/latest/meta-data/');
  assert.equal(r.ok, false);
});

test('SSRF blocks dword IP encoding', () => {
  const r = assertSafeUrl('https://2130706433/'); // 127.0.0.1 as dword
  assert.equal(r.ok, false);
});

test('SSRF blocks localhost', () => {
  const r = assertSafeUrl('https://localhost/mcp');
  assert.equal(r.ok, false);
});

test('SSRF allows public HTTPS', () => {
  const r = assertSafeUrl('https://example.com/mcp');
  assert.equal(r.ok, true);
});

test('SSRF allows local HTTP when allowLocal', () => {
  const r = assertSafeUrl('http://127.0.0.1:9090/mcp', { allowLocal: true });
  assert.equal(r.ok, true);
});

test('SSRF blocks hex dword encoding', () => {
  const r = assertSafeUrl('https://0x7f000001/mcp');
  assert.equal(r.ok, false);
});

test('SSRF blocks octal encoding', () => {
  const r = assertSafeUrl('https://017700000001/mcp');
  assert.equal(r.ok, false);
});

test('SSRF blocks dotted hex encoding', () => {
  const r = assertSafeUrl('https://0x7f.0x0.0x0.0x1/mcp');
  assert.equal(r.ok, false);
});

test('SSRF blocks userinfo credentials', () => {
  const r = assertSafeUrl('https://user:pass@example.com/mcp');
  assert.equal(r.ok, false);
  assert.equal(r.error, 'credentials_in_url');
});

test('SSRF no-redirect helper rejects Location', () => {
  const r = assertSafeUrlNoRedirect('https://example.com/mcp', {}, 'https://169.254.169.254/');
  assert.equal(r.ok, false);
  assert.equal(r.error, 'redirect_forbidden');
});

test('SSRF no-redirect helper allows when Location absent', () => {
  const r = assertSafeUrlNoRedirect('https://example.com/mcp', {}, null);
  assert.equal(r.ok, true);
});

test('first-party agent rejects protected traits', async () => {
  const ex = createFirstPartyExecutor();
  const r = await ex.execute({ message: 'Find tutors by ethnicity' });
  assert.equal(r.ok, false);
  assert.equal(r.error, 'protected_trait_rejected');
});

test('task store idempotency', () => {
  const store = createTaskStore();
  const t = store.create({ id: 't1', idempotency_key: 'abc', status: 'submitted' });
  assert.equal(store.getByIdempotency('abc').id, 't1');
  store.update('t1', { status: 'completed' });
  assert.equal(store.get('t1').status, 'completed');
  assert.ok(t);
});

test('MCP allowlist blocks danger.shell', async () => {
  const mcp = createMcpClient();
  const r = await mcp.executeAllowlisted('https://example.com', 'danger.shell', {});
  assert.equal(r.ok, false);
  assert.equal(r.error, 'tool_not_allowlisted');
});

test('MCP discover uses live tools/list and still blocks danger.shell', async () => {
  const mcp = createMcpClient({
    fetchImpl: async (_url, init) => {
      const body = JSON.parse(init.body);
      if (body.method === 'initialize') {
        return {
          ok: true,
          json: async () => ({
            jsonrpc: '2.0',
            id: 1,
            result: { protocolVersion: '2024-11-05', serverInfo: { name: 'mock' } },
          }),
        };
      }
      if (body.method === 'tools/list') {
        return {
          ok: true,
          json: async () => ({
            jsonrpc: '2.0',
            id: 1,
            result: {
              tools: [
                { name: 'ping', description: 'pong' },
                { name: 'danger.shell', description: 'must remain unapproved' },
              ],
            },
          }),
        };
      }
      return { ok: false, status: 404, json: async () => ({}) };
    },
  });
  const caps = await mcp.discover('https://example.com/mcp');
  assert.equal(caps.mode, 'live');
  assert.ok(caps.tools.some((t) => t.name === 'danger.shell'));
  const r = await mcp.executeAllowlisted('https://example.com', 'danger.shell', {});
  assert.equal(r.ok, false);
});

test('MCP discover failure returns empty tools, not invented stubs', async () => {
  const mcp = createMcpClient({
    fetchImpl: async () => {
      throw new Error('network');
    },
  });
  const caps = await mcp.discover('https://example.com/mcp');
  assert.equal(caps.mode, 'failed');
  assert.equal(caps.tools.length, 0);
});

test('HMAC auth accepts valid signature', () => {
  const secret = 'test-secret';
  const headers = signRequest('GET', '/v1/tasks/x', secret, 1_700_000_000_000);
  const req = { method: 'GET', url: '/v1/tasks/x', headers: { 'x-ngt-timestamp': headers['X-NGT-Timestamp'], 'x-ngt-signature': headers['X-NGT-Signature'] } };
  // Force skew window by patching Date — use fresh timestamp instead
  const now = Date.now();
  const h2 = signRequest('POST', '/v1/tasks', secret, now);
  const req2 = {
    method: 'POST',
    url: '/v1/tasks',
    headers: { 'x-ngt-timestamp': h2['X-NGT-Timestamp'], 'x-ngt-signature': h2['X-NGT-Signature'] },
  };
  assert.equal(verifyWpSignature(req2, secret).ok, true);
});

test('HMAC auth rejects bad signature', () => {
  const req = {
    method: 'POST',
    url: '/v1/tasks',
    headers: { 'x-ngt-timestamp': String(Date.now()), 'x-ngt-signature': '00'.repeat(32) },
  };
  assert.equal(verifyWpSignature(req, 'test-secret').ok, false);
});
