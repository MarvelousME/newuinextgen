import { PROVIDER_IDS } from '@ecosystem/contracts';

export class ProviderRouter {
  /** @param {{ odooProvisioner?: { ensureDatabase: (db: string) => Promise<object> } }} deps */
  constructor(deps = {}) {
    /** @type {Map<string, import('@ecosystem/contracts').ICustomerProvider>} */
    this.customerProviders = new Map();
    this.odooDatabases = new Map();
    this.odooProvisioner = deps.odooProvisioner || null;
  }

  /** @param {import('@ecosystem/contracts').ICustomerProvider} provider */
  registerCustomerProvider(provider) {
    this.customerProviders.set(provider.providerId, provider);
  }

  /**
   * @param {import('@ecosystem/contracts/types.mjs').TenantContext} ctx
   */
  customerProvider(ctx) {
    const p = this.customerProviders.get(PROVIDER_IDS.ODOO);
    if (!p) {
      throw new Error('no customer provider registered');
    }
    return p;
  }

  /**
   * Register Odoo DB for tenant (DB-per-tenant).
   * @param {import('@ecosystem/contracts/types.mjs').Tenant} tenant
   */
  async ensureOdooDatabase(tenant) {
    const dbName = tenant.odooDatabase;
    if (!dbName) {
      throw new Error('tenant missing odooDatabase');
    }
    let provision = { database: dbName, status: 'registered', created: false };
    if (this.odooProvisioner) {
      provision = await this.odooProvisioner.ensureDatabase(dbName);
    }
    this.odooDatabases.set(tenant.id, dbName);
    return provision;
  }

  /** @param {string} tenantId */
  getOdooDatabase(tenantId) {
    return this.odooDatabases.get(tenantId) || null;
  }
}
