/**
 * Login role paths — parent/student/tutor disambiguation before the form.
 */
import { test, expect } from '@playwright/test';
import { dismissCookieOrOverlays, gotoReady } from '../helpers';

test.describe('Login role paths', () => {
  test('login shows continue-as role cards before the form', async ({ page }) => {
    await gotoReady(page, '/login/');
    await dismissCookieOrOverlays(page);

    await expect(page.locator('#bi-login-role-parent')).toBeVisible({ timeout: 20_000 });
    await expect(page.locator('#bi-login-role-student')).toBeVisible();
    await expect(page.locator('#bi-login-role-tutor')).toBeVisible();
    await expect(page.locator('#ngc-loginform')).toHaveCount(0);
  });

  test('selecting parent opens the sign-in form with password toggle', async ({ page }) => {
    await gotoReady(page, '/login/?role=parent');
    await dismissCookieOrOverlays(page);

    await expect(page.locator('#bi-login-role-parent')).toHaveClass(/is-active/);
    await expect(page.locator('#ngc-loginform')).toBeVisible({ timeout: 20_000 });
    await expect(page.locator('#user_pass')).toBeVisible();

    const toggle = page.locator('.bi-password-toggle');
    await expect(toggle).toBeVisible({ timeout: 10_000 });
    await toggle.click();
    await expect(page.locator('#user_pass')).toHaveAttribute('type', 'text');
    await toggle.click();
    await expect(page.locator('#user_pass')).toHaveAttribute('type', 'password');
  });

  test('failed login shows recoverable error guidance', async ({ page }) => {
    await gotoReady(page, '/login/?role=student&login=failed');
    await dismissCookieOrOverlays(page);

    const alert = page.locator('.bi-login__error[role="alert"]');
    await expect(alert).toBeVisible({ timeout: 20_000 });
    await expect(alert).toContainText(/Sign-in didn’t work|password|Reset/i);
  });
});
