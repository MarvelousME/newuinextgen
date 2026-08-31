/** Secrets that must never be used as Odoo master or admin passwords. */
const WEAK_ODOO_SECRETS = new Set(['', 'admin', 'odoo', 'changeme', 'password', 'secret']);

/**
 * @param {unknown} value
 * @param {string} name
 * @returns {string}
 */
export function requireOdooSecret(value, name) {
  const trimmed = String(value || '').trim();
  if (!trimmed || WEAK_ODOO_SECRETS.has(trimmed.toLowerCase())) {
    const err = new Error(`${name} is missing or too weak — set it in docker/.env (never commit it)`);
    err.code = 'ODOO_SECRET_REQUIRED';
    throw err;
  }
  return trimmed;
}

/**
 * Login name may stay "admin"; passwords may not.
 * @param {unknown} value
 * @returns {string}
 */
export function resolveOdooLogin(value) {
  const trimmed = String(value || '').trim();
  return trimmed || 'admin';
}
