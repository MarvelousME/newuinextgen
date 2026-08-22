# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: tc-21-26-security.spec.ts >> TC-21 SQL injection inputs sanitized on contact/find forms
- Location: workflows\tc-21-26-security.spec.ts:32:5

# Error details

```
TimeoutError: apiRequestContext.get: Timeout 30000ms exceeded.
Call log:
  - → GET http://127.0.0.1:8890/
    - user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.7827.55 Safari/537.36
    - accept: */*
    - accept-encoding: gzip,deflate,br
  - ← 301 Moved Permanently
    - date: Sun, 16 Aug 2026 10:30:43 GMT
    - server: Apache/2.4.62 (Debian)
    - x-powered-by: PHP/8.2.28
    - permissions-policy: private-state-token-redemption=(self "https://www.google.com" "https://www.gstatic.com" "https://recaptcha.net" "https://challenges.cloudflare.com" "https://hcaptcha.com"), private-state-token-issuance=(self "https://www.google.com" "https://www.gstatic.com" "https://recaptcha.net" "https://challenges.cloudflare.com" "https://hcaptcha.com")
    - x-redirect-by: WordPress
    - location: http://localhost:8890/
    - content-length: 0
    - keep-alive: timeout=5, max=100
    - connection: Keep-Alive
    - content-type: text/html; charset=UTF-8
  - → GET http://localhost:8890/
    - user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.7827.55 Safari/537.36
    - accept: */*
    - accept-encoding: gzip,deflate,br

```

# Test source

```ts
  1   | /**
  2   |  * TC matrix helpers for headed E2E (BASE_URL default :8890).
  3   |  */
  4   | import fs from 'node:fs';
  5   | import path from 'node:path';
  6   | import { expect, type APIRequestContext, type Page, type TestInfo } from '@playwright/test';
  7   | import {
  8   |   dismissCookieOrOverlays,
  9   |   expectFormSubmitted,
  10  |   fillNgForm,
  11  |   gotoReady,
  12  |   primaryNgForm,
  13  |   submitNgForm,
  14  |   testEmail,
  15  |   wpAdminPassword,
  16  |   wpAdminUser,
  17  |   wpLogin,
  18  | } from '../helpers';
  19  | import { DEMO_PERSONAS, demoPassword } from './lesson-e2e';
  20  | 
  21  | export const TC_EVIDENCE_ROOT = path.resolve(__dirname, '../../delivery/evidence/tc-matrix');
  22  | 
  23  | export function tcEvidenceDir(): string {
  24  |   const id = process.env.TC_RUN_ID || new Date().toISOString().replace(/[:.]/g, '-');
  25  |   const dir = path.join(TC_EVIDENCE_ROOT, id);
  26  |   fs.mkdirSync(path.join(dir, 'screenshots'), { recursive: true });
  27  |   fs.mkdirSync(path.join(dir, 'notes'), { recursive: true });
  28  |   return dir;
  29  | }
  30  | 
  31  | export async function tcShot(page: Page, dir: string, name: string) {
  32  |   await page.screenshot({ path: path.join(dir, 'screenshots', name), fullPage: true }).catch(() => undefined);
  33  | }
  34  | 
  35  | export function annotateTc(testInfo: TestInfo, id: string, title: string) {
  36  |   testInfo.annotations.push({ type: 'tc', description: `${id}: ${title}` });
  37  | }
  38  | 
  39  | export function deepCrmEnabled() {
  40  |   return process.env.E2E_DEEP_CRM === '1';
  41  | }
  42  | 
  43  | export function payfastSettleEnabled() {
  44  |   return process.env.E2E_PAYFAST_SETTLE === '1';
  45  | }
  46  | 
  47  | export async function loginPersona(page: Page, email: string, password = demoPassword) {
  48  |   await page.goto('/wp-login.php', { waitUntil: 'domcontentloaded', timeout: 120_000 });
  49  |   await page.locator('#user_login').fill(email);
  50  |   await page.locator('#user_pass').fill(password);
  51  |   await Promise.all([
  52  |     page.waitForURL((u) => !u.pathname.includes('wp-login.php'), { timeout: 90_000, waitUntil: 'commit' }),
  53  |     page.locator('#wp-submit').click(),
  54  |   ]);
  55  |   const cookies = await page.context().cookies();
  56  |   expect(cookies.some((c) => /wordpress_logged_in/i.test(c.name)), `login ${email}`).toBeTruthy();
  57  | }
  58  | 
  59  | export async function ensureSiteUp(request: APIRequestContext) {
  60  |   // Prefer localhost — WP often 301s 127.0.0.1 → localhost and Playwright can stall on the hop.
  61  |   const candidates = [
  62  |     process.env.BASE_URL || 'http://localhost:8890',
  63  |     'http://localhost:8890',
  64  |     'http://127.0.0.1:8890',
  65  |   ];
  66  |   let lastErr: unknown;
  67  |   for (const base of [...new Set(candidates)]) {
  68  |     try {
> 69  |       const res = await request.get(base.replace(/\/$/, '') + '/', {
      |                                 ^ TimeoutError: apiRequestContext.get: Timeout 30000ms exceeded.
  70  |         maxRedirects: 5,
  71  |         timeout: 30_000,
  72  |       });
  73  |       if (res.status() < 500) {
  74  |         return;
  75  |       }
  76  |       lastErr = new Error(`HTTP ${res.status()} from ${base}`);
  77  |     } catch (e) {
  78  |       lastErr = e;
  79  |     }
  80  |   }
  81  |   throw lastErr instanceof Error ? lastErr : new Error(String(lastErr));
  82  | }
  83  | 
  84  | export function note(dir: string, file: string, body: unknown) {
  85  |   fs.writeFileSync(path.join(dir, 'notes', file), typeof body === 'string' ? body : JSON.stringify(body, null, 2));
  86  | }
  87  | 
  88  | export {
  89  |   DEMO_PERSONAS,
  90  |   demoPassword,
  91  |   dismissCookieOrOverlays,
  92  |   expectFormSubmitted,
  93  |   fillNgForm,
  94  |   gotoReady,
  95  |   primaryNgForm,
  96  |   submitNgForm,
  97  |   testEmail,
  98  |   wpAdminPassword,
  99  |   wpAdminUser,
  100 |   wpLogin,
  101 | };
  102 | 
```