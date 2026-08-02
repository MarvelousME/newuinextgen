import { chromium } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
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
await page.goto(`${BASE}/wp-admin/`, { waitUntil: 'domcontentloaded' });

const links = await page.locator('#adminmenu a').evaluateAll((as) =>
  as
    .map((a) => ({ t: (a.textContent || '').trim().replace(/\s+/g, ' '), h: a.getAttribute('href') || '' }))
    .filter((x) => /file|soft|manager|ftp|host|cpanel|organizer/i.test(x.t + ' ' + x.h))
);
console.log(JSON.stringify(links, null, 2));

for (const f of ['deploy-out/theme-fail.html', 'deploy-out/plugin-fail.html']) {
  const h = fs.readFileSync(path.join(__dirname, f), 'utf8');
  const m = h.match(/class="[^"]*notice[^"]*"[^>]*>[\s\S]{0,400}/gi) || [];
  console.log('\nnotices in', f);
  for (const n of m.slice(0, 10)) {
    console.log(
      '-',
      n
        .replace(/<[^>]+>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, 220)
    );
  }
  const unpack = h.match(/id="progress"[^>]*>[\s\S]{0,800}|class="update-php"[\s\S]{0,1200}/i);
  if (unpack) console.log('progress', unpack[0].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').slice(0, 400));
}

await browser.close();
