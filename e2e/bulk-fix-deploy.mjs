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
  await page.waitForSelector('#wpadminbar, body.logged-in, #wpbody', { timeout: 90_000 });
  await page.goto(`${BASE}/wp-admin/`, { waitUntil: 'domcontentloaded' });
}

async function bulkDeleteCompanions(page) {
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  // Deactivate any active companions first
  const activeActs = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i }).locator('a:has-text("Deactivate")');
  while (await activeActs.count()) {
    await activeActs.first().click();
    await page.waitForLoadState('domcontentloaded');
  }

  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  const boxes = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i }).locator('input[type="checkbox"][name="checked[]"]');
  const n = await boxes.count();
  console.log('checkboxes', n);
  for (let i = 0; i < n; i++) await boxes.nth(i).check({ force: true });
  if (n === 0) return;

  await page.locator('#bulk-action-selector-top').selectOption('delete-selected');
  page.once('dialog', (d) => d.accept());
  await page.locator('#doaction').click();
  await page.waitForLoadState('domcontentloaded');

  // Confirmation screen
  const confirm = page.locator('input#submit, input[value*="Yes"], button:has-text("Yes, delete these files")').first();
  if (await confirm.isVisible().catch(() => false)) {
    await confirm.click();
    await page.waitForLoadState('domcontentloaded');
  }
  fs.writeFileSync(path.join(OUT, 'bulk-delete.html'), await page.content());
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  console.log(
    'remaining companions',
    await page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i }).count()
  );
}

async function uiUploadPlugin(page) {
  await page.goto(`${BASE}/wp-admin/plugin-install.php?tab=upload`, { waitUntil: 'domcontentloaded' });
  await page.evaluate(() => {
    document.body.classList.add('show-upload-view');
    document.querySelectorAll('.wp-upload-form').forEach((el) => {
      el.style.display = 'block';
      el.style.visibility = 'visible';
    });
  });
  await page.setInputFiles('#pluginzip', PLUGIN_ZIP);
  await page.locator('#install-plugin-submit').evaluate((el) => el.click());

  for (let i = 0; i < 90; i++) {
    await page.waitForTimeout(2000);
    const html = await page.content();
    const plain = html.replace(/<script[\s\S]*?<\/script>/gi, ' ').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');
    if (/Plugin installed successfully|Destination folder already exists|The package could not be installed|Plugin installation failed|Unpacking the package/i.test(plain)) {
      console.log('plugin result @', i, plain.match(/Plugin installed successfully|Destination folder already exists|The package could not be installed|Plugin installation failed|failed: [^.]+\./i)?.[0]);
      fs.writeFileSync(path.join(OUT, 'ui-plugin-result.html'), html);
      console.log(plain.slice(plain.search(/Install|Plugin|Destination|error|Error/i), 450));
      break;
    }
    if (i % 10 === 0) console.log('plugin wait', i, page.url());
  }
}

async function activate(page) {
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  const row = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i }).first();
  console.log('rows', await page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i }).count());
  console.log('path', await row.getAttribute('data-plugin'));
  const act = row.locator('a:has-text("Activate")').first();
  if (await act.isVisible().catch(() => false)) {
    await act.click();
    await page.waitForLoadState('domcontentloaded');
  }
  console.log(
    'errors',
    (await page.locator('.notice-error, #message.error').allTextContents()).map((t) => t.replace(/\s+/g, ' ').trim())
  );
  console.log(
    'active',
    await page.locator('#the-list tr.active').filter({ hasText: /NextGenTutors-Companion/i }).count(),
    await page.locator('#the-list tr.active').filter({ hasText: /NextGenTutors-Companion/i }).first().getAttribute('data-plugin').catch(() => null)
  );
}

async function uiUploadTheme(page) {
  await page.goto(`${BASE}/wp-admin/theme-install.php`, { waitUntil: 'domcontentloaded' });
  await page.getByRole('button', { name: /Upload Theme/i }).click({ timeout: 5000 }).catch(() => {});
  await page.evaluate(() => {
    document.body.classList.add('show-upload-view');
    document.querySelectorAll('.wp-upload-form').forEach((el) => {
      el.style.display = 'block';
      el.style.visibility = 'visible';
    });
  });
  await page.setInputFiles('#themezip', THEME_ZIP);
  await page.locator('#install-theme-submit').evaluate((el) => el.click());
  for (let i = 0; i < 90; i++) {
    await page.waitForTimeout(2000);
    const html = await page.content();
    const plain = html.replace(/<script[\s\S]*?<\/script>/gi, ' ').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');
    if (/Theme installed successfully|Destination folder already exists|The package could not be installed|Stylesheet is missing/i.test(plain)) {
      console.log('theme result @', i, plain.match(/Theme installed successfully|Destination folder already exists|The package could not be installed|Stylesheet is missing/i)?.[0]);
      fs.writeFileSync(path.join(OUT, 'ui-theme-result.html'), html);
      console.log(plain.slice(plain.search(/Install|Theme|Destination|error|Error|Stylesheet/i), 450));
      break;
    }
    if (i % 10 === 0) console.log('theme wait', i, page.url());
  }
}

async function main() {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const page = await (await browser.newContext()).newPage();
  await login(page);
  await bulkDeleteCompanions(page);
  await uiUploadPlugin(page);
  await activate(page);
  await uiUploadTheme(page);
  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  console.log('BI cards', await page.locator('.theme').filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity/i }).count());
  const act = page.locator('.theme').filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity/i }).locator('.activate').first();
  if (await act.count()) {
    await act.click();
    await page.waitForLoadState('domcontentloaded');
  }
  console.log('active theme', (await page.locator('.theme.active').innerText()).replace(/\s+/g, ' ').slice(0, 160));
  await page.goto(`${BASE}/login/?role=parent`, { waitUntil: 'domcontentloaded' });
  console.log('login markers', await page.locator('#bi-login-role-parent, .bi-role-card, #ngc-loginform').count());
  await browser.close();
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
