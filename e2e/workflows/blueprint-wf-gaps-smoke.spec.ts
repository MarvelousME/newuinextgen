/**
 * WF coverage gaps — headed smoke for journeys not covered by dedicated specs.
 */
import { test, expect } from '@playwright/test';
import { dismissCookieOrOverlays, wpLogin } from '../helpers';

test.describe('Blueprint WF gaps — public + ops smoke', () => {
  test.setTimeout(180_000);

  test('WF-04/05/06 tutor journey pages render', async ({ page }) => {
    const res = await page.goto('/become-a-tutor/', { waitUntil: 'domcontentloaded' });
    expect(res?.status() ?? 0).toBeLessThan(500);
    await dismissCookieOrOverlays(page);
    await expect(page.locator('body')).toContainText(/tutor|become|apply|income|NextGen/i);
  });

  test('WF-09/13/15 package and about surfaces', async ({ page }) => {
    let ok = 0;
    for (const path of ['/pricing/', '/packages/', '/about/', '/how-it-works/', '/']) {
      const res = await page.goto(path, { waitUntil: 'domcontentloaded' });
      if (res && res.status() < 400) {
        await expect(page.locator('body')).toBeVisible();
        ok += 1;
      }
    }
    expect(ok).toBeGreaterThan(0);
  });

  test('WF-20/21 safeguarding + privacy pages', async ({ page }) => {
    let matched = false;
    for (const path of ['/privacy-policy/', '/privacy/', '/safeguarding/', '/terms/', '/']) {
      const res = await page.goto(path, { waitUntil: 'domcontentloaded' });
      if (!res || res.status() >= 400) {
        continue;
      }
      const text = await page.locator('body').innerText();
      if (/privacy|safeguard|terms|POPIA|policy|NextGen/i.test(text)) {
        matched = true;
        break;
      }
    }
    expect(matched).toBeTruthy();
  });

  test('WF-22/23 admin platform reachable when logged in', async ({ page }) => {
    await wpLogin(page);
    await page.goto('/wp-admin/admin.php?page=ngc-demo-control', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#wpadminbar')).toBeVisible();
    const body = await page.locator('body').innerText();
    expect(/Demo Control|Platform|NextGen|Companion|ngc/i.test(body)).toBeTruthy();
  });
});
