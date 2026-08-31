import { PROVIDER_IDS } from '@ecosystem/contracts';

/**
 * Convert a major-unit number to integer minor units. Never persist IEEE floats.
 * @param {number|string} amount
 * @param {number} [precision]
 */
function toMinorUnits(amount, precision = 2) {
  const n = Number(amount);
  if (!Number.isFinite(n)) {
    return 0;
  }
  return Math.round(n * 10 ** precision);
}

/**
 * Odoo customer invoices (account.move, out_invoice).
 */
export class OdooInvoiceProvider {
  /** @param {import('./odoo-client.mjs').OdooJsonRpcClient} client */
  constructor(client) {
    this.client = client;
    this.providerId = PROVIDER_IDS.ODOO;
    this.model = 'account.move';
  }

  /** @param {import('@ecosystem/contracts/types.mjs').TenantContext} ctx */
  _db(ctx) {
    if (!ctx.odooDatabase) {
      throw new Error('tenant odoo database missing');
    }
    return ctx.odooDatabase;
  }

  /**
   * @param {import('@ecosystem/contracts/types.mjs').TenantContext} ctx
   */
  async list(ctx, query = {}) {
    const page = Number(query.page || 1);
    const pageSize = Number(query.pageSize || 25);
    const db = this._db(ctx);
    const rows = await this.client.execute(db, this.model, 'search_read', [
      [['move_type', '=', 'out_invoice']],
      {
        fields: ['id', 'name', 'amount_total', 'currency_id', 'state', 'invoice_date', 'partner_id', 'move_type'],
        limit: pageSize,
      },
    ]);
    const items = (rows || []).map((r) => this._map(r));
    return { items, total: items.length, page, pageSize };
  }

  /**
   * @param {import('@ecosystem/contracts/types.mjs').TenantContext} ctx
   */
  async create(ctx, input) {
    const db = this._db(ctx);
    const payload = {
      move_type: 'out_invoice',
      name: input.reference || input.name || '/',
      amount_total: Number(input.amountMajor || 0),
      state: 'draft',
      invoice_date: input.date || new Date().toISOString().slice(0, 10),
      partner_id: input.customerId ? Number(input.customerId) : false,
    };
    const id = await this.client.execute(db, this.model, 'create', [payload]);
    return this._map({ id, ...payload, currency_id: [0, input.currency || 'ZAR'] });
  }

  async health() {
    return this.client.health();
  }

  _map(row) {
    const currency = Array.isArray(row.currency_id) ? row.currency_id[1] : row.currency_id || 'ZAR';
    const partner = Array.isArray(row.partner_id) ? row.partner_id[1] : '';
    return {
      id: String(row.id),
      reference: row.name || String(row.id),
      amountMinor: toMinorUnits(row.amount_total || 0),
      currency: typeof currency === 'string' ? currency : 'ZAR',
      status: String(row.state || 'draft').toUpperCase(),
      date: row.invoice_date || '',
      customer: partner || '',
      meta: { source: 'odoo', moveType: row.move_type || 'out_invoice' },
    };
  }
}
