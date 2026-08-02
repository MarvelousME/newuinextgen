import { chromium } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const BASE = (process.env.NGT_STAGING_BASE || 'https://nextgentutors.co.za/staging').replace(/\/$/, '');
const USER = process.env.NGT_STAGING_USER || '';
const PASS = process.env.NGT_STAGING_PASS || '';

const browser = await chromium.launch({ channel: 'chrome', headless: true });
const page = await (await browser.newContext()).newPage();
await page.goto(`${BASE}/wp-login.php`, { waitUntil: 'domcontentloaded' });
await page.fill('#user_login', USER);
await page.fill('#user_pass', PASS);
await page.click('#wp-submit');
await page.waitForSelector('#wpadminbar', { timeout: 90_000 });

await page.goto(`${BASE}/wp-admin/plugins.php`, { waitUntil: 'domcontentloaded' });
const rows = page.locator('#the-list tr').filter({ hasText: /NextGenTutors-Companion/i });
const n = await rows.count();
console.log('companions', n);
for (let i = 0; i < n; i++) {
  const row = rows.nth(i);
  console.log(
    '-',
    await row.getAttribute('data-plugin'),
    (await row.getAttribute('class') || '').includes('active') ? 'ACTIVE' : 'inactive'
  );
}

await page.goto(`${BASE}/wp-admin/themes.php`, { waitUntil: 'domcontentloaded' });
console.log(
  'active theme:',
  (await page.locator('.theme.active').innerText()).replace(/\s+/g, ' ').trim().slice(0, 120)
);
console.log(
  'BI present:',
  await page.locator('.theme').filter({ hasText: /BeyondInfinity|NextGenTutors-BeyondInfinity/i }).count()
);

const themeZip = path.join(ROOT, 'dist', 'NextGenTutors-BeyondInfinity.zip');
const pluginZip = path.join(ROOT, 'dist', 'NextGenTutors-Companion.zip');
console.log('theme zip MB', (fs.statSync(themeZip).size / 1e6).toFixed(2));
console.log('plugin zip MB', (fs.statSync(pluginZip).size / 1e6).toFixed(2));

await browser.close();
