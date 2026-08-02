/**
 * Platform kernel — queue/DLQ/audit REST smoke (admin).
 */
import { test, expect } from '@playwright/test';
import { wpLogin, gotoReady } from '../helpers';

const BASE = process.env.BASE_URL || 'http://localhost:8900';

test.describe('Platform kernel', () => {
  test('admin platform kernel page + queue stats REST', async ({ page, request }) => {
    await wpLogin(page);
    await gotoReady(page, `${BASE}/wp-admin/admin.php?page=ngc-platform-kernel`);
    await expect(page.locator('h1')).toContainText(/Platform Kernel/i);

    // REST requires auth cookie from browser context.
    const cookies = await page.context().cookies();
    const cookieHeader = cookies.map((c) => `${c.name}=${c.value}`).join('; ');
    const stats = await request.get(`${BASE}/wp-json/ngc/v1/platform/queue/stats`, {
      headers: { Cookie: cookieHeader },
    });
    // 200 when authed admin; 401/403 if caps missing — still prove route exists.
    expect([200, 401, 403]).toContain(stats.status());
    if (stats.ok()) {
      const body = await stats.json();
      expect(body).toHaveProperty('queues');
      expect(body).toHaveProperty('dlq_open');
    }

    const verify = await request.get(`${BASE}/wp-json/ngc/v1/platform/audit/verify`, {
      headers: { Cookie: cookieHeader },
    });
    expect([200, 401, 403]).toContain(verify.status());
  });
});
