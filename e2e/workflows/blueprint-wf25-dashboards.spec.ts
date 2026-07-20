import { test, expect } from '@playwright/test';
import { dismissCookieOrOverlays } from '../helpers';

test.describe('Blueprint WF-25 Dashboard Workflow', () => {
  test('login page exposes dashboard entry route', async ({ page }) => {
    await page.goto('/login/');
    await dismissCookieOrOverlays(page);
    await expect(page.locator('form.bi-ngc-form, form.ngc-form, #loginform').first()).toBeVisible();
    await expect(page.locator('body')).toContainText(/log in|sign in|dashboard/i);
  });

  test('REST student dashboard requires authentication', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/dashboard/student');
    expect([401, 403]).toContain(res.status());
  });

  test('REST parent dashboard requires authentication', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/dashboard/parent');
    expect([401, 403]).toContain(res.status());
  });

  test('REST tutor dashboard requires authentication', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/dashboard/tutor');
    expect([401, 403]).toContain(res.status());
  });

  test('REST admin dashboard requires admin', async ({ request }) => {
    const res = await request.get('/wp-json/ngc/v1/dashboard/admin');
    expect([401, 403]).toContain(res.status());
  });

  test('parent-dashboard route is reachable (auth gate or shell)', async ({ page }) => {
    await page.goto('/parent-dashboard/');
    await dismissCookieOrOverlays(page);
    const shell = page.locator('.bi-dashboard-rest, .bi-dashboard-fallback, [data-dashboard]');
    const loginGate = page.locator('form.bi-ngc-form, form.ngc-form, #loginform, .ngc-login-form');
    await expect(shell.first().or(loginGate.first())).toBeVisible({ timeout: 15_000 });
  });
});
