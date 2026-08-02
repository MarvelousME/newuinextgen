import { chromium } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const BASE = (process.env.NGT_STAGING_BASE || 'https://nextgentutors.co.za/staging').replace(/\/$/, '');
const USER = process.env.NGT_STAGING_USER || '';
const PASS = process.env.NGT_STAGING_PASS || '';
const OUT = path.join(__dirname, 'deploy-out');
const ROOT = path.resolve(__dirname, '..');
const PLUGIN_ZIP = path.join(ROOT, 'dist', 'NextGenTutors-Companion.zip');
const THEME_ZIP = path.join(ROOT, 'dist', 'NextGenTutors-BeyondInfinity.zip');

async function login(page) {
  await page.goto(`${BASE}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  await page.fill('#user_login', USER);
  await page.fill('#user_pass', PASS);
  await page.click('#wp-submit');
  await page.waitForSelector('#wpadminbar, body.logged-in', { timeout: 90_000 });
}

function plain(html) {
  return html.replace(/<script[\s\S]*?<\/script>/gi, ' ').replace(/<style[\s\S]*?<\/style>/gi, ' ').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
}

async function main() {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  await login(page);

  // Try single delete with full trace
  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  const row = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i }).first();
  const plugin = await row.getAttribute('data-plugin');
  console.log('deleting', plugin);
  const del = row.locator('.delete a, a:has-text("Delete")').first();
  await del.click();
  await page.waitForLoadState('domcontentloaded');
  fs.writeFileSync(path.join(OUT, 'single-delete-1.html'), await page.content());
  console.log('after delete click url', page.url());
  console.log('after delete plain', plain(await page.content()).slice(0, 800));

  const confirm = page.locator('input#submit, input[name="submit"], button:has-text("Yes"), input[value*="Yes"]').first();
  console.log('confirm visible', await confirm.isVisible().catch(() => false));
  if (await confirm.isVisible().catch(() => false)) {
    await confirm.click();
    await page.waitForLoadState('domcontentloaded');
    fs.writeFileSync(path.join(OUT, 'single-delete-2.html'), await page.content());
    console.log('after confirm url', page.url());
    console.log('after confirm plain', plain(await page.content()).slice(0, 800));
  }

  await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
  console.log('remaining', await page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i }).count());

  // FileOrganizer - inspect capabilities
  await page.goto(`${BASE}/wp-admin/admin.php?page=fileorganizer`, { waitUntil: 'domcontentloaded' });
  fs.writeFileSync(path.join(OUT, 'fileorganizer.html'), await page.content());
  const fo = await page.content();
  const connector = fo.match(/url\s*:\s*['"]([^'"]*admin-ajax[^'"]*)['"]/i) || fo.match(/connector['"]?\s*:\s*['"]([^'"]+)['"]/i);
  console.log('fo connector', connector?.[1]);
  const nonce = fo.match(/nonce['"]?\s*:\s*['"]([^'"]+)['"]/i);
  console.log('fo nonce', nonce?.[1]);

  // Try elfinder command via ajax if we can find it
  if (connector) {
    const ajax = connector[1].startsWith('http') ? connector[1] : `${BASE}${connector[1].startsWith('/') ? '' : '/'}${connector[1]}`;
    // List root
    const resp = await context.request.post(ajax.includes('?') ? ajax : `${ajax}`, {
      form: {
        cmd: 'open',
        target: '',
        _: String(Date.now()),
      },
      failOnStatusCode: false,
    });
    console.log('elfinder open status', resp.status(), (await resp.text()).slice(0, 400));
  }

  await browser.close();
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
