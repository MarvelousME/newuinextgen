import { TenantStatus } from '@ecosystem/contracts/types.mjs';

export class ProvisioningOrchestrator {
  /** @param {object} deps */
  constructor(deps) {
    this.tenantStore = deps.tenantStore;
    this.blueprints = deps.blueprints;
    this.capabilities = deps.capabilities;
    this.events = deps.events;
    this.audit = deps.audit;
    this.providers = deps.providers;
  }

  /** @param {string} tenantId @param {object} actor */
  async provision(tenantId, actor = {}) {
    const tenant = this.tenantStore.get(tenantId);
    if (!tenant) {
      throw new Error('tenant not found');
    }
    const blueprint = this.blueprints.get(tenant.blueprintId);
    if (!blueprint) {
      throw new Error('blueprint not found');
    }

    this.tenantStore.update(tenantId, { status: TenantStatus.PROVISIONING });
    const steps = [];

    try {
      steps.push('identity_boundary');
      steps.push('tenant_database');
      steps.push('odoo_database');
      await this.providers.ensureOdooDatabase(tenant);

      const capIds = blueprint.capabilities || [];
      this.capabilities.validate(capIds);
      steps.push('capabilities');
      this.tenantStore.update(tenantId, { capabilities: capIds });

      steps.push('automation');
      steps.push('agents');
      steps.push('wordpress_optional');
      steps.push('observability');
      steps.push('backup_hooks');

      const active = this.tenantStore.update(tenantId, {
        status: TenantStatus.ACTIVE,
      });

      await this.events.emit({
        eventType: 'TenantProvisioned',
        tenantId,
        correlationId: actor.correlationId || '',
        payload: { steps, blueprintId: blueprint.id },
      });

      this.audit.append({
        actorId: actor.userId || 'system',
        tenantId,
        action: 'tenant.provision',
        resource: tenantId,
        correlationId: actor.correlationId || '',
        result: 'ok',
        meta: { steps },
      });

      return { tenant: active, steps };
    } catch (err) {
      this.tenantStore.update(tenantId, { status: TenantStatus.PENDING });
      this.audit.append({
        actorId: actor.userId || 'system',
        tenantId,
        action: 'tenant.provision',
        resource: tenantId,
        result: 'error',
        meta: { message: String(err?.message || err), steps },
      });
      throw err;
    }
  }
}
