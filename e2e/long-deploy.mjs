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
  await page.click('#wp-submit');
  await page.waitForSelector('#wpadminbar, body.logged-in', { timeout: 90_000 });
}

async function main() {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  page.on('dialog', async (d) => {
    console.log('dialog', d.type(), d.message().slice(0, 100));
    await d.accept();
  });
  await login(page);

  // Direct-href deletes
  for (let i = 0; i < 8; i++) {
    await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
    const row = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i }).first();
    if (!(await row.count())) break;
    const del = row.locator('span.delete a, .delete a').first();
    const href = await del.getAttribute('href');
    console.log('delete href', href);
    if (!href) break;
    const url = href.startsWith('http') ? href : `${BASE}/wp-admin/${href.replace(/^\//, '')}`;
    await page.goto(url, { waitUntil: 'domcontentloaded' });
    console.log('delete page', page.url());
    console.log(plain(await page.content()).slice(0, 500));
    fs.writeFileSync(path.join(OUT, `del-${i}.html`), await page.content());
    const confirm = page.locator('input#submit, input[value*="Yes"], button:has-text("Yes, delete these files")').first();
    if (await confirm.isVisible().catch(() => false)) {
      await confirm.click();
      await page.waitForLoadState('domcontentloaded');
      console.log('confirmed delete', plain(await page.content()).slice(0, 400));
    }
  }
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  console.log('companions after direct delete', await page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i }).count());

  // Long-wait plugin upload
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

  let final = '';
  for (let i = 0; i < 120; i++) {
    await page.waitForTimeout(3000);
    let html = '';
    try {
      html = await page.content();
    } catch {
      continue;
    }
    const p = plain(html);
    if (/Plugin installed successfully/i.test(p)) {
      final = 'SUCCESS';
      console.log('PLUGIN SUCCESS @', i);
      break;
    }
    if (/Destination folder already exists|could not be installed|installation failed|incompatible|Fatal error/i.test(p)) {
      final = p.match(/Destination folder already exists|could not be installed|installation failed|Fatal error[^.]*/i)?.[0] || 'FAIL';
      console.log('PLUGIN FAIL', final, '@', i);
      fs.writeFileSync(path.join(OUT, 'plugin-fail.html'), html);
      console.log(p.slice(0, 800));
      break;
    }
    if (i % 10 === 0) console.log('plugin poll', i, page.url(), p.includes('Unpacking') ? 'unpacking' : 'other');
  }
  console.log('plugin final', final || 'TIMEOUT');
  if (!final) fs.writeFileSync(path.join(OUT, 'plugin-timeout.html'), await page.content().catch(() => ''));

  // Activate if present with correct path
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  const rows = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i });
  for (let i = 0; i < (await rows.count()); i++) {
    const row = rows.nth(i);
    const plugin = await row.getAttribute('data-plugin');
    console.log('row', plugin);
    if (plugin === 'NextGenTutors-Companion/nextgencompanion.php') {
      const act = row.locator('a:has-text("Activate")').first();
      if (await act.isVisible().catch(() => false)) {
        await act.click();
        await page.waitForLoadState('domcontentloaded');
      }
    }
  }
  console.log(
    'active',
    await page.locator('#the-list tr.active').filter({ hasText: /NextGenTutors-Companion/i }).first().getAttribute('data-plugin').catch(() => null)
  );

  // Theme upload long wait
  await page.goto(`${BASE}/wp-admin/theme-install.php`, { waitUntil: 'domcontentloaded' });
  await page.getByRole('button', { name: /Upload Theme/i }).click().catch(() => {});
  await page.evaluate(() => {
    document.body.classList.add('show-upload-view');
    document.querySelectorAll('.wp-upload-form').forEach((el) => {
      el.style.display = 'block';
      el.style.visibility = 'visible';
    });
  });
  await page.setInputFiles('#themezip', THEME_ZIP);
  await page.locator('#install-theme-submit').evaluate((el) => el.click());
  final = '';
  for (let i = 0; i < 120; i++) {
    await page.waitForTimeout(3000);
    let html = '';
    try {
      html = await page.content();
    } catch {
      continue;
    }
    const p = plain(html);
    if (/Theme installed successfully/i.test(p)) {
      final = 'SUCCESS';
      console.log('THEME SUCCESS @', i);
      break;
    }
    if (/Destination folder already exists|could not be installed|Stylesheet is missing|Fatal error/i.test(p)) {
      final = p.match(/Destination folder already exists|could not be installed|Stylesheet is missing|Fatal error[^.]*/i)?.[0] || 'FAIL';
      console.log('THEME FAIL', final, '@', i);
      fs.writeFileSync(path.join(OUT, 'theme-fail.html'), html);
      console.log(p.slice(0, 800));
      break;
    }
    if (i % 10 === 0) console.log('theme poll', i, page.url(), p.includes('Unpacking') ? 'unpacking' : 'other');
  }
  console.log('theme final', final || 'TIMEOUT');

  await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
  console.log('BI cards', await page.locator('.theme').filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity/i }).count());
  const act = page.locator('.theme').filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity/i }).locator('.activate').first();
  if (await act.count()) {
    await act.click();
    await page.waitForLoadState('domcontentloaded');
  }
  console.log('active theme', plain(await page.locator('.theme.active').innerHTML()).slice(0, 160));
  await page.goto(`${BASE}/login/?role=parent`, { waitUntil: 'domcontentloaded' });
  console.log('login markers', await page.locator('#bi-login-role-parent, .bi-role-card, #ngc-loginform').count());

  await browser.close();
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
