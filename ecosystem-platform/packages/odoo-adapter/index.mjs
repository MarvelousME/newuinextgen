import { PROVIDER_IDS } from '@ecosystem/contracts';
import { OdooJsonRpcClient } from './odoo-client.mjs';
import { OdooCustomerProvider } from './odoo-customer-provider.mjs';
import { OdooInvoiceProvider } from './odoo-invoice-provider.mjs';
import { OdooDatabaseProvisioner } from './odoo-database-provisioner.mjs';
import { resolveOdooLogin } from './odoo-secrets.mjs';

export function createOdooAdapters(options = {}) {
  const baseUrl = options.baseUrl || process.env.ODOO_URL || 'http://localhost:8069';
  const username = resolveOdooLogin(options.username || process.env.ODOO_USERNAME);
  const password = options.password || process.env.ODOO_PASSWORD || process.env.ODOO_ADMIN_PASSWORD || '';
  const client = new OdooJsonRpcClient({
    baseUrl,
    username,
    password,
  });

  const provisioner = new OdooDatabaseProvisioner({
    baseUrl,
    masterPassword: options.masterPassword || process.env.ODOO_MASTER_PASSWORD,
    adminLogin: username,
    adminPassword: password,
  });

  const customer = new OdooCustomerProvider(client);
  const invoices = new OdooInvoiceProvider(client);
  return { client, customer, invoices, provisioner };
}

export { OdooJsonRpcClient } from './odoo-client.mjs';
export { OdooCustomerProvider } from './odoo-customer-provider.mjs';
export { OdooInvoiceProvider } from './odoo-invoice-provider.mjs';
export { OdooDatabaseProvisioner } from './odoo-database-provisioner.mjs';
export { requireOdooSecret, resolveOdooLogin } from './odoo-secrets.mjs';
export { PROVIDER_IDS };
