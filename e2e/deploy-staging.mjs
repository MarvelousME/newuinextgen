import { chromium } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const BASE = (process.env.NGT_STAGING_BASE || 'https://nextgentutors.co.za/staging').replace(/\/$/, '');
const USER = process.env.NGT_STAGING_USER || '';
const PASS = process.env.NGT_STAGING_PASS || '';
const PLUGIN_ZIP = path.join(ROOT, 'dist', 'NextGenTutors-Companion.zip');
const THEME_ZIP = path.join(ROOT, 'dist', 'NextGenTutors-BeyondInfinity.zip');
const OUT = path.join(__dirname, 'deploy-out');

function extractNonce(html, name = '_wpnonce') {
  const patterns = [
    new RegExp(`name=["']${name}["']\\s+value=["']([^"']+)["']`, 'i'),
    new RegExp(`value=["']([^"']+)["']\\s+name=["']${name}["']`, 'i'),
  ];
  for (const re of patterns) {
    const m = html.match(re);
    if (m) return m[1];
  }
  // upload-plugin specific
  const m3 = html.match(/id=["']_wpnonce["'][^>]*value=["']([^"']+)["']/i);
  if (m3) return m3[1];
  throw new Error(`nonce ${name} missing`);
}

async function login(page) {
  await page.goto(`${BASE}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  await page.fill('#user_login', USER);
  await page.fill('#user_pass', PASS);
  await page.check('#rememberme').catch(() => {});
  await page.click('#wp-submit');
  await page.waitForSelector('#wpadminbar, #wpbody, body.logged-in', { timeout: 90_000 });
  await page.goto(`${BASE}/wp-admin/index.php`, { waitUntil: 'domcontentloaded' });
}

async function ensureUploadFormVisible(page, kind) {
  const toggle =
    kind === 'plugin'
      ? page.locator('.upload-view-toggle, a.upload, button:has-text("Upload Plugin"), a:has-text("Upload Plugin")').first()
      : page.locator('.upload-view-toggle, a.upload, button:has-text("Upload Theme"), a:has-text("Upload Theme")').first();
  if (await toggle.isVisible().catch(() => false)) {
    await toggle.click();
  }
  await page.evaluate(() => {
    document.querySelectorAll('.wp-upload-form, .upload-plugin, .upload-theme').forEach((el) => {
      el.style.display = 'block';
      el.style.visibility = 'visible';
      el.style.height = 'auto';
      el.style.overflow = 'visible';
    });
  });
}

async function multipartUpload(page, request, { kind, zipPath, zipName }) {
  const url =
    kind === 'plugin'
      ? `${BASE}/wp-admin/plugin-install.php?tab=upload`
      : `${BASE}/wp-admin/theme-install.php?upload`;
  await page.goto(url, { waitUntil: 'domcontentloaded' });
  await ensureUploadFormVisible(page, kind);
  const html = await page.content();
  fs.writeFileSync(path.join(OUT, `${kind}-form.html`), html);
  const nonce = extractNonce(html);
  console.log(kind, 'nonce', nonce);

  const action =
    kind === 'plugin'
      ? `${BASE}/wp-admin/update.php?action=upload-plugin`
      : `${BASE}/wp-admin/update.php?action=upload-theme`;
  const fileField = kind === 'plugin' ? 'pluginzip' : 'themezip';
  const submitName = kind === 'plugin' ? 'install-plugin-submit' : 'install-theme-submit';

  const resp = await request.post(action, {
    multipart: {
      _wpnonce: nonce,
      _wp_http_referer: url.replace(BASE, '') || url,
      [fileField]: {
        name: zipName,
        mimeType: 'application/zip',
        buffer: fs.readFileSync(zipPath),
      },
      [submitName]: 'Install Now',
    },
    timeout: 300_000,
    maxRedirects: 5,
    failOnStatusCode: false,
  });
  const body = await resp.text();
  fs.writeFileSync(path.join(OUT, `${kind}-resp.html`), body);
  console.log(kind, 'status', resp.status(), 'url', resp.url());
  console.log(kind, 'snip', body.replace(/\s+/g, ' ').slice(0, 700));
  return body;
}

async function deleteCompanion(page) {
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  const row = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion|NextGen Companion|nextgencompanion/i }).first();
  if (!(await row.count())) {
    console.log('companion not listed');
    return;
  }
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
    if (await confirm.isVisible().catch(() => false)) await confirm.click();
    await page.waitForLoadState('domcontentloaded');
  }
}

async function activateCompanion(page) {
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  const names = await page.locator('#the-list tr td.plugin-title strong, #the-list tr .plugin-title strong').allTextContents();
  console.log('plugins after upload:', names);
  const row = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion|NextGen Companion/i }).first();
  const act = row.locator('a:has-text("Activate")').first();
  if (await act.isVisible().catch(() => false)) {
    await act.click();
    await page.waitForLoadState('domcontentloaded');
  }
  const active = await page.locator('#the-list tr.active').filter({ hasText: /NextGenTutors-Companion|NextGen Companion/i }).count();
  console.log('companion active count', active);
  return active > 0;
}

async function main() {
  if (!USER || !PASS) throw new Error('missing credentials');
  fs.mkdirSync(OUT, { recursive: true });
  console.log('plugin zip', PLUGIN_ZIP, fs.existsSync(PLUGIN_ZIP), fs.statSync(PLUGIN_ZIP).size);
  console.log('theme zip', THEME_ZIP, fs.existsSync(THEME_ZIP), fs.statSync(THEME_ZIP).size);

  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  await login(page);

  // Restore companion
  await deleteCompanion(page);
  let body = await multipartUpload(page, context.request, {
    kind: 'plugin',
    zipPath: PLUGIN_ZIP,
    zipName: 'NextGenTutors-Companion.zip',
  });
  if (/Destination folder already exists/i.test(body)) {
    await deleteCompanion(page);
    body = await multipartUpload(page, context.request, {
      kind: 'plugin',
      zipPath: PLUGIN_ZIP,
      zipName: 'NextGenTutors-Companion.zip',
    });
  }
  const okPlugin = await activateCompanion(page);
  if (!okPlugin) {
    // Fallback: classic UI form submit
    console.log('Trying UI form.submit fallback for plugin...');
    await page.goto(`${BASE}/wp-admin/plugin-install.php?tab=upload`, { waitUntil: 'domcontentloaded' });
    await ensureUploadFormVisible(page, 'plugin');
    await page.setInputFiles('#pluginzip', PLUGIN_ZIP);
    await Promise.all([
      page.waitForNavigation({ timeout: 180_000 }).catch(() => null),
      page.evaluate(() => {
        const input = document.getElementById('pluginzip');
        if (!input || !input.form) throw new Error('missing plugin form');
        HTMLFormElement.prototype.submit.call(input.form);
      }),
    ]);
    fs.writeFileSync(path.join(OUT, 'plugin-ui-fallback.html'), await page.content());
    console.log('fallback url', page.url());
    await activateCompanion(page);
  }

  // Theme: activate fallback, delete BI, upload lean zip
  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  const fallback = page.locator('div[data-slug="twentytwentyfive"] .activate, div[data-slug="hello-elementor"] .activate').first();
  if (await fallback.isVisible().catch(() => false)) {
    await fallback.click();
    await page.waitForLoadState('domcontentloaded');
  }
  const card = page.locator('.theme').filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity/i }).first();
  if (await card.count()) {
    await card.click();
    await page.waitForTimeout(600);
    const del = page.locator('.theme-overlay .delete-theme, a.delete-theme').first();
    if (await del.count()) {
      page.once('dialog', (d) => d.accept());
      await del.click();
      await page.waitForTimeout(2000);
    }
  }

  body = await multipartUpload(page, context.request, {
    kind: 'theme',
    zipPath: THEME_ZIP,
    zipName: 'NextGenTutors-BeyondInfinity.zip',
  });
  if (/Destination folder already exists/i.test(body)) {
    throw new Error('Theme folder still exists after delete — manual cleanup needed');
  }

  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  const themes = await page.locator('.theme .theme-name, .theme span').allTextContents();
  console.log('themes sample', themes.slice(0, 40));
  const actTheme = page.locator('.theme').filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity/i }).locator('.activate').first();
  if (await actTheme.count()) {
    await actTheme.click();
    await page.waitForLoadState('domcontentloaded');
  }
  const activeTheme = await page.locator('.theme.active').innerText();
  console.log('active theme', activeTheme.replace(/\s+/g, ' ').slice(0, 200));

  await page.goto(`${BASE}/login/?role=parent`, { waitUntil: 'domcontentloaded' });
  const hasRole = await page.locator('#bi-login-role-parent, .bi-role-card, #ngc-loginform').count();
  console.log('login markers', hasRole, 'url', page.url());

  await browser.close();
  if (!okPlugin && hasRole === 0) process.exit(2);
  console.log('DONE');
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
