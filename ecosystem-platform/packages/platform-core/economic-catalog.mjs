/**
 * Economic Control Center screen matrix.
 * Screens bind to registered capabilities. Unregistered caps stay unavailable.
 */

export const ECONOMIC_PERMISSIONS = {
  READ: 'economic.read',
  TREASURY_TRANSFER: 'treasury.transfer',
  LEDGER_POST: 'ledger.post',
  LEDGER_REVERSE: 'ledger.reverse',
  PAYMENTS_REFUND: 'payments.refund',
  SETTLEMENTS_EXECUTE: 'settlements.execute',
  RECONCILE_RESOLVE: 'reconciliation.resolve',
  BILLING_MANAGE: 'billing.manage',
  INVOICES_CREATE: 'invoices.create',
  INVOICES_READ: 'invoices.read',
  CUSTOMERS_READ: 'customers.read',
  CUSTOMERS_CREATE: 'customers.create',
  TAX_MANAGE: 'tax.manage',
  RISK_READ: 'risk.read',
  FRAUD_REVIEW: 'fraud.review',
  REPORTS_GENERATE: 'reports.generate',
  AUDIT_READ: 'audit.read',
  PAYMENTS_READ: 'payments.read',
};

/** IAM L0–L7 → permissions. Super-admin (L0) is wildcard. */
export const PERMISSIONS_BY_LEVEL = {
  L0: ['*'],
  L1: [
    ECONOMIC_PERMISSIONS.READ,
    ECONOMIC_PERMISSIONS.AUDIT_READ,
    ECONOMIC_PERMISSIONS.REPORTS_GENERATE,
    ECONOMIC_PERMISSIONS.CUSTOMERS_READ,
    ECONOMIC_PERMISSIONS.INVOICES_READ,
    ECONOMIC_PERMISSIONS.PAYMENTS_READ,
    ECONOMIC_PERMISSIONS.RISK_READ,
  ],
  L2: [
    ECONOMIC_PERMISSIONS.READ,
    ECONOMIC_PERMISSIONS.AUDIT_READ,
    ECONOMIC_PERMISSIONS.CUSTOMERS_READ,
    ECONOMIC_PERMISSIONS.CUSTOMERS_CREATE,
    ECONOMIC_PERMISSIONS.INVOICES_READ,
    ECONOMIC_PERMISSIONS.INVOICES_CREATE,
    ECONOMIC_PERMISSIONS.PAYMENTS_READ,
    ECONOMIC_PERMISSIONS.BILLING_MANAGE,
    ECONOMIC_PERMISSIONS.REPORTS_GENERATE,
  ],
  L3: [
    ECONOMIC_PERMISSIONS.READ,
    ECONOMIC_PERMISSIONS.CUSTOMERS_READ,
    ECONOMIC_PERMISSIONS.INVOICES_READ,
    ECONOMIC_PERMISSIONS.PAYMENTS_READ,
    ECONOMIC_PERMISSIONS.AUDIT_READ,
  ],
  L4: [ECONOMIC_PERMISSIONS.READ, ECONOMIC_PERMISSIONS.CUSTOMERS_READ, ECONOMIC_PERMISSIONS.INVOICES_READ],
  L5: [ECONOMIC_PERMISSIONS.READ],
  L6: [ECONOMIC_PERMISSIONS.READ],
  L7: [ECONOMIC_PERMISSIONS.READ, ECONOMIC_PERMISSIONS.AUDIT_READ],
};

/**
 * Adapter kinds:
 * - platform / odoo: HTTP surface exists in this control plane
 * - companion-http: capability registered, no platform HTTP adapter yet
 * - none: capability not registered
 *
 * @typedef {object} EconomicScreen
 * @property {string} id
 * @property {string} label
 * @property {string} capability
 * @property {string} readPermission
 * @property {string[]} [actions]
 * @property {string} [api]
 * @property {'grid'|'overview'|'form'|'report'|'settings'|'timeline'} layout
 * @property {'platform'|'odoo'|'companion-http'|'none'} adapter
 */

