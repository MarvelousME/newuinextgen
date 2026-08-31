/**
 * Shared contract types (JSDoc).
 */

export const TenantStatus = {
  PENDING: 'pending',
  PROVISIONING: 'provisioning',
  ACTIVE: 'active',
  SUSPENDED: 'suspended',
  ARCHIVED: 'archived',
};

/**
 * @typedef {object} TenantContext
 * @property {string} tenantId
 * @property {string} [userId]
 * @property {string} [role]
 * @property {string} correlationId
 * @property {string} [odooDatabase]
 */

/**
 * @typedef {object} Customer
 * @property {string} id
 * @property {string} name
 * @property {string} [email]
 * @property {string} [phone]
 * @property {string} [company]
 * @property {Record<string, unknown>} [meta]
 */

/**
 * @typedef {object} Invoice
 * @property {string} id
 * @property {string} reference
 * @property {number} amountMinor
 * @property {string} currency
 * @property {string} status
 * @property {string} [date]
 * @property {string} [customer]
 * @property {Record<string, unknown>} [meta]
 */

/**
 * @typedef {object} Tenant
 * @property {string} id
 * @property {string} slug
 * @property {string} name
 * @property {string} status
 * @property {string} [blueprintId]
 * @property {string} [ownerUserId]
 * @property {string} [odooDatabase]
 * @property {string[]} capabilities
 * @property {Record<string, unknown>} [meta]
 * @property {string} createdAt
 * @property {string} updatedAt
 */

/**
 * @typedef {object} Blueprint
 * @property {string} id
 * @property {string} version
 * @property {string} industry
 * @property {string[]} capabilities
 * @property {string[]} roles
 * @property {string[]} agents
 * @property {string[]} integrations
 */
