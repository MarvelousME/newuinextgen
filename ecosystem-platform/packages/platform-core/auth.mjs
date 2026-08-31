import crypto from 'node:crypto';

/** @type {Set<string>} */
const WEAK_TOKENS = new Set([
  '',
  'platform-super-admin',
  'admin',
  'changeme',
  'password',
  'secret',
]);

/**
 * Configured API bearer token (trimmed).
 * @returns {string}
 */
export function getApiToken() {
  return String(process.env.ECOSYSTEM_API_TOKEN || '').trim();
}

/**
 * Whether a non-weak bearer token is configured.
 * @returns {boolean}
 */
export function isTokenConfigured() {
  const token = getApiToken();
  return token.length > 0 && !WEAK_TOKENS.has(token);
}

/**
 * Internal platform principal — never supplied by clients.
 * @returns {string}
 */
export function getPlatformPrincipalId() {
  const ids = (process.env.ECOSYSTEM_SUPER_ADMIN_IDS || 'platform-super-admin')
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);
  return ids[0] || 'platform-super-admin';
}

/**
 * @param {string} a
 * @param {string} b
 * @returns {boolean}
 */
function timingSafeEqualString(a, b) {
  const bufA = Buffer.from(a);
  const bufB = Buffer.from(b);
  if (bufA.length !== bufB.length) {
    return false;
  }
  return crypto.timingSafeEqual(bufA, bufB);
}

/**
 * @param {import('node:http').IncomingMessage} req
 * @returns {string|null}
 */
function extractBearerToken(req) {
  const auth = req.headers.authorization || req.headers.Authorization;
  if (!auth || typeof auth !== 'string') {
    return null;
  }
  const match = auth.match(/^Bearer\s+(.+)$/i);
  return match ? match[1].trim() : null;
}

/**
 * Authenticate request via Authorization Bearer token.
 * Client X-User-Id is never used for privilege.
 *
 * @param {import('node:http').IncomingMessage} req
 * @returns {{ ok: true, userId: string } | { ok: false, status: number, error: string }}
 */
export function authenticateRequest(req) {
  if (!isTokenConfigured()) {
    return { ok: false, status: 401, error: 'API token not configured' };
  }

  const token = extractBearerToken(req);
  if (!token) {
    return { ok: false, status: 401, error: 'Bearer token required' };
  }

  if (!timingSafeEqualString(token, getApiToken())) {
    return { ok: false, status: 401, error: 'invalid token' };
  }

  return { ok: true, userId: getPlatformPrincipalId() };
}
