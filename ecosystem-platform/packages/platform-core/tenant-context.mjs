import crypto from 'node:crypto';

/**
 * Resolve tenant context from request headers — never trust tenantId without membership check.
 * @param {import('node:http').IncomingMessage} req
 * @param {import('./iam.mjs').IamService} iam
 * @param {import('./tenant-store.mjs').TenantStore} tenantStore
 * @param {{ userId?: string }} [options]
 */
export function resolveTenantContext(req, iam, tenantStore, options = {}) {
  const correlationId =
    req.headers['x-correlation-id'] ||
    req.headers['x-request-id'] ||
    crypto.randomUUID();

  const userId = options.userId ? String(options.userId) : '';
  if (!userId) {
    const err = new Error('authentication required');
    err.code = 'AUTH_REQUIRED';
    throw err;
  }
  const tenantHeader = String(req.headers['x-tenant-id'] || '');
  const tenantSlug = String(req.headers['x-tenant-slug'] || '');

  let tenant = tenantHeader ? tenantStore.get(tenantHeader) : null;
  if (!tenant && tenantSlug) {
    tenant = tenantStore.getBySlug(tenantSlug);
  }

  if (tenant && !iam.canAccessTenant(userId, tenant.id)) {
    const err = new Error('tenant access denied');
    err.code = 'TENANT_FORBIDDEN';
    throw err;
  }

  if (tenant?.status === 'suspended') {
    const err = new Error('tenant suspended');
    err.code = 'TENANT_SUSPENDED';
    throw err;
  }

  return {
    tenantId: tenant?.id || '',
    userId,
    role: tenant ? iam.getRole(userId, tenant.id) : iam.isPlatformSuperAdmin(userId) ? 'L0' : '',
    correlationId: String(correlationId),
    odooDatabase: tenant?.odooDatabase || '',
  };
}
