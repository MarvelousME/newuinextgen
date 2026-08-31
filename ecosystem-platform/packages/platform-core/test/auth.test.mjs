/**
 * Platform auth tests — Bearer token, fail-closed, X-User-Id spoof resistance.
 */
import assert from 'node:assert/strict';
import {
  authenticateRequest,
  getPlatformPrincipalId,
  isTokenConfigured,
} from '../auth.mjs';
import { resolveTenantContext } from '../tenant-context.mjs';
import { createPlatformCore } from '../index.mjs';
import { createOdooAdapters } from '@ecosystem/odoo-adapter';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

const STRONG_TOKEN = 'ecosystem-test-token-' + Date.now();

/**
 * @param {Record<string, string>} [headers]
 * @returns {import('node:http').IncomingMessage}
 */
function mockReq(headers = {}) {
  return /** @type {import('node:http').IncomingMessage} */ ({
    headers,
  });
}

/**
 * @param {{ pass: number, fail: number }} stats
 */
export async function runAuthTests(stats) {
  const test = (name, fn) => {
    try {
      fn();
      console.log(`OK  ${name}`);
      stats.pass += 1;
    } catch (e) {
      console.error(`FAIL ${name}:`, e.message);
      stats.fail += 1;
    }
  };

  const savedToken = process.env.ECOSYSTEM_API_TOKEN;

  test('empty token is not configured', () => {
    process.env.ECOSYSTEM_API_TOKEN = '';
    assert.equal(isTokenConfigured(), false);
  });

  test('weak sentinel token is not configured', () => {
    process.env.ECOSYSTEM_API_TOKEN = 'platform-super-admin';
    assert.equal(isTokenConfigured(), false);
  });

  test('strong token is configured', () => {
    process.env.ECOSYSTEM_API_TOKEN = STRONG_TOKEN;
    assert.equal(isTokenConfigured(), true);
  });

  test('missing Bearer is denied', () => {
    process.env.ECOSYSTEM_API_TOKEN = STRONG_TOKEN;
    const result = authenticateRequest(mockReq({ 'x-user-id': 'platform-super-admin' }));
    assert.equal(result.ok, false);
    if (!result.ok) {
      assert.equal(result.status, 401);
    }
  });

  test('wrong Bearer is denied', () => {
    process.env.ECOSYSTEM_API_TOKEN = STRONG_TOKEN;
    const result = authenticateRequest(
      mockReq({ authorization: 'Bearer wrong-token-value' })
    );
    assert.equal(result.ok, false);
    if (!result.ok) {
      assert.equal(result.status, 401);
    }
  });

  test('valid Bearer yields platform principal not client header', () => {
    process.env.ECOSYSTEM_API_TOKEN = STRONG_TOKEN;
    process.env.ECOSYSTEM_SUPER_ADMIN_IDS = 'internal-platform-principal';
    const result = authenticateRequest(
      mockReq({
        authorization: `Bearer ${STRONG_TOKEN}`,
        'x-user-id': 'attacker-spoof',
      })
    );
    assert.equal(result.ok, true);
    if (result.ok) {
      assert.equal(result.userId, 'internal-platform-principal');
      assert.notEqual(result.userId, 'attacker-spoof');
    }
  });

  test('X-User-Id spoof without Bearer is denied', () => {
    process.env.ECOSYSTEM_API_TOKEN = STRONG_TOKEN;
    const result = authenticateRequest(
      mockReq({ 'x-user-id': getPlatformPrincipalId() })
    );
    assert.equal(result.ok, false);
  });

  test('tenant context rejects missing principal', () => {
    const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'ecosystem-auth-'));
    process.env.ECOSYSTEM_DATA_DIR = tmp;
    const odoo = createOdooAdapters();
    const core = createPlatformCore({ odooProvisioner: odoo.provisioner, dataDir: tmp });
    const req = mockReq({ 'x-user-id': 'spoofed-user', 'x-tenant-id': 't1' });
    assert.throws(
      () => resolveTenantContext(req, core.iam, core.tenantStore),
      (err) => err.code === 'AUTH_REQUIRED'
    );
  });

  test('tenant context uses authenticated principal not x-user-id', () => {
    const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'ecosystem-auth-ctx-'));
    process.env.ECOSYSTEM_DATA_DIR = tmp;
    process.env.ECOSYSTEM_SUPER_ADMIN_IDS = 'platform-super-admin';
    const odoo = createOdooAdapters();
    const core = createPlatformCore({ odooProvisioner: odoo.provisioner, dataDir: tmp });
    const tenant = core.tenantStore.create({ slug: 'auth-test', name: 'Auth Test' });
    const req = mockReq({ 'x-user-id': 'spoofed-user', 'x-tenant-id': tenant.id });
    const ctx = resolveTenantContext(req, core.iam, core.tenantStore, {
      userId: 'platform-super-admin',
    });
    assert.equal(ctx.userId, 'platform-super-admin');
    assert.notEqual(ctx.userId, 'spoofed-user');
  });

  process.env.ECOSYSTEM_API_TOKEN = savedToken;
}
