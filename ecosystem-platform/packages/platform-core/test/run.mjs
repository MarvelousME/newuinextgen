/**
 * Platform core tests.
 */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { createPlatformCore } from '../index.mjs';
import { createOdooAdapters } from '@ecosystem/odoo-adapter';
import { runAuthTests } from './auth.test.mjs';

const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'ecosystem-test-'));
process.env.ECOSYSTEM_DATA_DIR = tmp;
process.env.ECOSYSTEM_API_TOKEN = 'ecosystem-core-test-token-' + Date.now();
process.env.ODOO_ADAPTER_MEMORY = '1';

const odoo = createOdooAdapters();
const core = createPlatformCore({ odooProvisioner: odoo.provisioner });
core.providers.registerCustomerProvider(odoo.customer);

const stats = { pass: 0, fail: 0 };

async function run() {
  const test = (name, fn) => {
    try {
      fn();
      console.log(`OK  ${name}`);
      stats.pass += 1;
    } catch (e) {
      console.error(`FAIL ${name}:`, e.message);
      stats.fail += 1;
    }
  };

  const testAsync = async (name, fn) => {
    try {
      await fn();
      console.log(`OK  ${name}`);
      stats.pass += 1;
    } catch (e) {
      console.error(`FAIL ${name}:`, e.message);
      stats.fail += 1;
    }
  };

  test('blueprint education-tutoring loads', () => {
    const bp = core.blueprints.get('education-tutoring');
    assert.ok(bp);
    assert.ok(bp.capabilities.includes('crm'));
  });

  test('create tenant', () => {
    const suffix = Date.now().toString(36);
    const t = core.tenantStore.create({ slug: `nextgen-tutors-${suffix}`, name: 'NextGen Tutors' });
    assert.ok(t.slug.includes('nextgen-tutors'));
    assert.ok(t.odooDatabase.includes('nextgen'));
  });

  await testAsync('provision tenant', async () => {
    const list = core.tenantStore.list();
    const t = list.find((x) => x.slug.startsWith('nextgen-tutors-'));
    assert.ok(t);
    const result = await core.provisioning.provision(t.id, { userId: 'platform-super-admin' });
    assert.equal(result.tenant.status, 'active');
  });

  await testAsync('tenant isolation on customers', async () => {
    const suffix = Date.now().toString(36);
    const a = core.tenantStore.create({ slug: `tenant-a-${suffix}`, name: 'Tenant A' });
    const b = core.tenantStore.create({ slug: `tenant-b-${suffix}`, name: 'Tenant B' });
    await core.provisioning.provision(a.id, { userId: 'platform-super-admin' });
    await core.provisioning.provision(b.id, { userId: 'platform-super-admin' });

    core.iam.assignMembership('owner-a', a.id, 'L2');
    core.iam.assignMembership('owner-b', b.id, 'L2');

    const ctxA = {
      tenantId: a.id,
      userId: 'owner-a',
      odooDatabase: core.tenantStore.get(a.id).odooDatabase,
      correlationId: 'c1',
    };
    const ctxB = {
      tenantId: b.id,
      userId: 'owner-b',
      odooDatabase: core.tenantStore.get(b.id).odooDatabase,
      correlationId: 'c2',
    };

    const provider = core.providers.customerProvider(ctxA);
    await provider.create(ctxA, { name: 'Customer A', email: 'a@test.local' });
    await provider.create(ctxB, { name: 'Customer B', email: 'b@test.local' });

    const listA = await provider.list(ctxA);
    const listB = await provider.list(ctxB);
    assert.equal(listA.items.length, 1);
    assert.equal(listB.items.length, 1);
    assert.equal(listA.items[0].name, 'Customer A');
    assert.equal(listB.items[0].name, 'Customer B');
  });

  await runAuthTests(stats);

  console.log(`\n${stats.pass} passed, ${stats.fail} failed`);
  process.exit(stats.fail > 0 ? 1 : 0);
}

run();
