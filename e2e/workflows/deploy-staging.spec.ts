/**
 * Staging deploy via authenticated multipart upload.
 * Companion is restored first (safe), then theme (lean ZIP without videos).
 */
import { test, expect, type Page, type APIRequestContext } from '@playwright/test';
import path from 'path';
import fs from 'fs';

const BASE = (process.env.NGT_STAGING_BASE || 'https://nextgentutors.co.za/staging').replace(/\/$/, '');
const USER = process.env.NGT_STAGING_USER || '';
const PASS = process.env.NGT_STAGING_PASS || '';
const ROOT = path.resolve(__dirname, '..', '..');
const THEME_ZIP = path.join(ROOT, 'dist', 'NextGenTutors-BeyondInfinity.zip');
const PLUGIN_ZIP = path.join(ROOT, 'dist', 'NextGenTutors-Companion.zip');

test.describe.configure({ mode: 'serial' });
test.setTimeout(420_000);

async function wpLogin(page: Page) {
  await page.goto(`${BASE}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  await page.locator('#user_login').fill(USER);
  await page.locator('#user_pass').fill(PASS);
  await page.locator('#rememberme').check().catch(() => undefined);
  await page.locator('#wp-submit').click();
  await page.waitForSelector('#wpadminbar, #wpbody, body.logged-in', { timeout: 90_000 });
  await page.goto(`${BASE}/wp-admin/index.php`, { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#wpbody')).toBeVisible({ timeout: 60_000 });
}

function extractNonce(html: string, name: string): string {
  const patterns = [
    new RegExp(`name=["']${name}["']\\s+value=["']([^"']+)["']`, 'i'),
    new RegExp(`value=["']([^"']+)["']\\s+name=["']${name}["']`, 'i'),
  ];
  for (const re of patterns) {
    const m = html.match(re);
    if (m) return m[1];
  }
  throw new Error(`Nonce ${name} not found`);
}

async function deletePluginIfPresent(page: Page) {
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  const row = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion|NextGen Companion|nextgencompanion/i }).first();
  if (!(await row.count())) return;

  const deactivate = row.locator('a:has-text("Deactivate")').first();
  if (await deactivate.isVisible().catch(() => false)) {
    await deactivate.click();
    await page.waitForLoadState('domcontentloaded');
  }
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  const row2 = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion|NextGen Companion|nextgencompanion/i }).first();
  const del = row2.locator('a:has-text("Delete")').first();
  if (await del.count()) {
    page.once('dialog', (d) => d.accept());
    await del.click();
    const confirm = page.locator('#submit, input[value*="Yes"], button:has-text("Yes, delete these files")').first();
    if (await confirm.isVisible().catch(() => false)) {
      await confirm.click();
    }
    await page.waitForLoadState('domcontentloaded');
  }
}

async function uploadPlugin(page: Page, request: APIRequestContext) {
  await page.goto(`${BASE}/wp-admin/plugin-install.php?tab=upload`, { waitUntil: 'domcontentloaded' });
  const html = await page.content();
  const nonce = extractNonce(html, '_wpnonce');
  const resp = await request.post(`${BASE}/wp-admin/update.php?action=upload-plugin`, {
    multipart: {
      _wpnonce: nonce,
      _wp_http_referer: `${BASE}/wp-admin/plugin-install.php?tab=upload`,
      pluginzip: {
        name: 'NextGenTutors-Companion.zip',
        mimeType: 'application/zip',
        buffer: fs.readFileSync(PLUGIN_ZIP),
      },
      'install-plugin-submit': 'Install Now',
    },
    timeout: 300_000,
    maxRedirects: 0,
    failOnStatusCode: false,
  });
  const body = await resp.text();
  console.log('PLUGIN_UPLOAD_STATUS', resp.status());
  console.log('PLUGIN_UPLOAD_SNIP', body.replace(/\s+/g, ' ').slice(0, 500));
  if (/Destination folder already exists/i.test(body)) {
    throw new Error('PLUGIN_EXISTS');
  }
  if (resp.status() >= 400 && !/Plugin installed|Activate Plugin/i.test(body)) {
    throw new Error(`Plugin upload failed HTTP ${resp.status()}`);
  }
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  const row = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion|NextGen Companion/i }).first();
  const act = row.locator('a:has-text("Activate")').first();
  if (await act.isVisible().catch(() => false)) {
    await act.click();
    await page.waitForLoadState('domcontentloaded');
  }
}

