/**
 * Final staging deploy: accept confirm() dialogs, purge broken companions,
 * install clean Companion + BeyondInfinity theme.
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

function plain(html) {
  return html
    .replace(/<script[\s\S]*?<\/script>/gi, ' ')
    .replace(/<style[\s\S]*?<\/style>/gi, ' ')
    .replace(/<[^>]+>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

async function login(page) {
  await page.goto(`${BASE}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  await page.fill('#user_login', USER);
  await page.fill('#user_pass', PASS);
  await page.check('#rememberme').catch(() => {});
  await page.click('#wp-submit');
  await page.waitForSelector('#wpadminbar, body.logged-in', { timeout: 90_000 });
  await page.goto(`${BASE}/wp-admin/`, { waitUntil: 'domcontentloaded' });
}

async function acceptDialogs(page) {
  page.on('dialog', async (d) => {
    console.log('dialog', d.type(), d.message().slice(0, 120));
    await d.accept();
  });
}

async function deleteAllCompanions(page) {
  for (let i = 0; i < 8; i++) {
    await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
    const rows = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i });
    const count = await rows.count();
    console.log('companions left', count);
    if (!count) return;

    const row = rows.first();
    const deactivate = row.locator('a:has-text("Deactivate")').first();
    if (await deactivate.isVisible().catch(() => false)) {
      await deactivate.click();
      await page.waitForLoadState('domcontentloaded');
      continue;
    }
    const del = row.locator('a:has-text("Delete")').first();
    await del.click();
    // confirm screen OR dialog already accepted
    await page.waitForTimeout(1000);
    const confirm = page.locator('input#submit, input[value*="Yes"], button:has-text("Yes, delete these files")').first();
    if (await confirm.isVisible().catch(() => false)) {
      await confirm.click();
    }
    await page.waitForLoadState('domcontentloaded');
  }
}

async function waitForInstallResult(page, kind) {
  for (let i = 0; i < 90; i++) {
    await page.waitForTimeout(2000);
    let html = '';
    try {
      html = await page.content();
    } catch {
      continue;
    }
    const p = plain(html);
    const hit = p.match(
      kind === 'plugin'
        ? /Plugin installed successfully|Destination folder already exists|The package could not be installed|Plugin installation failed|Unpacking the package/i
        : /Theme installed successfully|Destination folder already exists|The package could not be installed|Stylesheet is missing|Unpacking the package/i
    );
    if (hit) {
      console.log(kind, 'result', hit[0], '@', i);
      fs.writeFileSync(path.join(OUT, `${kind}-final.html`), html);
      console.log(p.slice(p.search(/Install|Plugin|Theme|Destination|error|Error|Stylesheet/i), 500));
      return hit[0];
    }
    if (i % 10 === 0) console.log(kind, 'waiting', i, page.url());
  }
  fs.writeFileSync(path.join(OUT, `${kind}-timeout.html`), await page.content().catch(() => ''));
  return 'TIMEOUT';
}

async function uploadViaUi(page, kind) {
  if (kind === 'plugin') {
    await page.goto(`${BASE}/wp-admin/plugin-install.php?tab=upload`, { waitUntil: 'domcontentloaded' });
  } else {
    await page.goto(`${BASE}/wp-admin/theme-install.php`, { waitUntil: 'domcontentloaded' });
    await page.getByRole('button', { name: /Upload Theme/i }).click({ timeout: 8000 }).catch(() => {});
  }
  await page.evaluate(() => {
    document.body.classList.add('show-upload-view');
    document.querySelectorAll('.wp-upload-form').forEach((el) => {
      el.style.display = 'block';
      el.style.visibility = 'visible';
    });
  });
  const input = kind === 'plugin' ? '#pluginzip' : '#themezip';
  const submit = kind === 'plugin' ? '#install-plugin-submit' : '#install-theme-submit';
  const zip = kind === 'plugin' ? PLUGIN_ZIP : THEME_ZIP;
  await page.setInputFiles(input, zip);
  await page.locator(submit).evaluate((el) => el.click());
  return waitForInstallResult(page, kind);
}

async function activateCompanion(page) {
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  const row = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i }).first();
  console.log('companion path', await row.getAttribute('data-plugin'));
  const act = row.locator('a:has-text("Activate")').first();
  if (await act.isVisible().catch(() => false)) {
    await act.click();
    await page.waitForLoadState('domcontentloaded');
  }
  const errors = (await page.locator('.notice-error, #message.error').allTextContents()).map((t) =>
    t.replace(/\s+/g, ' ').trim()
  );
  console.log('activate errors', errors);
  const activePath = await page
    .locator('#the-list tr.active')
    .filter({ hasText: /NextGenTutors-Companion/i })
    .first()
    .getAttribute('data-plugin')
    .catch(() => null);
  console.log('active companion', activePath);
  return !!activePath && !String(activePath).includes('/NextGenTutors-Companion/NextGenTutors-Companion');
}

async function activateTheme(page) {
  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  const card = page.locator('.theme').filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity/i }).first();
  console.log('BI theme cards', await card.count());
  const act = card.locator('.activate').first();
  if (await act.count()) {
    await act.click();
    await page.waitForLoadState('domcontentloaded');
  }
  console.log('active theme', plain(await page.locator('.theme.active').innerHTML()).slice(0, 160));
}

async function main() {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const page = await (await browser.newContext()).newPage();
  acceptDialogs(page);
  await login(page);

  await deleteAllCompanions(page);
  const pluginResult = await uploadViaUi(page, 'plugin');
  const pluginOk = await activateCompanion(page);

  // Theme: switch to hello/tt5, delete BI if any, upload
  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  const fallback = page.locator('div[data-slug="hello-elementor"] .activate, div[data-slug="twentytwentyfive"] .activate').first();
  if (await fallback.isVisible().catch(() => false)) {
    await fallback.click();
    await page.waitForLoadState('domcontentloaded');
  }
  const bi = page.locator('.theme').filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity/i }).first();
  if (await bi.count()) {
    await bi.click();
    await page.waitForTimeout(600);
    const del = page.locator('.theme-overlay .delete-theme, a.delete-theme').first();
    if (await del.count()) {
      await del.click();
      await page.waitForTimeout(2000);
    }
  }

  const themeResult = await uploadViaUi(page, 'theme');
  await activateTheme(page);

  await page.goto(`${BASE}/login/?role=parent`, { waitUntil: 'domcontentloaded' });
  const markers = await page.locator('#bi-login-role-parent, .bi-role-card, #ngc-loginform, .ngc-form--login').count();
  console.log('SUMMARY', { pluginResult, pluginOk, themeResult, markers, url: page.url() });
  fs.writeFileSync(path.join(OUT, 'login-final.html'), await page.content());

  await browser.close();
  if (!pluginOk) process.exit(2);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
