/**
 * E2E: create tenant → provision (Odoo DB) → create customer.
 *
 * Memory mode (default): npm run test:e2e
 * Live Odoo: ODOO_E2E=1 ODOO_URL=http://localhost:8069 npm run test:e2e
 */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { createPlatformCore } from '../index.mjs';
import { createOdooAdapters } from '@ecosystem/odoo-adapter';

const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'ecosystem-e2e-'));
process.env.ECOSYSTEM_DATA_DIR = tmp;

const live = process.env.ODOO_E2E === '1' && process.env.ODOO_URL;
if (live) {
  delete process.env.ODOO_ADAPTER_MEMORY;
} else {
  process.env.ODOO_ADAPTER_MEMORY = '1';
}

const odoo = createOdooAdapters();
const core = createPlatformCore({ odooProvisioner: odoo.provisioner });
core.providers.registerCustomerProvider(odoo.customer);

const SUPER = 'platform-super-admin';

async function main() {
  const suffix = Date.now().toString(36);
  const tenant = core.tenantStore.create({
    slug: `e2e-tenant-${suffix}`,
    name: 'E2E Tenant',
    blueprintId: 'education-tutoring',
  });
  assert.ok(tenant.odooDatabase);

  const provision = await core.provisioning.provision(tenant.id, { userId: SUPER });
  assert.equal(provision.tenant.status, 'active');
  assert.ok(provision.steps.includes('odoo_database'));

  const odooStep = await core.providers.ensureOdooDatabase(tenant);
  assert.ok(odooStep.database);

  core.iam.assignMembership('e2e-owner', tenant.id, 'L2');
  const ctx = {
    tenantId: tenant.id,
    userId: 'e2e-owner',
    odooDatabase: tenant.odooDatabase,
    correlationId: 'e2e-customer',
  };
  const provider = core.providers.customerProvider(ctx);
  const email = `e2e-${suffix}@test.local`;
  const customer = await provider.create(ctx, {
    name: 'E2E Customer',
    email,
  });
  assert.ok(customer.id);

  const list = await provider.list(ctx);
  assert.ok(list.items.some((c) => c.email === email));

  console.log(
    `E2E_PASS mode=${live ? 'live-odoo' : 'memory'} tenant=${tenant.id} customer=${customer.id} odoo_db=${tenant.odooDatabase}`
  );
}

main().catch((err) => {
  console.error('E2E_FAIL', err.message);
  process.exit(1);
});
