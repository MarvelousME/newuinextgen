import { chromium } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const OUT = path.join(__dirname, 'deploy-out');
fs.mkdirSync(OUT, { recursive: true });

const USER = process.env.NGT_STAGING_USER || 'admin';
const PASS = process.env.NGT_STAGING_PASS || '';
const bases = [
  'https://nextgentutors.co.za/staging',
  'https://www.nextgentutors.co.za/staging',
];

const browser = await chromium.launch({ channel: 'chrome', headless: true });
for (const BASE of bases) {
  const context = await browser.newContext({ ignoreHTTPSErrors: true });
  const page = await context.newPage();
  console.log('TRY', BASE);
  try {
    const r = await page.goto(`${BASE}/wp-login.php`, { waitUntil: 'domcontentloaded', timeout: 90_000 });
    console.log('login page status', r?.status(), 'final', page.url());
    fs.writeFileSync(path.join(OUT, `login-form-${BASE.includes('www') ? 'www' : 'apex'}.html`), await page.content());
    await page.fill('#user_login', USER);
    await page.fill('#user_pass', PASS);
    await page.click('#wp-submit');
    await page.waitForTimeout(10_000);
    console.log('after', page.url(), 'title', await page.title());
    const text = (await page.locator('body').innerText()).replace(/\s+/g, ' ').slice(0, 400);
    console.log('body', text);
    console.log('markers', await page.locator('#wpadminbar, #wpbody, body.logged-in, #login_error').count());
    const err = await page.locator('#login_error').innerText().catch(() => '');
    if (err) console.log('login_error', err.replace(/\s+/g, ' ').trim());
  } catch (e) {
    console.log('ERR', e.message);
  }
  await context.close();
}
await browser.close();
