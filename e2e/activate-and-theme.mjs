/**
 * Activate Companion duplicates cleanup + FileOrganizer theme file replace.
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
const THEME_ZIP = path.join(ROOT, 'dist', 'NextGenTutors-BeyondInfinity.zip');

async function login(page) {
  await page.goto(`${BASE}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  await page.fill('#user_login', USER);
  await page.fill('#user_pass', PASS);
  await page.check('#rememberme').catch(() => {});
  await page.click('#wp-submit');
  await page.waitForSelector('#wpadminbar, #wpbody, body.logged-in', { timeout: 90_000 });
  await page.goto(`${BASE}/wp-admin/index.php`, { waitUntil: 'domcontentloaded' });
}

async function activateCompanion(page) {
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  const rows = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i });
  const count = await rows.count();
  console.log('companion rows', count);
  for (let i = 0; i < count; i++) {
    const row = rows.nth(i);
    const plugin = await row.getAttribute('data-plugin');
    const classes = await row.getAttribute('class');
    console.log('row', i, plugin, classes);
  }
  // Activate the first inactive companion
  for (let i = 0; i < count; i++) {
    const row = rows.nth(i);
    const act = row.locator('span.activate a, a:has-text("Activate")').first();
    if (await act.isVisible().catch(() => false)) {
      console.log('activating', await row.getAttribute('data-plugin'));
      await act.click();
      await page.waitForLoadState('domcontentloaded');
      break;
    }
  }
  const active = await page.locator('#the-list tr.active').filter({ hasText: /NextGenTutors-Companion/i }).count();
  console.log('active companions', active);
  return active > 0;
}

async function tryFileOrganizer(page) {
  const candidates = [
    `${BASE}/wp-admin/admin.php?page=fileorganizer`,
    `${BASE}/wp-admin/admin.php?page=fileorganizer-pro`,
    `${BASE}/wp-admin/admin.php?page=fileorg`,
  ];
  for (const url of candidates) {
    const resp = await page.goto(url, { waitUntil: 'domcontentloaded' });
    console.log('fo probe', url, resp?.status(), page.url());
    const body = await page.content();
    if (/elfinder|fileorganizer|elf-toolbar|cwd/i.test(body) && !/Sorry, you are not allowed/i.test(body)) {
      fs.writeFileSync(path.join(__dirname, 'deploy-out', 'fileorganizer.html'), body);
      return true;
    }
  }
  return false;
}

async function overwriteThemeViaUpdateCore(page, request) {
  // Last-resort: use theme editor? no.
  // Use Softaculous / Site tools? skip.
  // Direct filesystem via FileOrganizer AJAX if we find nonce.
  return false;
}

async function main() {
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  await login(page);

  const ok = await activateCompanion(page);
  if (!ok) throw new Error('Could not activate Companion');

  const fo = await tryFileOrganizer(page);
  console.log('fileorganizer available', fo);

  // Theme install via UI with setInputFiles + real click (not force) after revealing form
  await page.goto(`${BASE}/wp-admin/theme-install.php`, { waitUntil: 'domcontentloaded' });
  const uploadBtn = page.getByRole('button', { name: /Upload Theme/i }).or(page.locator('.upload-view-toggle')).first();
  await uploadBtn.click({ timeout: 10_000 }).catch(() => {});
  await page.waitForTimeout(500);
  await page.evaluate(() => {
    document.querySelectorAll('.wp-upload-form').forEach((el) => {
      el.style.display = 'block';
      el.classList.add('show-upload-view');
    });
    document.body.classList.add('show-upload-view');
  });
  await page.setInputFiles('#themezip', THEME_ZIP);
  // Use locator click without waiting for navigation forever
  await page.locator('#install-theme-submit').evaluate((el) => el.click());
  await page.waitForTimeout(5000);
  // Poll for result page content
  for (let i = 0; i < 60; i++) {
    await page.waitForTimeout(2000);
    const html = await page.content();
    if (/Theme installed successfully|Destination folder already exists|Stylesheet is missing|The package could not be installed/i.test(html)) {
      console.log('theme result detected at poll', i);
      fs.writeFileSync(path.join(__dirname, 'deploy-out', 'theme-ui-result.html'), html);
      console.log('snip', html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').slice(0, 500));
      break;
    }
    console.log('waiting theme install...', i, page.url());
  }

  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  const active = (await page.locator('.theme.active').innerText()).replace(/\s+/g, ' ');
  console.log('active theme', active);
  const hasBI = await page.locator('.theme').filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity/i }).count();
  console.log('BI theme cards', hasBI);
  if (hasBI) {
    const act = page.locator('.theme').filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity/i }).locator('.activate').first();
    if (await act.count()) {
      await act.click();
      await page.waitForLoadState('domcontentloaded');
    }
  }

  await page.goto(`${BASE}/login/?role=parent`, { waitUntil: 'domcontentloaded' });
  console.log('login markers', await page.locator('#bi-login-role-parent, .bi-role-card, #ngc-loginform, .ngc-form--login').count());

  await browser.close();
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
