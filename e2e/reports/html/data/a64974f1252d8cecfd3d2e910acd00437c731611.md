# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: system-verification.spec.ts >> System verification — public >> 04 booking / match CTA opens or remains actionable
- Location: workflows\system-verification.spec.ts:109:18

# Error details

```
TimeoutError: locator.click: Timeout 30000ms exceeded.
Call log:
  - waiting for locator('[data-ngi-open]:not(.ngi-sticky), [data-ngt-open-booking], .ngi-btn').first()
    - locator resolved to <a href="http://localhost:8900/find-a-tutor" class="ngi-btn ngi-btn-primary ngi-magnetic">Find My Tutor</a>
  - attempting click action
    - scrolling into view if needed
    - done scrolling
    - forcing action
    - performing click action
    - click action done
    - waiting for scheduled navigations to finish

```

# Test source

```ts
  57  |     await Promise.all([
  58  |       page.waitForURL(/wp-login\.php/, { timeout: 15_000 }),
  59  |       confirm.click(),
  60  |     ]);
  61  |   }
  62  | }
  63  | 
  64  | export async function openDemoControlCentre(page: Page) {
  65  |   await page.goto('/wp-admin/admin.php?page=ngc-demo-control', {
  66  |     waitUntil: 'domcontentloaded',
  67  |     timeout: 120_000,
  68  |   });
  69  |   await expect(page.getByTestId('ngc-demo-control')).toBeVisible({ timeout: 30_000 });
  70  | }
  71  | 
  72  | export async function fillNgForm(
  73  |   page: Page,
  74  |   fields: Record<string, string>,
  75  |   options?: { select?: Record<string, string>; form?: Locator }
  76  | ) {
  77  |   const form = options?.form ?? primaryNgForm(page);
  78  |   await form.scrollIntoViewIfNeeded();
  79  | 
  80  |   for (const [name, value] of Object.entries(options?.select ?? {})) {
  81  |     await form.locator(`select[name="${name}"]`).selectOption(value);
  82  |   }
  83  | 
  84  |   for (const [name, value] of Object.entries(fields)) {
  85  |     const locator = form.locator(`[name="${name}"]`);
  86  |     const tag = await locator.evaluate((el) => el.tagName.toLowerCase()).catch(() => 'input');
  87  |     if (tag === 'select') {
  88  |       await locator.selectOption(value);
  89  |     } else {
  90  |       await locator.fill(value);
  91  |     }
  92  |   }
  93  | }
  94  | 
  95  | /**
  96  |  * Submit via HTMLFormElement.submit() so ngc-validation cannot preventDefault.
  97  |  * Returns the admin-post redirect response (Location includes ngc_submitted).
  98  |  */
  99  | export async function submitNgForm(page: Page, form?: Locator): Promise<Response | null> {
  100 |   const target = form ?? primaryNgForm(page);
  101 |   await target.scrollIntoViewIfNeeded();
  102 | 
  103 |   const responsePromise = page.waitForResponse(
  104 |     (r) => r.url().includes('admin-post.php') && r.status() >= 300 && r.status() < 400,
  105 |     { timeout: 60_000 }
  106 |   );
  107 | 
  108 |   await target.evaluate((el) => (el as HTMLFormElement).submit());
  109 | 
  110 |   let response: Response | null = null;
  111 |   try {
  112 |     response = await responsePromise;
  113 |   } catch {
  114 |     response = null;
  115 |   }
  116 | 
  117 |   await page.waitForLoadState('domcontentloaded').catch(() => null);
  118 |   return response;
  119 | }
  120 | 
  121 | /**
  122 |  * Confirm submission. Toast JS strips ?ngc_submitted= from the URL via replaceState,
  123 |  * so we assert on the redirect Location and/or the success toast.
  124 |  */
  125 | export async function expectFormSubmitted(
  126 |   page: Page,
  127 |   formId: string,
  128 |   adminPostResponse?: Response | null
  129 | ) {
  130 |   if (adminPostResponse) {
  131 |     const location = adminPostResponse.headers()['location'] || '';
  132 |     expect(location).toContain(`ngc_submitted=${formId}`);
  133 |   }
  134 | 
  135 |   const toast = page.locator('#ngt-toast');
  136 |   await expect(toast).toContainText(/thank you|submitted successfully/i, {
  137 |     timeout: 20_000,
  138 |   });
  139 | }
  140 | 
  141 | export async function openHomeBookingModal(page: Page) {
  142 |   await gotoReady(page, '/');
  143 |   const cta = page.locator('[data-ngi-open]:not(.ngi-sticky), [data-ngt-open-booking], .ngi-btn').first();
  144 |   if (!(await cta.isVisible().catch(() => false))) {
  145 |     await expect(page.locator('body')).toContainText(/NextGen|Tutor|Book|Learn/i);
  146 |     return;
  147 |   }
  148 |   await cta.scrollIntoViewIfNeeded();
  149 |   await cta.click({ force: true });
  150 |   const modal = page.locator('#ngiBookingModal, [role="dialog"], .ngi-modal');
  151 |   if (await modal.first().isVisible().catch(() => false)) {
  152 |     await expect(modal.first()).toBeVisible();
  153 |   }
  154 | }
  155 | 
  156 | /** Marketing / auth pages used by full-system headed verification. */
> 157 | export const PUBLIC_SYSTEM_PAGES: Array<{
      |             ^ TimeoutError: locator.click: Timeout 30000ms exceeded.
  158 |   path: string;
  159 |   name: string;
  160 |   mustMatch: RegExp;
  161 |   landmark?: string;
  162 | }> = [
  163 |   { path: '/', name: 'Home', mustMatch: /Tutor|NextGen|Pace|Learn/i, landmark: 'h1' },
  164 |   { path: '/about/', name: 'About', mustMatch: /South African|Tutor|NextGen|exist/i, landmark: 'h1' },
  165 |   { path: '/find-a-tutor/', name: 'Find a Tutor', mustMatch: /Find|Tutor|Match|Subject/i, landmark: 'h1' },
  166 |   { path: '/become-a-tutor/', name: 'Become a Tutor', mustMatch: /Become|Tutor|Earn|Teach/i, landmark: 'h1' },
  167 |   { path: '/pricing/', name: 'Pricing', mustMatch: /Price|Package|R\s*\d|Plan|Rate/i, landmark: 'h1' },
  168 |   { path: '/login/', name: 'Login', mustMatch: /Sign in|Login|Continue as|Parent|Tutor/i },
  169 |   { path: '/register/', name: 'Register', mustMatch: /Register|Parent|Student|Create/i },
  170 |   { path: '/contact/', name: 'Contact', mustMatch: /Contact|Support|Message|Help/i },
  171 |   { path: '/guarantee/', name: 'Guarantee', mustMatch: /Guarantee|Lesson|Risk|First/i, landmark: 'h1' },
  172 | ];
  173 | 
  174 | const IGNORED_CONSOLE =
  175 |   /Download the React DevTools|third-party|facebook|googletagmanager|clarity\.ms|hotjar|Failed to load resource: net::ERR_|ResizeObserver loop|Non-Error promise rejection/i;
  176 | 
  177 | /**
  178 |  * Attach a console / pageerror collector. Call before navigation.
  179 |  * Returns a getter plus a dispose() that removes listeners (avoids stacking in loops).
  180 |  */
  181 | export function attachConsoleGuard(page: Page): {
  182 |   getFatals: () => string[];
  183 |   dispose: () => void;
  184 | } {
  185 |   const fatals: string[] = [];
  186 |   const onPageError = (err: Error) => {
  187 |     const msg = String(err?.message || err);
  188 |     if (!IGNORED_CONSOLE.test(msg)) fatals.push(`pageerror: ${msg}`);
  189 |   };
  190 |   const onConsole = (msg: { type: () => string; text: () => string }) => {
  191 |     if (msg.type() !== 'error') return;
  192 |     const text = msg.text();
  193 |     if (IGNORED_CONSOLE.test(text)) return;
  194 |     if (/favicon|fonts\.googleapis|cdn\.|404/i.test(text)) return;
  195 |     fatals.push(`console.error: ${text}`);
  196 |   };
  197 |   page.on('pageerror', onPageError);
  198 |   page.on('console', onConsole);
  199 |   return {
  200 |     getFatals: () => [...fatals],
  201 |     dispose: () => {
  202 |       page.off('pageerror', onPageError);
  203 |       page.off('console', onConsole);
  204 |     },
  205 |   };
  206 | }
  207 | 
  208 | /** Soft assert: no unexpected pageerrors (console noise alone does not fail). */
  209 | export function expectNoPageErrors(fatals: string[]) {
  210 |   const pageErrors = fatals.filter((f) => f.startsWith('pageerror:'));
  211 |   expect(pageErrors, pageErrors.join('\n')).toEqual([]);
  212 | }
  213 | 
  214 | export async function expectPageHealthy(
  215 |   page: Page,
  216 |   path: string,
  217 |   opts: { mustMatch: RegExp; landmark?: string; name?: string }
  218 | ) {
  219 |   const guard = attachConsoleGuard(page);
  220 |   try {
  221 |     let res = null as Awaited<ReturnType<Page['goto']>>;
  222 |     try {
  223 |       res = await page.goto(path, { waitUntil: 'domcontentloaded', timeout: 90_000 });
  224 |     } catch (first) {
  225 |       // Slow WP / aborted navigations — one retry on a fresh load.
  226 |       await page.waitForTimeout(1_000);
  227 |       res = await page.goto(path, { waitUntil: 'domcontentloaded', timeout: 90_000 });
  228 |       if (!res) throw first;
  229 |     }
  230 |     expect(res, `${opts.name || path} should respond`).toBeTruthy();
  231 |     const status = res!.status();
  232 |     expect(status, `${opts.name || path} HTTP status`).toBeLessThan(500);
  233 |     await dismissCookieOrOverlays(page);
  234 | 
  235 |     const bodyText = (await page.locator('body').innerText().catch(() => '')) || '';
  236 |     if (status === 404 || /page can.?t be found|not found/i.test(bodyText.slice(0, 400))) {
  237 |       throw new Error(`${opts.name || path} returned not found`);
  238 |     }
  239 | 
  240 |     if (opts.landmark) {
  241 |       await expect(page.locator(opts.landmark).first()).toBeVisible({ timeout: 30_000 });
  242 |     }
  243 |     await expect(page.locator('body')).toContainText(opts.mustMatch, { timeout: 30_000 });
  244 |     expectNoPageErrors(guard.getFatals());
  245 |   } finally {
  246 |     guard.dispose();
  247 |   }
  248 | }
  249 | 
```