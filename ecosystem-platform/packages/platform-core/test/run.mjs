/**
 * Platform core tests.
 */
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { createPlatformCore, ECONOMIC_SCREENS, toMinorUnits, buildEconomicOverview, authorizeEconomicMutation } from '../index.mjs';
import { createOdooAdapters, OdooDatabaseProvisioner, requireOdooSecret } from '@ecosystem/odoo-adapter';
import { runAuthTests } from './auth.test.mjs';

const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'ecosystem-test-'));
process.env.ECOSYSTEM_DATA_DIR = tmp;
process.env.ECOSYSTEM_API_TOKEN = 'ecosystem-core-test-token-' + Date.now();
process.env.ODOO_ADAPTER_MEMORY = '1';

const odoo = createOdooAdapters();
const core = createPlatformCore({ odooProvisioner: odoo.provisioner });
core.providers.registerCustomerProvider(odoo.customer);
core.providers.registerInvoiceProvider(odoo.invoices);

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

  test('weak odoo secrets are rejected', () => {
    assert.throws(() => requireOdooSecret('admin', 'ODOO_MASTER_PASSWORD'), /too weak/);
    assert.throws(() => requireOdooSecret('', 'ODOO_MASTER_PASSWORD'), /missing or too weak/);
    assert.equal(
      requireOdooSecret('local-odoo-secret-test-only', 'ODOO_MASTER_PASSWORD'),
      'local-odoo-secret-test-only'
    );
  });

  await testAsync('live createDatabase fails closed without a strong master password', async () => {
    const p = new OdooDatabaseProvisioner({
      baseUrl: 'http://127.0.0.1:8069',
      masterPassword: 'admin',
      adminPassword: 'admin',
    });
    await assert.rejects(
      () => p.createDatabase('tenant_x'),
      (err) => err.code === 'ODOO_SECRET_REQUIRED'
    );
  });

  await runAuthTests(stats);

  test('economic catalog has 42 screens', () => {
    assert.equal(ECONOMIC_SCREENS.length, 42);
    assert.ok(ECONOMIC_SCREENS.every((s) => s.id && s.capability && s.readPermission));
  });

  test('iam can() is permission-based not role-string', () => {
    const tmpIam = fs.mkdtempSync(path.join(os.tmpdir(), 'ecosystem-iam-'));
    const coreIam = createPlatformCore({ odooProvisioner: odoo.provisioner, dataDir: tmpIam });
    const tenant = coreIam.tenantStore.create({ slug: 'perm-test', name: 'Perm Test' });
    coreIam.iam.assignMembership('viewer', tenant.id, 'L5');
    coreIam.iam.assignMembership('owner', tenant.id, 'L2');
    assert.equal(coreIam.iam.can('viewer', 'economic.read', tenant.id), true);
    assert.equal(coreIam.iam.can('viewer', 'invoices.create', tenant.id), false);
    assert.equal(coreIam.iam.can('viewer', 'payments.refund', tenant.id), false);
    assert.equal(coreIam.iam.can('owner', 'invoices.create', tenant.id), true);
    assert.equal(coreIam.iam.can('owner', 'payments.refund', tenant.id), false);
    assert.equal(coreIam.iam.can('platform-super-admin', 'payments.refund', tenant.id), true);
  });

  test('toMinorUnits is integer-safe', () => {
    assert.equal(toMinorUnits('12.34'), 1234);
    assert.equal(toMinorUnits(4.1), 410);
    assert.equal(toMinorUnits('not-a-number'), 0);
  });

  test('treasury stays unavailable until registered', () => {
    const overview = buildEconomicOverview({
      core,
      iamUserId: 'platform-super-admin',
      customerTotal: null,
      invoiceTotal: null,
    });
    const treasury = overview.screens.find((s) => s.id === 'treasury');
    assert.equal(treasury.available, false);
    assert.match(treasury.reason, /treasury capability not registered/);
    const balance = overview.kpis.find((k) => k.id === 'balance');
    assert.equal(balance.available, false);
    assert.equal(balance.amountMinor, null);
  });

  test('unauthorized mutation fails closed', () => {
    const denied = authorizeEconomicMutation(core, 'nobody', '', 'invoices.create');
    assert.equal(denied.ok, false);
    assert.equal(denied.status, 403);
    const missing = authorizeEconomicMutation(core, 'platform-super-admin', '', 'treasury.transfer');
    assert.equal(missing.ok, false);
    assert.equal(missing.status, 501);
  });

  await testAsync('invoice create lists in memory adapter', async () => {
    const list = core.tenantStore.list();
    const t = list.find((x) => x.slug.startsWith('nextgen-tutors-'));
    assert.ok(t);
    const ctxInv = {
      tenantId: t.id,
      userId: 'platform-super-admin',
      odooDatabase: core.tenantStore.get(t.id).odooDatabase,
      correlationId: 'inv-1',
    };
    const invoices = core.providers.invoiceProvider(ctxInv);
    await invoices.create(ctxInv, { reference: 'INV-TEST', amountMajor: 100.5, currency: 'ZAR' });
    const listed = await invoices.list(ctxInv);
    assert.equal(listed.items.length, 1);
    assert.equal(listed.items[0].amountMinor, 10050);
    assert.equal(listed.items[0].status, 'DRAFT');
  });

  console.log(`\n${stats.pass} passed, ${stats.fail} failed`);
  process.exit(stats.fail > 0 ? 1 : 0);
}

run();
