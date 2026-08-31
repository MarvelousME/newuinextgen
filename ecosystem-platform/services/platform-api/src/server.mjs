import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createPlatformCore, resolveTenantContext, authenticateRequest, buildEconomicOverview, buildEconomicCatalog, authorizeEconomicMutation, configuredCurrencies, ECONOMIC_PERMISSIONS } from '@ecosystem/platform-core';
import { createOdooAdapters } from '@ecosystem/odoo-adapter';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '../../..');
const CONTROL_CENTER = path.join(ROOT, 'apps/control-center');
const UI_TOKENS = path.join(ROOT, 'packages/nextgen-ui/tokens.css');

const PORT = Number(process.env.ECOSYSTEM_API_PORT || 8790);
const odoo = createOdooAdapters();
const core = createPlatformCore({ odooProvisioner: odoo.provisioner });
core.providers.registerCustomerProvider(odoo.customer);
core.providers.registerInvoiceProvider(odoo.invoices);

/** @param {http.IncomingMessage} req */
function readBody(req) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    req.on('data', (c) => chunks.push(c));
    req.on('end', () => {
      const raw = Buffer.concat(chunks).toString('utf8');
      if (!raw) {
        resolve({});
        return;
      }
      try {
        resolve(JSON.parse(raw));
      } catch (e) {
        reject(e);
      }
    });
    req.on('error', reject);
  });
}

function json(res, status, payload, headers = {}) {
  const body = JSON.stringify(payload);
  res.writeHead(status, {
    'Content-Type': 'application/json',
    'X-Correlation-Id': headers.correlationId || '',
  });
  res.end(body);
}

function serveStatic(res, filePath, contentType) {
  if (!fs.existsSync(filePath)) {
    json(res, 404, { error: 'not found' });
    return;
  }
  res.writeHead(200, { 'Content-Type': contentType });
  res.end(fs.readFileSync(filePath));
}

/**
 * @param {http.IncomingMessage} req
 * @param {http.ServerResponse} res
 * @returns {{ ok: true, userId: string } | null}
 */
function requireAuth(req, res) {
  const auth = authenticateRequest(req);
  if (!auth.ok) {
    json(res, auth.status, { error: auth.error });
    return null;
  }
  return auth;
}

/**
 * @param {{ ok: true, userId: string }} auth
 * @param {http.ServerResponse} res
 * @returns {boolean}
 */
function requirePlatformSuperAdmin(auth, res) {
  if (!core.iam.isPlatformSuperAdmin(auth.userId)) {
    json(res, 403, { error: 'platform super admin required' });
    return false;
  }
  return true;
}

/**
 * @param {http.IncomingMessage} req
 * @param {{ ok: true, userId: string }} auth
 * @param {string} tenantId
 */
