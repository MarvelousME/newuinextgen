import fs from 'node:fs';
import crypto from 'node:crypto';
import { TenantStatus } from '@ecosystem/contracts/types.mjs';

export class TenantStore {
  /** @param {string} filePath */
  constructor(filePath) {
    this.filePath = filePath;
    this._tenants = this._load();
  }

  _load() {
    if (!fs.existsSync(this.filePath)) {
      return {};
    }
    try {
      return JSON.parse(fs.readFileSync(this.filePath, 'utf8'));
    } catch {
      return {};
    }
  }

  _save() {
    fs.writeFileSync(this.filePath, JSON.stringify(this._tenants, null, 2));
  }

  /** @returns {import('@ecosystem/contracts/types.mjs').Tenant[]} */
  list() {
    return Object.values(this._tenants);
  }

  /** @param {string} id */
  get(id) {
    return this._tenants[id] || null;
  }

  /** @param {string} slug */
  getBySlug(slug) {
    return this.list().find((t) => t.slug === slug) || null;
  }

  /**
   * @param {{ slug: string; name: string; blueprintId?: string; ownerUserId?: string }} input
   */
  create(input) {
    const slug = String(input.slug || '').trim().toLowerCase().replace(/[^a-z0-9-]/g, '-');
    if (!slug) {
      throw new Error('slug required');
    }
    if (this.getBySlug(slug)) {
      throw new Error('tenant slug exists');
    }
    const id = crypto.randomUUID();
    const now = new Date().toISOString();
    const tenant = {
      id,
      slug,
      name: input.name || slug,
      status: TenantStatus.PENDING,
      blueprintId: input.blueprintId || 'education-tutoring',
      ownerUserId: input.ownerUserId || '',
      odooDatabase: `tenant_${slug.replace(/-/g, '_')}`,
      capabilities: [],
      meta: {},
      createdAt: now,
      updatedAt: now,
    };
    this._tenants[id] = tenant;
    this._save();
    return tenant;
  }

  /** @param {string} id @param {Partial<import('@ecosystem/contracts/types.mjs').Tenant>} patch */
  update(id, patch) {
    const t = this.get(id);
    if (!t) {
      throw new Error('tenant not found');
    }
    const updated = { ...t, ...patch, id: t.id, updatedAt: new Date().toISOString() };
    this._tenants[id] = updated;
    this._save();
    return updated;
  }
}
