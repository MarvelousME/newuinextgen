import { PROVIDER_IDS } from '@ecosystem/contracts';

export class OdooCustomerProvider {
  /** @param {import('./odoo-client.mjs').OdooJsonRpcClient} client */
  constructor(client) {
    this.client = client;
    this.providerId = PROVIDER_IDS.ODOO;
    this.model = 'res.partner';
  }

  /** @param {import('@ecosystem/contracts/types.mjs').TenantContext} ctx */
  _db(ctx) {
    if (!ctx.odooDatabase) {
      throw new Error('tenant odoo database missing');
    }
    return ctx.odooDatabase;
  }

  /** @param {import('@ecosystem/contracts/types.mjs').TenantContext} ctx */
  async list(ctx, query = {}) {
    const page = Number(query.page || 1);
    const pageSize = Number(query.pageSize || 25);
    const db = this._db(ctx);
    const rows = await this.client.execute(db, this.model, 'search_read', [
      [],
      { fields: ['id', 'name', 'email', 'phone', 'company_name'], limit: pageSize },
    ]);
    const items = (rows || []).map((r) => this._map(r));
    return { items, total: items.length, page, pageSize };
  }

  /** @param {import('@ecosystem/contracts/types.mjs').TenantContext} ctx @param {string} id */
  async get(ctx, id) {
    const db = this._db(ctx);
    const rows = await this.client.execute(db, this.model, 'search_read', [
      [['id', '=', Number(id)]],
      { fields: ['id', 'name', 'email', 'phone', 'company_name'], limit: 1 },
    ]);
    if (!rows?.length) {
      return null;
    }
    return this._map(rows[0]);
  }

  /** @param {import('@ecosystem/contracts/types.mjs').TenantContext} ctx */
  async create(ctx, input) {
    const db = this._db(ctx);
    const payload = {
      name: input.name || 'Customer',
      email: input.email || '',
      phone: input.phone || '',
      company_name: input.company || '',
    };
    const id = await this.client.execute(db, this.model, 'create', [payload]);
    return { id: String(id), ...input, name: payload.name };
  }

  /** @param {import('@ecosystem/contracts/types.mjs').TenantContext} ctx */
  async update(ctx, id, patch) {
    const existing = await this.get(ctx, id);
    if (!existing) {
      throw new Error('customer not found');
    }
    return { ...existing, ...patch, id: String(id) };
  }

  async health() {
    return this.client.health();
  }

  _map(row) {
    return {
      id: String(row.id),
      name: row.name || '',
      email: row.email || '',
      phone: row.phone || '',
      company: row.company_name || '',
      meta: { source: 'odoo' },
    };
  }
}
