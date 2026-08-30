/**
 * Odoo JSON-RPC client — tenant DB selected per request context.
 */
export class OdooJsonRpcClient {
  /** @param {{ baseUrl: string; username: string; password: string }} config */
  constructor(config) {
    this.baseUrl = config.baseUrl.replace(/\/$/, '');
    this.username = config.username;
    this.password = config.password;
    this._sessions = new Map();
    this._memory = new Map();
    this.useMemory = process.env.ODOO_ADAPTER_MEMORY === '1' || !process.env.ODOO_URL;
  }

  /** @param {string} db */
  async authenticate(db) {
    if (this.useMemory) {
      return { uid: 1, db };
    }
    const cached = this._sessions.get(db);
    if (cached) {
      return cached;
    }
    const body = {
      jsonrpc: '2.0',
      method: 'call',
      params: {
        service: 'common',
        method: 'authenticate',
        args: [db, this.username, this.password, {}],
      },
      id: Date.now(),
    };
    const res = await fetch(`${this.baseUrl}/jsonrpc`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    const json = await res.json();
    if (!json.result) {
      throw new Error('odoo auth failed');
    }
    const session = { uid: json.result, db };
    this._sessions.set(db, session);
    return session;
  }

  /**
   * @param {string} db
   * @param {string} model
   * @param {string} method
   * @param {unknown[]} args
   */
  async execute(db, model, method, args = []) {
    if (this.useMemory) {
      const key = `${db}:${model}`;
      if (!this._memory.has(key)) {
        this._memory.set(key, []);
      }
      const store = this._memory.get(key);
      if (method === 'search_read') {
        return store;
      }
      if (method === 'create') {
        const id = store.length + 1;
        const rec = { id, ...(args[0] || {}) };
        store.push(rec);
        return id;
      }
      return [];
    }
    const session = await this.authenticate(db);
    const body = {
      jsonrpc: '2.0',
      method: 'call',
      params: {
        service: 'object',
        method: 'execute_kw',
        args: [db, session.uid, this.password, model, method, args],
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
      throw new Error(json.error.data?.message || json.error.message || 'odoo error');
    }
    return json.result;
  }

  async health() {
    if (this.useMemory) {
      return { status: 'HEALTHY', mode: 'memory' };
    }
    try {
      await fetch(this.baseUrl);
      return { status: 'HEALTHY', mode: 'jsonrpc' };
    } catch {
      return { status: 'UNHEALTHY', mode: 'jsonrpc' };
    }
  }
}
