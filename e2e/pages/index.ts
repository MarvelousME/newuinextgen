/**
 * Minimal Page Object Model for recurring surfaces.
 */
import type { Page, Locator } from '@playwright/test';
import { expect } from '@playwright/test';
import { dismissCookieOrOverlays, gotoReady, primaryNgForm } from '../helpers';

export class HomePage {
  constructor(private readonly page: Page) {}
  async open() {
    await gotoReady(this.page, '/');
  }
  hero(): Locator {
    return this.page.locator('h1.ngi-title, .ngi-hero h1, .bi-theme-content h1').first();
  }
  primaryCta(): Locator {
    return this.page
      .locator(
        'a.ngt-btn--primary[href*="find-a-tutor"], .ngi-hero a[href*="find-a-tutor"], a[data-ngi-open], a.ngi-btn'
      )
      .first();
  }
}

export class LoginPage {
  constructor(private readonly page: Page) {}
  async open(role?: 'parent' | 'student' | 'tutor') {
    await gotoReady(this.page, role ? `/login/?role=${role}` : '/login/');
  }
  roleParent(): Locator {
    return this.page.locator('#bi-login-role-parent');
  }
  form(): Locator {
    return this.page.locator('#ngc-loginform, form#loginform, form.ngc-form').first();
  }
}

export class RegisterPage {
  constructor(private readonly page: Page) {}
  async openParent() {
    await gotoReady(this.page, '/register/?role=parent');
  }
  intake(): Locator {
    return primaryNgForm(this.page);
  }
}

export class WpAdminPage {
  constructor(private readonly page: Page) {}
  async gotoPlugin(slug: string) {
    await this.page.goto(`/wp-admin/admin.php?page=${slug}`, {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await dismissCookieOrOverlays(this.page);
  }
  async expectAdminShell() {
    await expect(this.page.locator('#wpadminbar, .ngc-admin-shell, #wpbody').first()).toBeVisible({
      timeout: 30_000,
    });
  }
}

export class FindTutorPage {
  constructor(private readonly page: Page) {}
  async open() {
    await gotoReady(this.page, '/find-a-tutor/');
  }
  heading(): Locator {
    return this.page.locator('h1').first();
  }
}
