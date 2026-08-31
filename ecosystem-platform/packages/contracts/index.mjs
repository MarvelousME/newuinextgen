/**
 * Provider contracts — UI and application layers depend on these shapes only.
 * @package @ecosystem/contracts
 */

/** @typedef {import('./types.mjs').TenantContext} TenantContext */
/** @typedef {import('./types.mjs').Customer} Customer */
/** @typedef {import('./types.mjs').PagedResult} PagedResult */

export const PROVIDER_IDS = {
  ODOO: 'odoo',
  COMPANION: 'companion',
  MEMORY: 'memory',
};

export const IAM_LEVELS = {
  PLATFORM_SUPER_ADMIN: 'L0',
  PLATFORM_OPERATOR: 'L1',
  TENANT_OWNER: 'L2',
  TENANT_ADMIN: 'L3',
  TENANT_MANAGER: 'L4',
  TENANT_USER: 'L5',
  PORTAL_USER: 'L6',
  SERVICE_IDENTITY: 'L7',
};

/**
 * @typedef {object} ICustomerProvider
 * @property {string} providerId
 * @property {(ctx: TenantContext, query?: object) => Promise<PagedResult<Customer>>} list
 * @property {(ctx: TenantContext, id: string) => Promise<Customer|null>} get
 * @property {(ctx: TenantContext, input: Partial<Customer>) => Promise<Customer>} create
 * @property {(ctx: TenantContext, id: string, patch: Partial<Customer>) => Promise<Customer>} update
 * @property {() => Promise<{ status: string }>} health
 */

export {};
