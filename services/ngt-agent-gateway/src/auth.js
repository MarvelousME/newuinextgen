import crypto from 'node:crypto';

/**
 * HMAC signature: headers X-NGT-Timestamp, X-NGT-Signature = hex(hmac_sha256(secret, `${ts}.${method}.${path}`))
 */
export function verifyWpSignature(req, secret) {
  const ts = req.headers['x-ngt-timestamp'];
  const sig = req.headers['x-ngt-signature'];
  if (!ts || !sig) return { ok: false, error: 'missing_signature' };
  const age = Math.abs(Date.now() - Number(ts));
  if (!Number.isFinite(Number(ts)) || age > 5 * 60 * 1000) {
    return { ok: false, error: 'timestamp_skew' };
  }
  const path = req.url?.split('?')[0] || '/';
  const payload = `${ts}.${req.method}.${path}`;
  const expected = crypto.createHmac('sha256', secret).update(payload).digest('hex');
  const a = Buffer.from(String(sig));
  const b = Buffer.from(expected);
  if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) {
    return { ok: false, error: 'bad_signature' };
  }
  return { ok: true };
}

export function signRequest(method, path, secret, ts = Date.now()) {
  const payload = `${ts}.${method}.${path}`;
  return {
    'X-NGT-Timestamp': String(ts),
    'X-NGT-Signature': crypto.createHmac('sha256', secret).update(payload).digest('hex'),
  };
}
