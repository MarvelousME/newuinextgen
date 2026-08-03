/**
 * End-to-end gateway HTTP test (no external network).
 */
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { server, AGENT_CARD } from '../src/server.js';
import { signRequest } from '../src/auth.js';

function listen() {
  return new Promise((resolve) => {
    server.listen(0, '127.0.0.1', () => {
      const addr = server.address();
      resolve(addr.port);
    });
  });
}

test('gateway health + authenticated A2A task + unauthorized agent', async (t) => {
  process.env.NGT_GATEWAY_SHARED_SECRET = 'e2e-secret';
  process.env.NGT_GATEWAY_ALLOW_INSECURE = '0';
  const port = await listen();
  t.after(() => new Promise((r) => server.close(() => r())));

  const health = await fetch(`http://127.0.0.1:${port}/health`);
  assert.equal(health.status, 200);
  const hj = await health.json();
  assert.equal(hj.ok, true);
  assert.ok(hj.a2a_mode);

  const path = '/v1/tasks';
  const headers = {
    'Content-Type': 'application/json',
    ...signRequest('POST', path, 'e2e-secret'),
  };
  const res = await fetch(`http://127.0.0.1:${port}${path}`, {
    method: 'POST',
    headers,
    body: JSON.stringify({
      agent_id: AGENT_CARD.id,
      message: 'Subject expertise: Mathematics in Gauteng',
      idempotency_key: 'e2e-1',
    }),
  });
  assert.equal(res.status, 200);
  const body = await res.json();
  assert.equal(body.ok, true);
  assert.equal(body.task.status, 'completed');

  const bad = await fetch(`http://127.0.0.1:${port}${path}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...signRequest('POST', path, 'e2e-secret'),
    },
    body: JSON.stringify({ agent_id: 'evil.agent', message: 'hi', idempotency_key: 'e2e-2' }),
  });
  assert.equal(bad.status, 403);

  const unauth = await fetch(`http://127.0.0.1:${port}${path}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ agent_id: AGENT_CARD.id, message: 'x', idempotency_key: 'e2e-3' }),
  });
  assert.equal(unauth.status, 401);

  const mcp = await fetch(`http://127.0.0.1:${port}/v1/mcp/execute`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...signRequest('POST', '/v1/mcp/execute', 'e2e-secret'),
    },
    body: JSON.stringify({
      endpoint: 'https://example.com/mcp',
      tool: 'ping',
      tool_approved: true,
    }),
  });
  assert.equal(mcp.status, 200);
  const mj = await mcp.json();
  assert.equal(mj.ok, true);

  const drift = await fetch(`http://127.0.0.1:${port}/v1/mcp/execute`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...signRequest('POST', '/v1/mcp/execute', 'e2e-secret'),
    },
    body: JSON.stringify({
      endpoint: 'https://example.com/mcp',
      tool: 'danger.shell',
      tool_approved: true,
    }),
  });
  const dj = await drift.json();
  assert.equal(dj.ok, false);
  assert.equal(dj.error, 'tool_not_allowlisted');
});
