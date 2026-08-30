import { PROVIDER_IDS } from '@ecosystem/contracts';
import { OdooJsonRpcClient } from './odoo-client.mjs';
import { OdooCustomerProvider } from './odoo-customer-provider.mjs';
import { OdooDatabaseProvisioner } from './odoo-database-provisioner.mjs';

export function createOdooAdapters(options = {}) {
  const baseUrl = options.baseUrl || process.env.ODOO_URL || 'http://localhost:8069';
  const client = new OdooJsonRpcClient({
    baseUrl,
    username: options.username || process.env.ODOO_USERNAME || 'admin',
    password: options.password || process.env.ODOO_PASSWORD || 'admin',
  });

  const provisioner = new OdooDatabaseProvisioner({
    baseUrl,
    masterPassword: options.masterPassword || process.env.ODOO_MASTER_PASSWORD,
    adminLogin: options.username || process.env.ODOO_USERNAME || 'admin',
    adminPassword: options.password || process.env.ODOO_PASSWORD || 'admin',
  });

  const customer = new OdooCustomerProvider(client);
  return { client, customer, provisioner };
}

export { OdooJsonRpcClient } from './odoo-client.mjs';
export { OdooCustomerProvider } from './odoo-customer-provider.mjs';
export { OdooDatabaseProvisioner } from './odoo-database-provisioner.mjs';
export { PROVIDER_IDS };