function resolveTenantForEconomic(req, auth, tenantId) {
  req.headers['x-tenant-id'] = tenantId;
  const ctx = resolveTenantContext(req, core.iam, core.tenantStore, { userId: auth.userId });
  const tenant = core.tenantStore.get(tenantId);
  ctx.odooDatabase = tenant?.odooDatabase || '';
  return ctx;
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`);
  const pathname = url.pathname;
  const method = req.method || 'GET';

  if (method === 'GET' && pathname === '/') {
    return serveStatic(res, path.join(CONTROL_CENTER, 'index.html'), 'text/html; charset=utf-8');
  }
  if (method === 'GET' && pathname === '/app.js') {
    return serveStatic(res, path.join(CONTROL_CENTER, 'app.js'), 'application/javascript; charset=utf-8');
  }
  if (method === 'GET' && pathname === '/architecture.js') {
    return serveStatic(res, path.join(CONTROL_CENTER, 'architecture.js'), 'application/javascript; charset=utf-8');
  }
  if (method === 'GET' && pathname === '/control-center.css') {
    return serveStatic(res, path.join(CONTROL_CENTER, 'control-center.css'), 'text/css; charset=utf-8');
  }
  if (method === 'GET' && pathname === '/economic.css') {
    return serveStatic(res, path.join(CONTROL_CENTER, 'economic.css'), 'text/css; charset=utf-8');
  }
  if (method === 'GET' && pathname === '/economic.js') {
    return serveStatic(res, path.join(CONTROL_CENTER, 'economic.js'), 'application/javascript; charset=utf-8');
  }
  if (method === 'GET' && pathname === '/economic-catalog.js') {
    return serveStatic(res, path.join(CONTROL_CENTER, 'economic-catalog.js'), 'application/javascript; charset=utf-8');
  }
  if (method === 'GET' && pathname.startsWith('/components/')) {
    const rel = pathname.replace(/^\//, '');
    const filePath = path.join(CONTROL_CENTER, rel);
    if (filePath.startsWith(CONTROL_CENTER) && fs.existsSync(filePath)) {
      return serveStatic(res, filePath, 'application/javascript; charset=utf-8');
    }
    return json(res, 404, { error: 'not found' });
  }
  if (method === 'GET' && pathname === '/ui/tokens.css') {
    return serveStatic(res, UI_TOKENS, 'text/css; charset=utf-8');
  }

  try {
    if (pathname === '/health' && method === 'GET') {
      const odooHealth = await odoo.client.health();
      return json(res, 200, {
        status: 'HEALTHY',
        service: 'ecosystem-platform-api',
        odoo: odooHealth,
      });
    }

    if (pathname.startsWith('/api/v1/')) {
      const auth = requireAuth(req, res);
      if (!auth) {
        return;
      }

      if (pathname === '/api/v1/platform/overview' && method === 'GET') {
        if (!requirePlatformSuperAdmin(auth, res)) {
          return;
        }
        return json(res, 200, {
          tenants: core.tenantStore.list(),
          capabilities: core.capabilities.list(),
          blueprints: core.blueprints.list(),
          auditTail: core.audit.tail(10),
        });
      }

      if (pathname === '/api/v1/platform/tenants' && method === 'GET') {
        if (!requirePlatformSuperAdmin(auth, res)) {
          return;
        }
        return json(res, 200, { items: core.tenantStore.list() });
      }

      if (pathname === '/api/v1/platform/tenants' && method === 'POST') {
        if (!requirePlatformSuperAdmin(auth, res)) {
          return;
        }
        const body = await readBody(req);
        const tenant = core.tenantStore.create({
          slug: body.slug,
          name: body.name,
          blueprintId: body.blueprintId,
          ownerUserId: body.ownerUserId,
        });
        if (body.ownerUserId) {
          core.iam.assignMembership(body.ownerUserId, tenant.id, 'L2');
        }
        await core.events.emit({
          eventType: 'TenantCreated',
          tenantId: tenant.id,
          correlationId: String(req.headers['x-correlation-id'] || ''),
          payload: { slug: tenant.slug },
        });
        return json(res, 201, { tenant });
      }

      const provisionMatch = pathname.match(/^\/api\/v1\/platform\/tenants\/([^/]+)\/provision$/);
      if (provisionMatch && method === 'POST') {
        if (!requirePlatformSuperAdmin(auth, res)) {
          return;
        }
        const result = await core.provisioning.provision(provisionMatch[1], {
          userId: auth.userId,
          correlationId: String(req.headers['x-correlation-id'] || ''),
        });
        return json(res, 200, result);
      }

      const customersMatch = pathname.match(/^\/api\/v1\/tenants\/([^/]+)\/customers$/);
      if (customersMatch && method === 'GET') {
        req.headers['x-tenant-id'] = customersMatch[1];
        const ctx = resolveTenantContext(req, core.iam, core.tenantStore, { userId: auth.userId });
        const tenant = core.tenantStore.get(customersMatch[1]);
        ctx.odooDatabase = tenant?.odooDatabase || '';
        const provider = core.providers.customerProvider(ctx);
        const data = await provider.list(ctx, {
          page: url.searchParams.get('page'),
          pageSize: url.searchParams.get('pageSize'),
        });
        return json(res, 200, data, { correlationId: ctx.correlationId });
      }

      if (customersMatch && method === 'POST') {
        req.headers['x-tenant-id'] = customersMatch[1];
        const ctx = resolveTenantContext(req, core.iam, core.tenantStore, { userId: auth.userId });
        const tenant = core.tenantStore.get(customersMatch[1]);
        ctx.odooDatabase = tenant?.odooDatabase || '';
        const body = await readBody(req);
        const provider = core.providers.customerProvider(ctx);
        const customer = await provider.create(ctx, body);
        core.audit.append({
          actorId: ctx.userId,
          tenantId: ctx.tenantId,
          action: 'customer.create',
          resource: customer.id,
          correlationId: ctx.correlationId,
          result: 'ok',
        });
        return json(res, 201, { customer }, { correlationId: ctx.correlationId });
      }

      if (pathname === '/api/v1/economic/catalog' && method === 'GET') {
        if (!core.iam.can(auth.userId, ECONOMIC_PERMISSIONS.READ, url.searchParams.get('tenantId') || '')) {
          return json(res, 403, { error: 'permission denied', permission: ECONOMIC_PERMISSIONS.READ });
        }
        return json(res, 200, buildEconomicCatalog(core, auth.userId, url.searchParams.get('tenantId') || ''));
      }

      if (pathname === '/api/v1/economic/overview' && method === 'GET') {
        const tenantId = url.searchParams.get('tenantId') || '';
        if (!core.iam.can(auth.userId, ECONOMIC_PERMISSIONS.READ, tenantId)) {
          return json(res, 403, { error: 'permission denied', permission: ECONOMIC_PERMISSIONS.READ });
        }
        let customerTotal = null;
        let invoiceTotal = null;
        if (tenantId) {
          const tenantCtx = resolveTenantForEconomic(req, auth, tenantId);
          try {
            customerTotal = (await core.providers.customerProvider(tenantCtx).list(tenantCtx)).total;
          } catch {
            customerTotal = null;
          }
          try {
            invoiceTotal = (await core.providers.invoiceProvider(tenantCtx).list(tenantCtx)).total;
          } catch {
            invoiceTotal = null;
          }
        }
        const odooHealth = await odoo.client.health();
        return json(
          res,
          200,
          buildEconomicOverview({
            core,
            iamUserId: auth.userId,
            tenantId,
            odooHealth,
            customerTotal,
            invoiceTotal,
          })
        );
      }

      if (pathname === '/api/v1/economic/audit' && method === 'GET') {
        const tenantId = url.searchParams.get('tenantId') || '';
        if (!core.iam.can(auth.userId, ECONOMIC_PERMISSIONS.AUDIT_READ, tenantId)) {
          return json(res, 403, { error: 'permission denied', permission: ECONOMIC_PERMISSIONS.AUDIT_READ });
        }
        const q = String(url.searchParams.get('q') || '').toLowerCase();
        const rows = core.audit.tail(100).filter((row) => {
          if (tenantId && row.tenantId && row.tenantId !== tenantId) {
            return false;
          }
          if (!q) {
            return true;
          }
          return JSON.stringify(row).toLowerCase().includes(q);
        });
        return json(res, 200, { items: rows, total: rows.length, page: 1, pageSize: rows.length });
      }

      if (pathname === '/api/v1/economic/currencies' && method === 'GET') {
        if (!core.iam.can(auth.userId, ECONOMIC_PERMISSIONS.READ, url.searchParams.get('tenantId') || '')) {
          return json(res, 403, { error: 'permission denied', permission: ECONOMIC_PERMISSIONS.READ });
        }
        return json(res, 200, configuredCurrencies());
      }

      if (pathname === '/api/v1/economic/customers' && method === 'GET') {
        const tenantId = url.searchParams.get('tenantId') || '';
        if (!tenantId) {
          return json(res, 400, { error: 'tenantId required' });
        }
        if (!core.iam.can(auth.userId, ECONOMIC_PERMISSIONS.CUSTOMERS_READ, tenantId)) {
          return json(res, 403, { error: 'permission denied', permission: ECONOMIC_PERMISSIONS.CUSTOMERS_READ });
        }
        const ctx = resolveTenantForEconomic(req, auth, tenantId);
        const data = await core.providers.customerProvider(ctx).list(ctx, {
          page: url.searchParams.get('page'),
          pageSize: url.searchParams.get('pageSize'),
        });
        return json(res, 200, data, { correlationId: ctx.correlationId });
      }

      if (pathname === '/api/v1/economic/customers' && method === 'POST') {
        const body = await readBody(req);
        const tenantId = body.tenantId || url.searchParams.get('tenantId') || '';
        const gate = authorizeEconomicMutation(core, auth.userId, tenantId, 'customers.create');
        if (!gate.ok) {
          return json(res, gate.status, { error: gate.error, permission: gate.permission });
        }
        const ctx = resolveTenantForEconomic(req, auth, tenantId);
        const customer = await core.providers.customerProvider(ctx).create(ctx, body);
        core.audit.append({
          actorId: ctx.userId,
          tenantId: ctx.tenantId,
          action: 'customer.create',
          resource: customer.id,
          correlationId: ctx.correlationId,
          result: 'ok',
        });
        await core.events.emit({
          eventType: 'customer.created',
          tenantId: ctx.tenantId,
          correlationId: ctx.correlationId,
          payload: { id: customer.id },
        });
        return json(res, 201, { customer }, { correlationId: ctx.correlationId });
      }

      if (pathname === '/api/v1/economic/invoices' && method === 'GET') {
        const tenantId = url.searchParams.get('tenantId') || '';
        if (!tenantId) {
          return json(res, 400, { error: 'tenantId required' });
        }
        if (!core.iam.can(auth.userId, ECONOMIC_PERMISSIONS.INVOICES_READ, tenantId)) {
          return json(res, 403, { error: 'permission denied', permission: ECONOMIC_PERMISSIONS.INVOICES_READ });
        }
        const ctx = resolveTenantForEconomic(req, auth, tenantId);
        const data = await core.providers.invoiceProvider(ctx).list(ctx, {
          page: url.searchParams.get('page'),
          pageSize: url.searchParams.get('pageSize'),
        });
        return json(res, 200, data, { correlationId: ctx.correlationId });
      }

      if (pathname === '/api/v1/economic/invoices' && method === 'POST') {
        const body = await readBody(req);
        const tenantId = body.tenantId || url.searchParams.get('tenantId') || '';
        const gate = authorizeEconomicMutation(core, auth.userId, tenantId, 'invoices.create');
        if (!gate.ok) {
          return json(res, gate.status, { error: gate.error, permission: gate.permission });
        }
        const ctx = resolveTenantForEconomic(req, auth, tenantId);
        const invoice = await core.providers.invoiceProvider(ctx).create(ctx, body);
        core.audit.append({
          actorId: ctx.userId,
          tenantId: ctx.tenantId,
          action: 'invoice.create',
          resource: invoice.id,
          correlationId: ctx.correlationId,
          result: 'ok',
        });
        await core.events.emit({
          eventType: 'invoice.issued',
          tenantId: ctx.tenantId,
          correlationId: ctx.correlationId,
          payload: { id: invoice.id, status: invoice.status },
        });
        return json(res, 201, { invoice }, { correlationId: ctx.correlationId });
      }

      if (pathname === '/api/v1/economic/actions' && method === 'POST') {
        const body = await readBody(req);
        const tenantId = body.tenantId || url.searchParams.get('tenantId') || '';
        const gate = authorizeEconomicMutation(core, auth.userId, tenantId, body.action);
        return json(res, gate.status, gate.ok ? { ok: true, action: body.action } : { error: gate.error, permission: gate.permission });
      }
    }

    return json(res, 404, { error: 'not found' });
  } catch (err) {
    const code = err?.code || 'INTERNAL_ERROR';
    const status =
      code === 'TENANT_FORBIDDEN' || code === 'TENANT_SUSPENDED' || code === 'AUTH_REQUIRED'
        ? 403
        : 500;
    return json(res, status, { error: String(err?.message || err), code });
  }
});

server.listen(PORT, () => {
  console.log(`ecosystem-platform-api listening on :${PORT}`);
});
