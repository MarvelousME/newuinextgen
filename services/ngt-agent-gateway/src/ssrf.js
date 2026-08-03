/**
 * SSRF guards for MCP endpoints (Gateway-side, connection-time).
 * Complements WordPress NGC_Mcp_Ssrf.
 *
 * Policy notes (callers must honour):
 * - userinfo (username/password) in URLs is rejected.
 * - Hex / octal / dword / dotted-encoded hosts are blocked before DNS.
 * - HTTP redirects MUST NOT be followed. This module does not perform fetch;
 *   callers using undici/node fetch must pass `redirect: 'manual'` or
 *   `redirect: 'error'` (never `'follow'`). See assertSafeUrlNoRedirect.
 */

import net from 'node:net';
import dns from 'node:dns/promises';

function isBlockedIp(ip) {
  if (!net.isIP(ip)) return true;
  // IPv4 private / link-local / metadata
  if (net.isIPv4(ip)) {
    const parts = ip.split('.').map(Number);
    const [a, b] = parts;
    if (a === 10) return true;
    if (a === 127) return true;
    if (a === 0) return true;
    if (a === 169 && b === 254) return true;
    if (a === 172 && b >= 16 && b <= 31) return true;
    if (a === 192 && b === 168) return true;
    if (a === 100 && b >= 64 && b <= 127) return true; // CGNAT
    return false;
  }
  const lower = ip.toLowerCase();
  if (lower === '::1') return true;
  if (lower.startsWith('fc') || lower.startsWith('fd')) return true; // ULA
  if (lower.startsWith('fe80')) return true;
  return false;
}

function hostLooksDangerous(host) {
  const h = host.toLowerCase().replace(/^\[|\]$/g, '');
  if (h === 'localhost' || h === 'metadata' || h === 'metadata.google.internal') return true;
  if (h.endsWith('.local') || h.endsWith('.internal') || h.endsWith('.localhost')) return true;
  // Decimal / octal / hex IP encodings often used in SSRF bypasses
  if (/^0x[0-9a-f]+$/i.test(h)) return true;
  if (/^0[0-7]+$/.test(h)) return true;
  if (/^\d+$/.test(h) && Number(h) > 0) return true; // dword IP
  // Dotted hex/octal (0x7f.0.0.1, 0177.0.0.1) and short forms (127.1)
  if (/^(?:0x[0-9a-f]+|0[0-7]*|\d+)(?:\.(?:0x[0-9a-f]+|0[0-7]*|\d+))+$/i.test(h)) {
    const parts = h.split('.');
    const hasEncoded = parts.some(
      (p) => /^0x/i.test(p) || (/^0[0-7]+$/.test(p) && p !== '0'),
    );
    if (hasEncoded) return true;
    if (parts.length !== 4 && !net.isIP(h)) return true;
  }
  return false;
}

export function assertSafeUrl(raw, { allowLocal = false } = {}) {
  let u;
  try {
    u = new URL(String(raw || ''));
  } catch {
    return { ok: false, error: 'invalid_url' };
  }
  if (u.username || u.password) {
    return { ok: false, error: 'credentials_in_url' };
  }
  if (!['https:', 'http:'].includes(u.protocol)) {
    return { ok: false, error: 'bad_scheme' };
  }
  if (u.protocol === 'http:' && !allowLocal) {
    return { ok: false, error: 'https_required' };
  }
  const host = u.hostname;
  if (hostLooksDangerous(host) && !allowLocal) {
    return { ok: false, error: 'blocked_host', detail: host };
  }
  if (net.isIP(host) && isBlockedIp(host) && !allowLocal) {
    return { ok: false, error: 'blocked_ip', detail: host };
  }
  return { ok: true, url: u.toString(), host };
}

/**
 * Same as assertSafeUrl, plus reject any Location header so callers cannot
 * chain-follow redirects into private networks. Prefer redirect:'manual'|'error'
 * on fetch; if a Location was already observed, pass it here to fail closed.
 */
export function assertSafeUrlNoRedirect(raw, opts = {}, locationHeader = null) {
  const base = assertSafeUrl(raw, opts);
  if (!base.ok) return base;
  if (locationHeader != null && String(locationHeader).trim() !== '') {
    return { ok: false, error: 'redirect_forbidden', detail: String(locationHeader) };
  }
  return base;
}

export async function assertSafeUrlResolved(raw, opts = {}) {
  const base = assertSafeUrl(raw, opts);
  if (!base.ok) return base;
  const host = new URL(base.url).hostname;
  if (net.isIP(host)) return base;
  try {
    const records = await dns.lookup(host, { all: true });
    for (const r of records) {
      if (isBlockedIp(r.address) && !opts.allowLocal) {
        return { ok: false, error: 'ssrf_resolved_ip', detail: r.address };
      }
    }
  } catch (e) {
    return { ok: false, error: 'dns_failed', detail: String(e.message || e) };
  }
  return base;
}
