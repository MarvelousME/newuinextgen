/**
 * Economic overview assembled from registered platform facts only.
 * Monetary KPIs stay unavailable until a ledger provider is registered.
 */

import {
  ECONOMIC_SCREENS,
  EXECUTABLE_MUTATIONS,
  REGISTERED_ECONOMIC_CAPABILITIES,
  UNREGISTERED_ECONOMIC_CAPABILITIES,
  adapterAvailable,
  isCapabilityRegistered,
  unavailableReason,
} from './economic-catalog.mjs';

/**
 * Convert a major-unit number to integer minor units. Never persist IEEE floats.
 * @param {number|string} amount
 * @param {number} [precision]
 */
export function toMinorUnits(amount, precision = 2) {
  const n = Number(amount);
  if (!Number.isFinite(n)) {
    return 0;
  }
  const factor = 10 ** precision;
  return Math.round(n * factor);
}

/**
 * @param {{
 *   core: import('./index.mjs').createPlatformCore extends Function ? object : object,
 *   iamUserId: string,
 *   tenantId?: string,
 *   odooHealth?: { status?: string },
 *   customerTotal?: number|null,
 *   invoiceTotal?: number|null,
 * }} input
 */
export function buildEconomicOverview(input) {
  const tenants = input.core.tenantStore.list();
  const caps = input.core.capabilities.list();
  const registeredIds = new Set([
    ...REGISTERED_ECONOMIC_CAPABILITIES.map((c) => c.id),
    ...caps.map((c) => c.id),
  ]);

  const screens = ECONOMIC_SCREENS.map((screen) => describeScreen(screen, caps, input.core.iam, input.iamUserId, input.tenantId || ''));

  const unavailableMoney = {
    available: false,
    reason: 'Unavailable — ledger capability not registered.',
    amountMinor: null,
    currency: 'ZAR',
  };

  return {
    product: 'economic-control-center',
    environment: process.env.ECOSYSTEM_ENV || 'local',
    currency: process.env.ECOSYSTEM_BASE_CURRENCY || 'ZAR',
    fiscalPeriod: process.env.ECOSYSTEM_FISCAL_PERIOD || 'FY2026',
    tenantId: input.tenantId || '',
    tenantCount: tenants.length,
    odoo: input.odooHealth || { status: 'UNKNOWN' },
    kpis: [
      { id: 'tenants', label: 'Active Tenants', available: true, value: tenants.length, unit: 'count' },
      {
        id: 'customers',
        label: 'Customers',
        available: input.customerTotal !== null && input.customerTotal !== undefined,
        value: input.customerTotal ?? null,
        unit: 'count',
        reason: input.customerTotal == null ? 'Select a tenant with CRM provisioned.' : '',
      },
      {
        id: 'invoices',
        label: 'Invoices',
        available: input.invoiceTotal !== null && input.invoiceTotal !== undefined,
        value: input.invoiceTotal ?? null,
        unit: 'count',
        reason: input.invoiceTotal == null ? 'Select a tenant with invoicing provisioned.' : '',
      },
      { id: 'balance', label: 'Total Balance', ...unavailableMoney },
      { id: 'cash', label: 'Available Cash', ...unavailableMoney },
      { id: 'reserved', label: 'Reserved Funds', ...unavailableMoney },
      { id: 'revenue-mtd', label: 'Revenue MTD', ...unavailableMoney },
      { id: 'expenses-mtd', label: 'Expenses MTD', ...unavailableMoney },
    ],
    capabilities: {
      registered: [...registeredIds],
      unregistered: UNREGISTERED_ECONOMIC_CAPABILITIES.filter((id) => !registeredIds.has(id)),
    },
    screens,
    auditTail: input.core.audit.tail(12),
    configured: {
      currency: process.env.ECOSYSTEM_BASE_CURRENCY || 'ZAR',
      fiscalPeriod: process.env.ECOSYSTEM_FISCAL_PERIOD || 'FY2026',
      environment: process.env.ECOSYSTEM_ENV || 'local',
    },
  };
}

export function describeScreen(screen, caps, iam, userId, tenantId) {
  const registered = isCapabilityRegistered(screen.capability, caps);
  const allowed = iam.can(userId, screen.readPermission, tenantId);
  const dataBound = registered && allowed && adapterAvailable(screen.adapter);
  return {
    id: screen.id,
    label: screen.label,
    capability: screen.capability,
    registered,
    allowed,
    adapter: screen.adapter,
    available: dataBound,
    reason: unavailableReason(screen, { registered, allowed }),
    layout: screen.layout,
    api: screen.api || null,
    actions: screen.actions || [],
  };
}

export function buildEconomicCatalog(core, userId, tenantId = '') {
  const caps = core.capabilities.list();
  return {
    product: 'economic-control-center',
    screens: ECONOMIC_SCREENS.map((screen) => describeScreen(screen, caps, core.iam, userId, tenantId)),
    grants: Object.fromEntries(
      [
        'economic.read',
        'customers.read',
        'customers.create',
        'invoices.read',
        'invoices.create',
        'payments.read',
        'payments.refund',
        'audit.read',
        'reports.generate',
      ].map((p) => [p, core.iam.can(userId, p, tenantId)])
    ),
  };
}

/**
 * Monetary mutations must fail closed unless capability + adapter + permission exist.
 * @returns {{ ok: boolean, status: number, error?: string }}
 */
export function authorizeEconomicMutation(core, userId, tenantId, actionId) {
  const spec = EXECUTABLE_MUTATIONS[actionId];
  if (!spec) {
    return { ok: false, status: 501, error: `Unavailable — ${actionId} is not an executable economic mutation.` };
  }
  if (!core.iam.can(userId, spec.permission, tenantId)) {
    return { ok: false, status: 403, error: 'permission denied', permission: spec.permission };
  }
  if (!core.capabilities.get(spec.capability) && !REGISTERED_ECONOMIC_CAPABILITIES.some((c) => c.id === spec.capability)) {
    return { ok: false, status: 501, error: `Unavailable — ${spec.capability} capability not registered.` };
  }
  if (!adapterAvailable(spec.adapter)) {
    return { ok: false, status: 501, error: `Unavailable — ${spec.capability} HTTP adapter not registered.` };
  }
  return { ok: true, status: 200 };
}

export function configuredCurrencies() {
  const code = process.env.ECOSYSTEM_BASE_CURRENCY || 'ZAR';
  return {
    items: [
      {
        code,
        symbol: code === 'ZAR' ? 'R' : code,
        precision: 2,
        status: 'ACTIVE',
        baseCurrency: true,
        settlementSupport: false,
        source: 'platform-config',
      },
    ],
    total: 1,
    page: 1,
    pageSize: 25,
    note: 'Configured base currency only. Exchange-rate provider is not registered.',
  };
}