/** @type {EconomicScreen[]} */
export const ECONOMIC_SCREENS = [
  { id: 'overview', label: 'Economic Home', capability: 'economic-overview', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'overview', api: '/api/v1/economic/overview', actions: ['observe', 'diagnose', 'audit', 'report'], adapter: 'platform' },
  { id: 'treasury', label: 'Treasury', capability: 'treasury', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'overview', actions: ['transact', 'settle', 'forecast'], adapter: 'none' },
  { id: 'ledger', label: 'General Ledger', capability: 'ledger', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', actions: ['observe', 'audit'], adapter: 'none' },
  { id: 'accounts', label: 'Accounts', capability: 'ledger', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'none' },
  { id: 'wallets', label: 'Wallets', capability: 'wallets', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', actions: ['transact'], adapter: 'none' },
  { id: 'payments', label: 'Payments', capability: 'payments', readPermission: ECONOMIC_PERMISSIONS.PAYMENTS_READ, layout: 'grid', actions: ['observe', 'diagnose'], adapter: 'companion-http' },
  { id: 'billing', label: 'Billing', capability: 'invoicing', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', actions: ['transact'], adapter: 'odoo' },
  { id: 'invoices', label: 'Invoices', capability: 'invoicing', readPermission: ECONOMIC_PERMISSIONS.INVOICES_READ, layout: 'grid', api: '/api/v1/economic/invoices', actions: ['transact'], adapter: 'odoo' },
  { id: 'revenue', label: 'Revenue', capability: 'invoicing', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'overview', adapter: 'odoo' },
  { id: 'expenses', label: 'Expenses', capability: 'expenses', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'none' },
  { id: 'settlements', label: 'Settlements', capability: 'settlements', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'timeline', adapter: 'none' },
  { id: 'reconciliation', label: 'Reconciliation', capability: 'reconciliation', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', actions: ['reconcile'], adapter: 'none' },
  { id: 'tax', label: 'Tax', capability: 'tax', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'overview', adapter: 'none' },
  { id: 'contracts', label: 'Contracts', capability: 'contracts', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'none' },
  { id: 'marketplace', label: 'Marketplace', capability: 'marketplace', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'none' },
  { id: 'earnings', label: 'Earnings', capability: 'payouts', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'overview', adapter: 'none' },
  { id: 'rewards', label: 'Rewards', capability: 'rewards', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'none' },
  { id: 'ubi', label: 'UBI / Distributions', capability: 'distributions', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'none' },
  { id: 'assets', label: 'Assets', capability: 'ledger', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'none' },
  { id: 'liabilities', label: 'Liabilities', capability: 'ledger', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'none' },
  { id: 'cash-flow', label: 'Cash Flow', capability: 'treasury', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'overview', actions: ['forecast'], adapter: 'none' },
  { id: 'budgets', label: 'Budgets', capability: 'budgets', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'none' },
  { id: 'forecasting', label: 'Forecasting', capability: 'forecasting', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'overview', actions: ['forecast'], adapter: 'none' },
  { id: 'pricing', label: 'Pricing', capability: 'invoicing', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'odoo' },
  { id: 'commissions', label: 'Commissions', capability: 'commissions', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'none' },
  { id: 'fees', label: 'Fees', capability: 'payments', readPermission: ECONOMIC_PERMISSIONS.PAYMENTS_READ, layout: 'grid', adapter: 'companion-http' },
  { id: 'gateways', label: 'Gateways', capability: 'payments', readPermission: ECONOMIC_PERMISSIONS.PAYMENTS_READ, layout: 'grid', adapter: 'companion-http' },
  { id: 'fraud', label: 'Fraud', capability: 'fraud', readPermission: ECONOMIC_PERMISSIONS.FRAUD_REVIEW, layout: 'grid', adapter: 'none' },
  { id: 'risk', label: 'Risk', capability: 'risk', readPermission: ECONOMIC_PERMISSIONS.RISK_READ, layout: 'overview', adapter: 'none' },
  { id: 'compliance', label: 'Compliance', capability: 'audit', readPermission: ECONOMIC_PERMISSIONS.AUDIT_READ, layout: 'grid', adapter: 'platform' },
  { id: 'disputes', label: 'Disputes', capability: 'payments', readPermission: ECONOMIC_PERMISSIONS.PAYMENTS_READ, layout: 'timeline', adapter: 'companion-http' },
  { id: 'refunds', label: 'Refunds', capability: 'payments', readPermission: ECONOMIC_PERMISSIONS.PAYMENTS_READ, layout: 'grid', adapter: 'companion-http' },
  { id: 'payouts', label: 'Payouts', capability: 'payouts', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'none' },
  { id: 'beneficiaries', label: 'Beneficiaries', capability: 'payouts', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'none' },
  { id: 'customers', label: 'Customers', capability: 'crm', readPermission: ECONOMIC_PERMISSIONS.CUSTOMERS_READ, layout: 'grid', api: '/api/v1/economic/customers', actions: ['transact'], adapter: 'odoo' },
  { id: 'vendors', label: 'Vendors', capability: 'crm', readPermission: ECONOMIC_PERMISSIONS.CUSTOMERS_READ, layout: 'grid', adapter: 'odoo' },
  { id: 'currencies', label: 'Currencies', capability: 'economic-overview', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'platform' },
  { id: 'exchange-rates', label: 'Exchange Rates', capability: 'fx', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'grid', adapter: 'none' },
  { id: 'automation', label: 'Economic Automation', capability: 'workflow-automation', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'timeline', actions: ['automate'], adapter: 'platform' },
  { id: 'reports', label: 'Reports', capability: 'audit', readPermission: ECONOMIC_PERMISSIONS.REPORTS_GENERATE, layout: 'report', actions: ['report'], adapter: 'platform' },
  { id: 'audit', label: 'Audit', capability: 'audit', readPermission: ECONOMIC_PERMISSIONS.AUDIT_READ, layout: 'grid', api: '/api/v1/economic/audit', adapter: 'platform' },
  { id: 'settings', label: 'Settings', capability: 'economic-overview', readPermission: ECONOMIC_PERMISSIONS.READ, layout: 'settings', adapter: 'platform' },
];

export const EXECUTABLE_MUTATIONS = {
  'customers.create': { capability: 'crm', permission: ECONOMIC_PERMISSIONS.CUSTOMERS_CREATE, adapter: 'odoo' },
  'invoices.create': { capability: 'invoicing', permission: ECONOMIC_PERMISSIONS.INVOICES_CREATE, adapter: 'odoo' },
};

export function adapterAvailable(adapter) {
  return adapter === 'platform' || adapter === 'odoo';
}

export function unavailableReason(screen, { registered, allowed }) {
  if (!allowed) {
    return 'Unavailable — permission denied.';
  }
  if (!registered) {
    return `Unavailable — ${screen.capability} capability not registered.`;
  }
  if (screen.adapter === 'companion-http') {
    return `Unavailable — ${screen.capability} HTTP adapter not registered.`;
  }
  if (screen.adapter === 'none') {
    return `Unavailable — ${screen.capability} capability not registered.`;
  }
  return '';
}

export const REGISTERED_ECONOMIC_CAPABILITIES = [
  { id: 'economic-overview', name: 'Economic Overview', provider: 'platform', registered: true },
  { id: 'crm', name: 'CRM', provider: 'odoo', registered: true },
  { id: 'invoicing', name: 'Invoicing', provider: 'odoo', registered: true },
  { id: 'payments', name: 'Payments', provider: 'companion', registered: true },
  { id: 'audit', name: 'Audit', provider: 'platform', registered: true },
  { id: 'workflow-automation', name: 'Workflow Engine', provider: 'platform', registered: true },
];

export const UNREGISTERED_ECONOMIC_CAPABILITIES = [
  'treasury',
  'ledger',
  'wallets',
  'expenses',
  'settlements',
  'reconciliation',
  'tax',
  'contracts',
  'marketplace',
  'payouts',
  'rewards',
  'distributions',
  'budgets',
  'forecasting',
  'commissions',
  'fraud',
  'risk',
  'fx',
];

export function isCapabilityRegistered(id, registryList = []) {
  if (REGISTERED_ECONOMIC_CAPABILITIES.some((c) => c.id === id && c.registered)) {
    return true;
  }
  return Boolean(registryList.find((c) => c.id === id));
}

export function getScreen(id) {
  return ECONOMIC_SCREENS.find((s) => s.id === id) || null;
}
