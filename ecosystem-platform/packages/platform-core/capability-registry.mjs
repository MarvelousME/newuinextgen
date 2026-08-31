/**
 * Platform capability catalogue — aligns with rad-platform capability manifests.
 */
export class CapabilityRegistry {
  constructor() {
    this._capabilities = [
      { id: 'crm', category: 'business', name: 'CRM', provider: 'odoo', dependencies: [] },
      { id: 'sales', category: 'business', name: 'Sales', provider: 'odoo', dependencies: ['crm'] },
      { id: 'invoicing', category: 'business', name: 'Invoicing', provider: 'odoo', dependencies: ['sales'] },
      { id: 'inventory', category: 'business', name: 'Inventory', provider: 'odoo', dependencies: [] },
      { id: 'projects', category: 'business', name: 'Projects', provider: 'odoo', dependencies: [] },
      { id: 'booking', category: 'digital', name: 'Booking', provider: 'companion', dependencies: [] },
      { id: 'lms', category: 'digital', name: 'LMS', provider: 'wordpress', dependencies: [] },
      { id: 'website', category: 'digital', name: 'Website', provider: 'wordpress', dependencies: [] },
      { id: 'payments', category: 'digital', name: 'Payments', provider: 'companion', dependencies: ['sales'] },
      { id: 'video', category: 'digital', name: 'Video Lessons', provider: 'companion', dependencies: ['booking'] },
      { id: 'workflow-automation', category: 'automation', name: 'Workflow Engine', provider: 'platform', dependencies: [] },
      { id: 'ai-matching', category: 'ai', name: 'AI Tutor Matching', provider: 'companion', dependencies: ['crm'] },
      { id: 'ai-agents', category: 'ai', name: 'AI Agents', provider: 'agent-gateway', dependencies: [] },
      { id: 'monitoring', category: 'operations', name: 'Monitoring', provider: 'platform', dependencies: [] },
      { id: 'audit', category: 'operations', name: 'Audit', provider: 'platform', dependencies: [] },
      { id: 'economic-overview', category: 'economic', name: 'Economic Overview', provider: 'platform', dependencies: ['audit'] },
    ];
  }

  list() {
    return [...this._capabilities];
  }

  /** @param {string} id */
  get(id) {
    return this._capabilities.find((c) => c.id === id) || null;
  }

  /** @param {string[]} ids */
  validate(ids) {
    const missing = ids.filter((id) => !this.get(id));
    if (missing.length) {
      throw new Error(`unknown capabilities: ${missing.join(', ')}`);
    }
    return ids.map((id) => this.get(id));
  }
}
