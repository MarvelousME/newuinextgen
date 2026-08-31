import { requireOdooSecret, resolveOdooLogin } from './odoo-secrets.mjs';

/**
 * Odoo database-per-tenant provisioner.
 * Uses Odoo web database API when ODOO_URL is set; no-op in memory mode.
 */
export class OdooDatabaseProvisioner {
  /** @param {{ baseUrl: string; masterPassword?: string; adminLogin?: string; adminPassword?: string }} config */
  constructor(config) {
    this.baseUrl = (config.baseUrl || '').replace(/\/$/, '');
    this.masterPassword = config.masterPassword || process.env.ODOO_MASTER_PASSWORD || '';
    this.adminLogin = resolveOdooLogin(config.adminLogin || process.env.ODOO_USERNAME);
    this.adminPassword =
      config.adminPassword || process.env.ODOO_PASSWORD || process.env.ODOO_ADMIN_PASSWORD || '';
    this.useMemory =
      process.env.ODOO_ADAPTER_MEMORY === '1' || !this.baseUrl || !process.env.ODOO_URL;
    this._provisioned = new Set();
  }

  /** @param {string} dbName */
  async ensureDatabase(dbName) {
    if (!dbName || !/^[a-z0-9_]+$/.test(dbName)) {
      throw new Error(`invalid odoo database name: ${dbName}`);
    }
    if (this.useMemory) {
      this._provisioned.add(dbName);
      return { database: dbName, status: 'memory', created: false };
    }

    const existing = await this.listDatabases();
    if (existing.includes(dbName)) {
      this._provisioned.add(dbName);
      return { database: dbName, status: 'exists', created: false };
    }

    await this.createDatabase(dbName);
    this._provisioned.add(dbName);
    return { database: dbName, status: 'created', created: true };
  }

  async listDatabases() {
    if (this.useMemory) {
      return [...this._provisioned];
    }
    const body = {
      jsonrpc: '2.0',
      method: 'call',
      params: {
        service: 'db',
        method: 'list',
        args: [],
      },
      id: Date.now(),
    };
    const res = await fetch(`${this.baseUrl}/jsonrpc`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    const json = await res.json();
    if (json.error) {
      throw new Error(json.error.data?.message || json.error.message || 'odoo db list failed');
    }
    return Array.isArray(json.result) ? json.result : [];
  }

  /** @param {string} dbName */
  async createDatabase(dbName) {
    const master = requireOdooSecret(this.masterPassword, 'ODOO_MASTER_PASSWORD');
    const adminPassword = requireOdooSecret(this.adminPassword, 'ODOO_ADMIN_PASSWORD');
    const form = new URLSearchParams();
    form.set('master_pwd', master);
    form.set('name', dbName);
    form.set('login', this.adminLogin);
    form.set('password', adminPassword);
    form.set('lang', 'en_US');
    form.set('country_code', 'za');
    form.set('phone', '');
    form.set('demo', 'false');

    const res = await fetch(`${this.baseUrl}/web/database/create`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: form.toString(),
      redirect: 'manual',
    });

    // Odoo returns 200 HTML on success or 400 on failure.
    if (res.status >= 400) {
      const text = await res.text();
      throw new Error(`odoo database create failed (${res.status}): ${text.slice(0, 200)}`);
    }
    return true;
  }
}
