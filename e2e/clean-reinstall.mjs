/**
 * Clean-reinstall Companion + BeyondInfinity theme on staging.
 * Removes broken nested folders first so WordPress unpacks to the correct path.
 */
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

async function login(page) {
  await page.goto(`${BASE}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  await page.fill('#user_login', USER);
  await page.fill('#user_pass', PASS);
  await page.check('#rememberme').catch(() => {});
  await page.click('#wp-submit');
  await page.waitForSelector('#wpadminbar, #wpbody, body.logged-in', { timeout: 90_000 });
  await page.goto(`${BASE}/wp-admin/index.php`, { waitUntil: 'domcontentloaded' });
}

function extractNonce(html) {
  const m =
    html.match(/name=["']_wpnonce["']\s+value=["']([^"']+)["']/i) ||
    html.match(/value=["']([^"']+)["']\s+name=["']_wpnonce["']/i);
  if (!m) throw new Error('nonce missing');
  return m[1];
}

async function deleteAllCompanions(page) {
  for (let pass = 0; pass < 5; pass++) {
    await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
    const rows = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i });
    const count = await rows.count();
    console.log('companion delete pass', pass, 'count', count);
    if (!count) return;
    const row = rows.first();
    const deactivate = row.locator('a:has-text("Deactivate")').first();
    if (await deactivate.isVisible().catch(() => false)) {
      await deactivate.click();
      await page.waitForLoadState('domcontentloaded');
      continue;
    }
    const del = row.locator('a:has-text("Delete")').first();
    if (!(await del.count())) {
      console.log('no delete link for', await row.getAttribute('data-plugin'));
      return;
    }
    page.once('dialog', (d) => d.accept());
    await del.click();
    const confirm = page.locator('#submit, input[value*="Yes"], button:has-text("Yes, delete these files")').first();
    if (await confirm.isVisible().catch(() => false)) await confirm.click();
    await page.waitForLoadState('domcontentloaded');
  }
}

async function uploadPlugin(page, request) {
  await page.goto(`${BASE}/wp-admin/plugin-install.php?tab=upload`, { waitUntil: 'domcontentloaded' });
  await page.evaluate(() => {
    document.querySelectorAll('.wp-upload-form').forEach((el) => {
      el.style.display = 'block';
      el.style.visibility = 'visible';
    });
  });
  const nonce = extractNonce(await page.content());
  const resp = await request.post(`${BASE}/wp-admin/update.php?action=upload-plugin`, {
    multipart: {
      _wpnonce: nonce,
      _wp_http_referer: '/staging/wp-admin/plugin-install.php?tab=upload',
      pluginzip: {
        name: 'NextGenTutors-Companion.zip',
        mimeType: 'application/zip',
        buffer: fs.readFileSync(PLUGIN_ZIP),
      },
      'install-plugin-submit': 'Install Now',
    },
    timeout: 300_000,
    maxRedirects: 5,
    failOnStatusCode: false,
  });
  const body = await resp.text();
  fs.writeFileSync(path.join(OUT, 'clean-plugin-resp.html'), body);
  const plain = body.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');
  console.log('plugin upload status', resp.status());
  console.log('plugin plain snip', plain.slice(plain.search(/Plugin|installed|error|Destination/i), 500));
  return body;
}

async function activateCompanion(page) {
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  const row = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i }).first();
  console.log('plugin path', await row.getAttribute('data-plugin'));
  const act = row.locator('a:has-text("Activate")').first();
  if (await act.isVisible().catch(() => false)) {
    await act.click();
    await page.waitForLoadState('domcontentloaded');
  }
  // Capture fatal/activation errors
  const notices = await page.locator('.notice-error, #message.error, .wp-die-message').allTextContents();
  console.log('notices', notices.map((n) => n.replace(/\s+/g, ' ').trim()).slice(0, 5));
  const active = await page.locator('#the-list tr.active').filter({ hasText: /NextGenTutors-Companion/i }).count();
  console.log('active', active, 'data-plugin', await page.locator('#the-list tr.active').filter({ hasText: /NextGenTutors-Companion/i }).first().getAttribute('data-plugin').catch(() => null));
  return active > 0;
}

async function deleteBeyondInfinity(page) {
  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  // Ensure not active
  const fallback = page.locator('div[data-slug="hello-elementor"] .activate, div[data-slug="twentytwentyfive"] .activate').first();
  if (await fallback.isVisible().catch(() => false)) {
    await fallback.click();
    await page.waitForLoadState('domcontentloaded');
  }
  for (let i = 0; i < 3; i++) {
    await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
    const card = page.locator('.theme').filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity/i }).first();
    if (!(await card.count())) {
      console.log('no BI theme card');
      return;
    }
    await card.click();
    await page.waitForTimeout(700);
    const del = page.locator('.theme-overlay .delete-theme, a.delete-theme').first();
    if (!(await del.count())) return;
    page.once('dialog', (d) => d.accept());
    await del.click();
    await page.waitForTimeout(2000);
  }
}

async function uploadTheme(page, request) {
  await page.goto(`${BASE}/wp-admin/theme-install.php?upload`, { waitUntil: 'domcontentloaded' });
  await page.evaluate(() => {
    document.querySelectorAll('.wp-upload-form').forEach((el) => {
      el.style.display = 'block';
      el.style.visibility = 'visible';
    });
    document.body.classList.add('show-upload-view');
  });
  const nonce = extractNonce(await page.content());
  const resp = await request.post(`${BASE}/wp-admin/update.php?action=upload-theme`, {
    multipart: {
      _wpnonce: nonce,
      _wp_http_referer: '/staging/wp-admin/theme-install.php?upload',
      themezip: {
        name: 'NextGenTutors-BeyondInfinity.zip',
        mimeType: 'application/zip',
        buffer: fs.readFileSync(THEME_ZIP),
      },
      'install-theme-submit': 'Install Now',
    },
    timeout: 300_000,
    maxRedirects: 5,
    failOnStatusCode: false,
  });
  const body = await resp.text();
  fs.writeFileSync(path.join(OUT, 'clean-theme-resp.html'), body);
  const plain = body.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');
  console.log('theme upload status', resp.status());
  console.log('theme plain snip', plain.slice(plain.search(/Theme|installed|error|Destination|Stylesheet/i), 500));
  return body;
}

async function activateTheme(page) {
  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  const card = page.locator('.theme').filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity/i }).first();
  console.log('BI cards', await card.count());
  const act = card.locator('.activate').first();
  if (await act.count()) {
    await act.click();
    await page.waitForLoadState('domcontentloaded');
  }
  console.log('active', (await page.locator('.theme.active').innerText()).replace(/\s+/g, ' ').slice(0, 160));
}

async function main() {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  await login(page);

  await deleteAllCompanions(page);
  await uploadPlugin(page, context.request);
  const pluginOk = await activateCompanion(page);
  console.log('pluginOk', pluginOk);

  await deleteBeyondInfinity(page);
  await uploadTheme(page, context.request);
  await activateTheme(page);

  await page.goto(`${BASE}/login/?role=parent`, { waitUntil: 'domcontentloaded' });
  const markers = await page.locator('#bi-login-role-parent, .bi-role-card, #ngc-loginform, .ngc-form--login').count();
  console.log('login markers', markers, page.url());
  fs.writeFileSync(path.join(OUT, 'login-smoke.html'), await page.content());

  await browser.close();
  if (!pluginOk) process.exit(2);
  console.log('DONE');
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
