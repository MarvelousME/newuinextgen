import { chromium } from '@playwright/test';
import fs from 'fs';

const BASE = (process.env.NGT_STAGING_BASE || 'https://nextgentutors.co.za/staging').replace(/\/$/, '');
const USER = process.env.NGT_STAGING_USER || '';
const PASS = process.env.NGT_STAGING_PASS || '';

const browser = await chromium.launch({ channel: 'chrome', headless: true });
const page = await (await browser.newContext()).newPage();
await page.goto(`${BASE}/wp-login.php`, { waitUntil: 'domcontentloaded' });
await page.fill('#user_login', USER);
await page.fill('#user_pass', PASS);
await page.click('#wp-submit');
await page.waitForSelector('#wpadminbar, body.logged-in', { timeout: 90_000 });

for (const url of [
  `${BASE}/wp-admin/admin.php?page=fileorganizer`,
  `${BASE}/wp-admin/admin.php?page=fileorganizer-pro`,
  `${BASE}/wp-admin/admin.php?page=softaculous`,
]) {
  await page.goto(url, { waitUntil: 'domcontentloaded' });
  const html = (await page.content()).toLowerCase();
  console.log(
    url.split('/staging')[1],
    'title=',
    (await page.title()).slice(0, 70),
    'elfinder=',
    html.includes('elfinder'),
    'denied=',
    html.includes('not allowed') || html.includes('sorry')
  );
}

await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
const rows = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i });
const n = await rows.count();
console.log('companion count', n);
for (let i = 0; i < n; i++) {
  const row = rows.nth(i);
  console.log(i, await row.getAttribute('data-plugin'));
  console.log(' ', ((await row.locator('.row-actions').innerText().catch(() => '')) || '').replace(/\s+/g, ' ').trim());
}

if (fs.existsSync('deploy-out/bulk-delete.html')) {
  const h = fs.readFileSync('deploy-out/bulk-delete.html', 'utf8');
  const plain = h.replace(/<script[\s\S]*?<\/script>/gi, ' ').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');
  console.log('bulk-delete snip', plain.slice(0, 900));
}

await browser.close();
