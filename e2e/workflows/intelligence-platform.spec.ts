/**
 * Intelligence platform — headed e2e against Docker WordPress.
 *
 *   cd e2e
 *   $env:BASE_URL='http://localhost:8900'
 *   npm run test:intelligence-headed
 */
import { test, expect } from '@playwright/test';
import { wpLogin } from '../helpers';

const INTEL_URL = '/wp-admin/admin.php?page=ngtmc-mission-control&tab=intelligence';

test.describe('Intelligence platform', () => {
  test.setTimeout(180_000);

  test.beforeEach(async ({ page }) => {
    await wpLogin(page);
  });

  test('overview loads KPIs, advanced charts, and draggable widgets', async ({ page }) => {
    await page.goto(INTEL_URL, { waitUntil: 'domcontentloaded', timeout: 120_000 });
    await expect(page.getByTestId('ngtmc-intelligence')).toBeVisible({ timeout: 30_000 });
    await expect(page.getByTestId('ngtmc-intel-widgets')).toBeVisible();
    await page.waitForFunction(
      () => document.querySelector('#ngtmc-intel-kpis')?.children.length > 0,
      { timeout: 45_000 },
    );
    await expect(page.getByTestId('ngtmc-chart-sankey')).toBeVisible();
    await expect(page.getByTestId('ngtmc-network-graph')).toBeVisible();
    await expect(page.getByTestId('ngtmc-chart-geo')).toBeVisible();
    await expect(page.getByTestId('ngtmc-chart-radar')).toBeVisible();
    await expect(page.getByTestId('ngtmc-chart-funnel')).toBeVisible();
    const widgets = page.locator('#ngtmc-intel-widgets .ngtmc-intel-widget');
    expect(await widgets.count()).toBeGreaterThan(3);
  });

  test('events virtual grid loads server-paged rows', async ({ page }) => {
    await page.goto(`${INTEL_URL}&intel_view=events`, {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await expect(page.getByTestId('ngtmc-virtual-grid')).toBeVisible({ timeout: 30_000 });
    await page.waitForFunction(
      () => document.querySelector('.ngtmc-virtual-row') !== null ||
        document.querySelector('.ngtmc-virtual-meta')?.textContent?.includes('0 total'),
      { timeout: 45_000 },
    );
    const meta = page.locator('.ngtmc-virtual-meta');
    await expect(meta).toContainText(/total/i);
  });

  test('REST emit ingests domain event (Hub bridge pattern)', async ({ page, request }) => {
    await page.goto(INTEL_URL, { waitUntil: 'domcontentloaded', timeout: 120_000 });
    await expect(page.getByTestId('ngtmc-intelligence')).toBeVisible({ timeout: 30_000 });

    const nonce = await page.evaluate(() => {
      return (window as unknown as { ngtmcIntel?: { nonce?: string } }).ngtmcIntel?.nonce || '';
    });
    expect(nonce.length).toBeGreaterThan(0);

    const cookies = await page.context().cookies();
    const cookieHeader = cookies.map((c) => `${c.name}=${c.value}`).join('; ');

    const emitRes = await request.post('/wp-json/ngc/v1/intelligence/emit', {
      headers: {
        Cookie: cookieHeader,
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      data: {
        event_key: 'workflow.action.e2e_test',
        plugin_slug: 'automation-hub',
        module: 'workflows',
        severity: 'info',
        message: 'E2E bridge emit test',
        payload: { source: 'e2e' },
      },
    });

    expect(emitRes.ok()).toBeTruthy();
    const body = await emitRes.json();
    expect(body.id).toBeGreaterThan(0);

    const verifyRes = await request.get(
      `/wp-json/ngc/v1/intelligence/events?search=${encodeURIComponent('E2E bridge emit test')}&per_page=5`,
      { headers: { Cookie: cookieHeader, 'X-WP-Nonce': nonce } },
    );
    expect(verifyRes.ok()).toBeTruthy();
    const verifyBody = await verifyRes.json();
    expect((verifyBody.rows || []).some((r: { message?: string }) => r.message?.includes('E2E bridge emit test'))).toBeTruthy();

    await page.goto(`${INTEL_URL}&intel_view=events`, {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await expect(page.getByTestId('ngtmc-virtual-grid')).toBeVisible({ timeout: 30_000 });
    await page.locator('#ngtmc-intel-search').fill('E2E bridge emit test');
    await page.locator('#ngtmc-intel-search').dispatchEvent('change');
    await page.waitForFunction(
      () => {
        const rows = document.querySelectorAll('.ngtmc-virtual-row');
        for (const row of rows) {
          if (row.textContent?.includes('E2E bridge emit test')) return true;
        }
        return false;
      },
      { timeout: 45_000 },
    );
  });

  test('settings form exposes Teams/Slack/SMS/WhatsApp webhooks', async ({ page }) => {
    await page.goto(`${INTEL_URL}&intel_view=settings`, {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await expect(page.locator('[name="teams_webhook_url"]')).toBeVisible({ timeout: 30_000 });
    await expect(page.locator('[name="slack_webhook_url"]')).toBeVisible();
    await expect(page.locator('[name="whatsapp_webhook_url"]')).toBeVisible();
    await expect(page.locator('[name="sms_webhook_url"]')).toBeVisible();
  });

  test('NL ask returns an answer', async ({ page }) => {
    await page.goto(INTEL_URL, { waitUntil: 'domcontentloaded', timeout: 120_000 });
    await expect(page.getByTestId('ngtmc-intel-ask-input')).toBeVisible({ timeout: 30_000 });
    await page.getByTestId('ngtmc-intel-ask-input').fill('What is platform health?');
    await page.getByTestId('ngtmc-intel-ask-btn').click();
    await expect(page.getByTestId('ngtmc-intel-ask-answer')).not.toBeEmpty({ timeout: 30_000 });
  });

  test('REST intelligence endpoints reject unauthenticated access', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/intelligence/dashboard', { failOnStatusCode: false });
    expect([401, 403]).toContain(res.status());
  });

  test('config API does not expose webhook secret', async ({ page, request }) => {
    await page.goto(`${INTEL_URL}&intel_view=settings`, { waitUntil: 'domcontentloaded', timeout: 120_000 });
    const nonce = await page.evaluate(() => {
      return (window as unknown as { ngtmcIntel?: { nonce?: string } }).ngtmcIntel?.nonce || '';
    });
    const cookies = await page.context().cookies();
    const cookieHeader = cookies.map((c) => `${c.name}=${c.value}`).join('; ');
    const res = await request.get('/wp-json/ngc/v1/intelligence/config', {
      headers: { Cookie: cookieHeader, 'X-WP-Nonce': nonce },
    });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    expect(body).not.toHaveProperty('webhook_secret');
  });
});
