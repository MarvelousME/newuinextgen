import { IAM_LEVELS } from '@ecosystem/contracts';
import { PERMISSIONS_BY_LEVEL } from './economic-catalog.mjs';

export class IamService {
  /** @param {{ tenantStore: import('./tenant-store.mjs').TenantStore; audit: import('./audit-log.mjs').AuditLog }} deps */
  constructor(deps) {
    this.tenantStore = deps.tenantStore;
    this.audit = deps.audit;
    this.memberships = new Map();
    this.superAdmins = new Set(
      (process.env.ECOSYSTEM_SUPER_ADMIN_IDS || 'platform-super-admin').split(',').map((s) => s.trim())
    );
  }

  /** @param {string} userId @param {string} tenantId @param {string} role */
  assignMembership(userId, tenantId, role = IAM_LEVELS.TENANT_OWNER) {
    const key = `${userId}:${tenantId}`;
    this.memberships.set(key, { userId, tenantId, role });
    this.audit.append({
      actorId: userId,
      tenantId,
      action: 'iam.membership.assign',
      resource: tenantId,
      result: 'ok',
      meta: { role },
    });
  }

  /** @param {string} userId */
  isPlatformSuperAdmin(userId) {
    return this.superAdmins.has(userId);
  }

  /**
   * @param {string} userId @param {string} tenantId
   */
  canAccessTenant(userId, tenantId) {
    if (this.isPlatformSuperAdmin(userId)) {
      return true;
    }
    return this.memberships.has(`${userId}:${tenantId}`);
  }

  /** @param {string} userId @param {string} tenantId */
  getRole(userId, tenantId) {
    if (this.isPlatformSuperAdmin(userId)) {
      return IAM_LEVELS.PLATFORM_SUPER_ADMIN;
    }
    const m = this.memberships.get(`${userId}:${tenantId}`);
    return m?.role || null;
  }

  /**
   * Capability-style permission check. Prefer this over role === 'admin'.
   * @param {string} userId
   * @param {string} permission
   * @param {string} [tenantId]
   */
  can(userId, permission, tenantId = '') {
    if (this.isPlatformSuperAdmin(userId)) {
      return true;
    }
    const role = tenantId ? this.getRole(userId, tenantId) : null;
    if (!role) {
      return false;
    }
    const granted = PERMISSIONS_BY_LEVEL[role] || [];
    return granted.includes('*') || granted.includes(permission);
  }
}
