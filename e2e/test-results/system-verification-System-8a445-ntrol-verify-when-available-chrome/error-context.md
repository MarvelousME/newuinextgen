# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: system-verification.spec.ts >> System verification — admin >> 10 demo control verify when available
- Location: workflows\system-verification.spec.ts:216:18

# Error details

```
TimeoutError: locator.click: Timeout 30000ms exceeded.
Call log:
  - waiting for getByTestId('ngc-demo-verify-btn')
    - locator resolved to <button type="submit" class="button " data-testid="ngc-demo-verify-btn">Verify</button>
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
  137 |       .first();
  138 |     if (await parentRole.isVisible().catch(() => false)) {
  139 |       await parentRole.click();
  140 |       await page.waitForTimeout(500);
  141 |     } else {
  142 |       await gotoReady(page, '/register/?role=parent');
  143 |     }
  144 | 
  145 |     const hasRegister =
  146 |       (await primaryNgForm(page).isVisible().catch(() => false)) ||
  147 |       (await page.locator('form.bi-ngc-form, form.ngc-form, form#ngc-registerform, form').filter({ has: page.locator('input[type="email"], input[name*="email"]') }).first().isVisible().catch(() => false)) ||
  148 |       (await page.locator('body').getByText(/I'm a Parent|Parent|Register/i).first().isVisible().catch(() => false));
  149 |     expect(hasRegister, 'register should expose role chooser or intake form').toBeTruthy();
  150 |     record('forms.register', 'pass');
  151 | 
  152 |     await gotoReady(page, '/find-a-tutor/');
  153 |     const hasFind =
  154 |       (await primaryNgForm(page).isVisible().catch(() => false)) ||
  155 |       (await page.locator('form.bi-ngc-form, form.ngc-form, form').first().isVisible().catch(() => false));
  156 |     expect(hasFind, 'find-a-tutor should expose a form').toBeTruthy();
  157 |     await expect(page.locator('body')).toContainText(/Find|Tutor|Match|Subject/i);
  158 |     record('forms.find_a_tutor', 'pass');
  159 |   });
  160 | 
  161 |   test('07 tutor archive / profile browse', async ({ page }) => {
  162 |     await gotoReady(page, '/tutors/');
  163 |     const status = await page.evaluate(() => document.body?.innerText?.slice(0, 200) || '');
  164 |     if (
  165 |       /not found|404|page can.?t be found/i.test(status) &&
  166 |       !(await page.locator('article, .tutor-card, .ngt-tutor').count())
  167 |     ) {
  168 |       await gotoReady(page, '/find-a-tutor/');
  169 |       await expect(page.locator('body')).toContainText(/Tutor|Match|Subject/i);
  170 |       record('tutors.archive', 'pass', 'fallback find-a-tutor');
  171 |       return;
  172 |     }
  173 |     await expect(page.locator('body')).toContainText(/Tutor|Educator|Subject|Match/i, { timeout: 30_000 });
  174 |     record('tutors.archive', 'pass');
  175 |   });
  176 | 
  177 |   test('08 axe serious/critical scan on home (record only)', async ({ page }) => {
  178 |     await gotoReady(page, '/');
  179 |     const axe = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa']).analyze();
  180 |     const blocking = axe.violations.filter((v) => ['serious', 'critical'].includes(v.impact || ''));
  181 |     record('a11y.home_axe', 'pass', blocking.map((v) => `${v.impact}:${v.id}`).join(', ') || 'none');
  182 |     expect(axe.violations).toBeTruthy();
  183 |   });
  184 | });
  185 | 
  186 | test.describe('System verification — admin', () => {
  187 |   test.describe.configure({ mode: 'serial' });
  188 |   test.setTimeout(180_000);
  189 | 
  190 |   test.afterAll(async () => {
  191 |     await writeEvidence();
  192 |   });
  193 | 
  194 |   test('09 wp-admin login and Companion surfaces', async ({ page }) => {
  195 |     await wpLogin(page);
  196 |     await expect(page.locator('#wpadminbar').first()).toBeVisible({ timeout: 30_000 });
  197 |     record('admin.login', 'pass');
  198 | 
  199 |     await page.goto('/wp-admin/admin.php?page=ngc-settings', {
  200 |       waitUntil: 'domcontentloaded',
  201 |       timeout: 120_000,
  202 |     });
  203 |     const bodyText = await page.locator('body').innerText();
  204 |     if (/ngc-settings|Companion|NextGen|Settings|You need a higher level/i.test(bodyText)) {
  205 |       record('admin.companion_settings', 'pass');
  206 |     } else {
  207 |       await page.goto('/wp-admin/plugins.php', { waitUntil: 'domcontentloaded' });
  208 |       await expect(page.locator('body')).toContainText(/NextGen|Companion|Plugin/i);
  209 |       record('admin.companion_settings', 'pass', 'via plugins.php');
  210 |     }
  211 |   });
  212 | 
  213 |   test('10 demo control verify when available', async ({ page }) => {
  214 |     if (!(await page.locator('#wpadminbar').isVisible().catch(() => false))) {
  215 |       await wpLogin(page);
  216 |     }
  217 | 
  218 |     try {
  219 |       await openDemoControlCentre(page);
  220 |     } catch {
  221 |       record('demo.control', 'skip', 'Demo Control Centre not available');
  222 |       test.skip(true, 'Demo Control Centre unavailable on this stack');
  223 |       return;
  224 |     }
  225 | 
  226 |     await expect(page.getByRole('heading', { name: /Demo Control Centre/i })).toBeVisible({
  227 |       timeout: 30_000,
  228 |     });
  229 |     record('demo.control', 'pass');
  230 | 
  231 |     const verifyBtn = page.getByTestId('ngc-demo-verify-btn');
  232 |     if (await verifyBtn.isVisible().catch(() => false)) {
  233 |       await verifyBtn.click({ force: true });
  234 |       await page.waitForLoadState('domcontentloaded').catch(() => null);
  235 |       const verify = page.getByTestId('ngc-demo-verify');
  236 |       if (await verify.isVisible().catch(() => false)) {
> 237 |         const text = (await verify.textContent())?.trim() || '';
      |                       ^ TimeoutError: locator.click: Timeout 30000ms exceeded.
  238 |         expect(text).toMatch(/PASS|FAIL/i);
  239 |         record('demo.verify', 'pass', text);
  240 |       } else {
  241 |         record('demo.verify', 'skip', 'verify status node missing');
  242 |       }
  243 |     } else {
  244 |       record('demo.verify', 'skip', 'verify button missing');
  245 |     }
  246 |   });
  247 | 
  248 |   test('11 logout cleanup', async ({ page }) => {
  249 |     await wpLogout(page);
  250 |     await gotoReady(page, '/login/');
  251 |     await expect(page.locator('#bi-login-role-parent').first()).toBeVisible({ timeout: 20_000 });
  252 |     record('auth.logout', 'pass');
  253 |   });
  254 | });
  255 | 
```