async function activateFallbackTheme(page: Page) {
  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  const fallback = page
    .locator(
      'div[data-slug="hello-elementor"] .activate, div[data-slug="twentytwentyfour"] .activate, div[data-slug="twentytwentyfive"] .activate, div[data-slug="astra"] .activate'
    )
    .first();
  if (await fallback.isVisible().catch(() => false)) {
    await fallback.click();
    await page.waitForLoadState('domcontentloaded');
  }
}

async function deleteBeyondInfinityTheme(page: Page) {
  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  const card = page
    .locator('.theme')
    .filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity|beyondinfinity/i })
    .first();
  if (!(await card.count())) return;
  await card.click();
  await page.waitForTimeout(700);
  const del = page.locator('.theme-overlay .delete-theme, .theme-actions .delete-theme, a.delete-theme').first();
  if (await del.count()) {
    page.once('dialog', (d) => d.accept());
    await del.click();
    await page.waitForTimeout(2500);
  }
}

async function uploadTheme(page: Page, request: APIRequestContext) {
  await page.goto(`${BASE}/wp-admin/theme-install.php?upload`, { waitUntil: 'domcontentloaded' });
  const html = await page.content();
  const nonce = extractNonce(html, '_wpnonce');
  const resp = await request.post(`${BASE}/wp-admin/update.php?action=upload-theme`, {
    multipart: {
      _wpnonce: nonce,
      _wp_http_referer: `${BASE}/wp-admin/theme-install.php?upload`,
      themezip: {
        name: 'NextGenTutors-BeyondInfinity.zip',
        mimeType: 'application/zip',
        buffer: fs.readFileSync(THEME_ZIP),
      },
      'install-theme-submit': 'Install Now',
    },
    timeout: 300_000,
    maxRedirects: 0,
    failOnStatusCode: false,
  });
  const body = await resp.text();
  console.log('THEME_UPLOAD_STATUS', resp.status());
  console.log('THEME_UPLOAD_SNIP', body.replace(/\s+/g, ' ').slice(0, 500));
  if (/Destination folder already exists/i.test(body)) {
    throw new Error('THEME_EXISTS');
  }
  if (/exceeds the maximum upload|exceeds the limit|post_max_size|upload_max_filesize/i.test(body)) {
    throw new Error('THEME_TOO_LARGE');
  }
  if (resp.status() >= 400 && !/Theme installed|Activate|Stylesheet/i.test(body)) {
    throw new Error(`Theme upload failed HTTP ${resp.status()}`);
  }
}

async function activateBeyondInfinity(page: Page) {
  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  const act = page
    .locator('.theme')
    .filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity/i })
    .locator('.activate')
    .first();
  if (await act.count()) {
    await act.click();
    await page.waitForLoadState('domcontentloaded');
  }
}

test('deploy theme + companion to staging', async ({ page, context }) => {
  test.skip(!USER || !PASS, 'Set NGT_STAGING_USER and NGT_STAGING_PASS');
  expect(fs.existsSync(THEME_ZIP)).toBeTruthy();
  expect(fs.existsSync(PLUGIN_ZIP)).toBeTruthy();
  console.log('THEME_ZIP_MB', (fs.statSync(THEME_ZIP).size / 1e6).toFixed(2));
  console.log('PLUGIN_ZIP_MB', (fs.statSync(PLUGIN_ZIP).size / 1e6).toFixed(2));

  await wpLogin(page);
  const request = context.request;

  // 1) Restore Companion first
  try {
    await deletePluginIfPresent(page);
    await uploadPlugin(page, request);
  } catch (e) {
    if (String(e).includes('PLUGIN_EXISTS')) {
      await deletePluginIfPresent(page);
      await uploadPlugin(page, request);
    } else {
      throw e;
    }
  }

  await expect(
    page.locator('#the-list tr.active').filter({ hasText: /NextGenTutors-Companion|NextGen Companion/i }).first()
  ).toBeVisible({ timeout: 30_000 });

  // 2) Theme replace
  await activateFallbackTheme(page);
  await deleteBeyondInfinityTheme(page);
  try {
    await uploadTheme(page, request);
  } catch (e) {
    if (String(e).includes('THEME_EXISTS')) {
      await deleteBeyondInfinityTheme(page);
      await uploadTheme(page, request);
    } else {
      throw e;
    }
  }
  await activateBeyondInfinity(page);

  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  await expect(page.locator('.theme.active')).toContainText(/BeyondInfinity|NextGen/i);

  await page.goto(`${BASE}/login/?role=parent`, { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#bi-login-role-parent, .bi-role-card, #ngc-loginform').first()).toBeVisible({
    timeout: 45_000,
  });
});
