import { test, expect } from '@playwright/test';

test.describe('Blueprint WF-11 Payment Workflow', () => {
  test('REST wallet requires authentication', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/wallet');
    expect([401, 403]).toContain(res.status());
  });

  test('REST invoices list requires authentication', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/invoices');
    expect([401, 403]).toContain(res.status());
  });

  test('WooCommerce storefront responds or returns expected fallback', async ({ request }) => {
    const res = await request.get('/shop/');
    expect([200, 301, 302, 404]).toContain(res.status());
  });
});
