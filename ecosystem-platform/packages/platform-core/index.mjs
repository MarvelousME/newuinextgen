import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { IAM_LEVELS, PROVIDER_IDS } from '@ecosystem/contracts';
import { TenantStatus } from '@ecosystem/contracts/types.mjs';
import { BlueprintRegistry } from './blueprint-registry.mjs';
import { CapabilityRegistry } from './capability-registry.mjs';
import { TenantStore } from './tenant-store.mjs';
import { AuditLog } from './audit-log.mjs';
import { EventBus } from './event-bus.mjs';
import { ProvisioningOrchestrator } from './provisioning.mjs';
import { ProviderRouter } from './provider-router.mjs';
import { IamService } from './iam.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const DATA_DIR = process.env.ECOSYSTEM_DATA_DIR || path.join(__dirname, '..', '..', '.data');

export function createPlatformCore(options = {}) {
  const dataDir = options.dataDir || DATA_DIR;
  fs.mkdirSync(dataDir, { recursive: true });

  const tenantStore = new TenantStore(path.join(dataDir, 'tenants.json'));
  const audit = new AuditLog(path.join(dataDir, 'audit.jsonl'));
  const events = new EventBus({ audit });
  const blueprints = new BlueprintRegistry(
    options.blueprintDir || path.join(__dirname, '..', '..', 'tenant-blueprints')
  );
  const capabilities = new CapabilityRegistry();
  const providers = new ProviderRouter({
    odooProvisioner: options.odooProvisioner || null,
  });
  const iam = new IamService({ tenantStore, audit });

  const provisioning = new ProvisioningOrchestrator({
    tenantStore,
    blueprints,
    capabilities,
    events,
    audit,
    providers,
  });

  return {
    tenantStore,
    audit,
    events,
    blueprints,
    capabilities,
    providers,
    iam,
    provisioning,
    IAM_LEVELS,
    PROVIDER_IDS,
    TenantStatus,
  };
}

export { BlueprintRegistry } from './blueprint-registry.mjs';
export { CapabilityRegistry } from './capability-registry.mjs';
export { TenantStore } from './tenant-store.mjs';
export { AuditLog } from './audit-log.mjs';
export { EventBus } from './event-bus.mjs';
export { ProvisioningOrchestrator } from './provisioning.mjs';
export { ProviderRouter } from './provider-router.mjs';
export { IamService } from './iam.mjs';
export {
  authenticateRequest,
  getApiToken,
  getPlatformPrincipalId,
  isTokenConfigured,
} from './auth.mjs';
export { resolveTenantContext } from './tenant-context.mjs';
