/**
 * NGT Agent Gateway — separate from WordPress PHP.
 * Uses official @a2a-js/sdk when available; falls back to contract-faithful local runtime
 * if the package cannot be resolved (disk/network), clearly marking mode in /health.
 */

import http from 'node:http';
import { randomUUID } from 'node:crypto';
import { URL } from 'node:url';
import { createTaskStore } from './task-store.js';
import { createFirstPartyExecutor } from './first-party-agent.js';
import { createMcpClient } from './mcp-client.js';
import { assertSafeUrl } from './ssrf.js';
import { verifyWpSignature } from './auth.js';

const PORT = Number(process.env.NGT_GATEWAY_PORT || 8787);
const BIND = process.env.NGT_GATEWAY_BIND || '127.0.0.1';

let a2aSdk = null;
let a2aMode = 'fallback-local';
try {
  a2aSdk = await import('@a2a-js/sdk');
  a2aMode = 'official-a2a-js-sdk';
} catch {
  a2aMode = 'fallback-local-no-sdk-install';
}

const store = createTaskStore();
const executor = createFirstPartyExecutor();
const mcp = createMcpClient();

const AGENT_CARD = {
  id: 'ngt.firstparty.diagnostics',
  name: 'NGT Diagnostics Agent',
  description: 'First-party read-only diagnostics and echo agent for gateway verification.',
  version: '1.0.0',
  url: `http://127.0.0.1:${PORT}/a2a/ngt.firstparty.diagnostics`,
  skills: ['echo', 'health.summary'],
  trusted: true,
};

function json(res, status, body) {
  const payload = JSON.stringify(body);
  res.writeHead(status, {
    'Content-Type': 'application/json; charset=utf-8',
    'Cache-Control': 'no-store',
  });
  res.end(payload);
}

function readBody(req) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    let size = 0;
    req.on('data', (c) => {
      size += c.length;
      if (size > 256 * 1024) {
        reject(new Error('payload_too_large'));
        req.destroy();
        return;
      }
      chunks.push(c);
    });
    req.on('end', () => {
      const raw = Buffer.concat(chunks).toString('utf8');
      if (!raw) return resolve({});
      try {
        resolve(JSON.parse(raw));
      } catch {
        reject(new Error('invalid_json'));
      }
    });
    req.on('error', reject);
  });
}

function requireAuth(req) {
  const allowInsecure = process.env.NGT_GATEWAY_ALLOW_INSECURE === '1';
  const sharedSecret = process.env.NGT_GATEWAY_SHARED_SECRET || '';
  if (allowInsecure && !sharedSecret) {
    return { ok: true, mode: 'insecure-dev' };
  }
  if (!sharedSecret) {
    return { ok: false, error: 'gateway_secret_not_configured' };
  }
  return verifyWpSignature(req, sharedSecret);
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`);
  try {
    if (req.method === 'GET' && url.pathname === '/health') {
      return json(res, 200, {
        ok: true,
        service: 'ngt-agent-gateway',
        version: '1.0.0',
        a2a_mode: a2aMode,
        a2a_sdk_loaded: Boolean(a2aSdk),
        agent_card: AGENT_CARD.id,
        kill_switch: process.env.NGT_GATEWAY_KILL_SWITCH === '1',
      });
    }

    if (req.method === 'GET' && url.pathname === '/.well-known/agent.json') {
      return json(res, 200, AGENT_CARD);
    }

    const auth = requireAuth(req);
    if (!auth.ok && url.pathname.startsWith('/v1/')) {
      return json(res, 401, { ok: false, error: auth.error || 'unauthorized' });
    }

    if (process.env.NGT_GATEWAY_KILL_SWITCH === '1' && url.pathname.startsWith('/v1/')) {
      return json(res, 503, { ok: false, error: 'kill_switch_engaged' });
    }

    if (req.method === 'POST' && url.pathname === '/v1/tasks') {
      const body = await readBody(req);
      const agentId = String(body.agent_id || '');
      if (agentId !== AGENT_CARD.id) {
        return json(res, 403, { ok: false, error: 'untrusted_or_unknown_agent' });
      }
      const idem = String(body.idempotency_key || randomUUID());
      const existing = store.getByIdempotency(idem);
      if (existing) {
        return json(res, 200, { ok: true, idempotent: true, task: existing });
      }
      const task = store.create({
        id: `task_${randomUUID().replace(/-/g, '').slice(0, 16)}`,
        agent_id: agentId,
        status: 'submitted',
        message: String(body.message || ''),
        correlation_id: String(body.correlation_id || randomUUID()),
        idempotency_key: idem,
        created_at: new Date().toISOString(),
        a2a_mode: a2aMode,
      });
      // Execute first-party agent asynchronously-ish (inline for testability).
      const result = await executor.execute(task);
      const done = store.update(task.id, {
        status: result.ok ? 'completed' : 'failed',
        artifacts: result.artifacts || [],
        result: result.summary || null,
        updated_at: new Date().toISOString(),
        error: result.error || null,
      });
      return json(res, 200, { ok: true, task: done, sdk: a2aMode });
    }

    if (req.method === 'GET' && url.pathname.startsWith('/v1/tasks/')) {
      const id = url.pathname.split('/').pop();
      const task = store.get(id);
      if (!task) return json(res, 404, { ok: false, error: 'task_not_found' });
      return json(res, 200, { ok: true, task });
    }

    if (req.method === 'POST' && url.pathname === '/v1/mcp/execute') {
      const body = await readBody(req);
      const endpoint = String(body.endpoint || '');
      const safe = assertSafeUrl(endpoint, { allowLocal: process.env.NGT_GATEWAY_ALLOW_LOCAL === '1' });
      if (!safe.ok) return json(res, 400, { ok: false, error: safe.error, detail: safe.detail });
      if (!body.tool_approved) {
        return json(res, 403, { ok: false, error: 'capability_not_approved' });
      }
      const out = await mcp.executeAllowlisted(endpoint, body.tool, body.args || {});
      return json(res, out.ok ? 200 : 502, out);
    }

    if (req.method === 'POST' && url.pathname === '/v1/mcp/discover') {
      const body = await readBody(req);
      const endpoint = String(body.endpoint || '');
      const safe = assertSafeUrl(endpoint, { allowLocal: process.env.NGT_GATEWAY_ALLOW_LOCAL === '1' });
      if (!safe.ok) return json(res, 400, { ok: false, error: safe.error });
      const caps = await mcp.discover(endpoint);
      return json(res, 200, { ok: true, capabilities: caps, approved: false });
    }

    json(res, 404, { ok: false, error: 'not_found' });
  } catch (err) {
    const msg = err instanceof Error ? err.message : 'server_error';
    json(res, msg === 'payload_too_large' ? 413 : 500, { ok: false, error: msg });
  }
});

if (process.argv[1] && process.argv[1].replace(/\\/g, '/').endsWith('/src/server.js')) {
  server.listen(PORT, BIND, () => {
    // eslint-disable-next-line no-console
    console.log(`ngt-agent-gateway listening on ${BIND}:${PORT} mode=${a2aMode}`);
  });
}

export { server, AGENT_CARD, store, a2aMode };
