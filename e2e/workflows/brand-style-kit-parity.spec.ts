/**
 * Brand Style Kit — token layers, button contract, responsive sanity, admin preview.
 */
import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
import { gotoReady, wpLogin, PUBLIC_SYSTEM_PAGES, expectPageHealthy } from '../helpers';

const BASE = process.env.BASE_URL || 'http://localhost:8890';
const EVIDENCE = path.join(__dirname, '..', '..', 'delivery', 'evidence', 'brand-style-kit');

const REQUIRED_STYLES = [
  'brand-semantic.css',
  'accessibility.css',
  'integrations/elementor.css',
  'integrations/gutenberg.css',
  'integrations/wpbakery.css',
  'components/tutor-card.css',
];

/** Skip spiral preloader so layout metrics are stable. */
async function skipHomeIntro(page: import('@playwright/test').Page) {
  await page.addInitScript(() => {
    try {
      sessionStorage.setItem('ngt_entered', '1');
    } catch {
      /* ignore */
    }
  });
}

async function stylesheetUrls(page: import('@playwright/test').Page): Promise<string[]> {
  return page.evaluate(() =>
    Array.from(document.querySelectorAll('link[rel="stylesheet"]')).map((l) => (l as HTMLLinkElement).href)
  );
}

async function assertNoHorizontalOverflow(page: import('@playwright/test').Page, label: string) {
  const overflow = await page.evaluate(() => {
    document.querySelectorAll('#preloader, #spiral-canvas, .preloader').forEach((el) => el.remove());
    document.body.classList.remove('preloading');
    const main = document.querySelector('main, .site-main, #content, .bi-page') as HTMLElement | null;
    const target = main || document.body;
    return target.scrollWidth - target.clientWidth;
  });
  expect(overflow, `${label} horizontal overflow`).toBeLessThanOrEqual(8);
}

test.describe('Brand Style Kit parity', () => {
  test.beforeAll(() => {
    fs.mkdirSync(EVIDENCE, { recursive: true });
  });

  test('homepage loads canonical token + integration CSS', async ({ page }) => {
    await skipHomeIntro(page);
    await gotoReady(page, BASE + '/');
    await page.waitForTimeout(1500);
    const urls = await stylesheetUrls(page);
    for (const fragment of REQUIRED_STYLES) {
      expect(
        urls.some((u) => u.includes(fragment.replace(/\//g, '%2F')) || u.includes(fragment)),
        `missing stylesheet fragment: ${fragment}`
      ).toBeTruthy();
    }

    const primary = await page.evaluate(() => {
      const html = document.documentElement;
      return getComputedStyle(html).getPropertyValue('--ngt-color-brand-primary').trim();
    });
    expect(primary.length, 'brand primary token on html').toBeGreaterThan(0);

    await page.screenshot({ path: path.join(EVIDENCE, '01-home-tokens.png'), fullPage: false });
    await assertNoHorizontalOverflow(page, 'home');
  });

  test('primary buttons render with brand tokens (no legacy ngbi global override)', async ({ page }) => {
    await gotoReady(page, BASE + '/find-a-tutor/');
    const btn = page.locator('.ngt-btn--primary, a.ngt-btn--primary, button.ngt-btn--primary').first();
    if ((await btn.count()) === 0) {
      test.skip(true, 'No .ngt-btn--primary on find-a-tutor');
    }
    await expect(btn).toBeVisible({ timeout: 15_000 });

    const styles = await btn.evaluate((el) => {
      const cs = getComputedStyle(el);
      return {
        bgImage: cs.backgroundImage,
        bgColor: cs.backgroundColor,
      };
    });
    // Legacy ngbi overlay used blue/violet gradient; kinetic theme may use gold→green by design.
    expect(styles.bgImage, 'must not use retired ngbi blue gradient').not.toMatch(/2563eb|7c3aed/i);
    expect(
      styles.bgImage !== 'none' || styles.bgColor !== 'rgba(0, 0, 0, 0)',
      'primary button should have visible fill'
    ).toBeTruthy();

    await page.screenshot({ path: path.join(EVIDENCE, '02-find-a-tutor-button.png'), fullPage: false });
  });

  test('tutor card uses canonical BEM classes', async ({ page }) => {
    await gotoReady(page, BASE + '/');
    const card = page.locator('.ngt-card--tutor, .ngt-tutor-card.ngt-card').first();
    if ((await card.count()) === 0) {
      await gotoReady(page, BASE + '/find-a-tutor/');
    }
    const target = page.locator('.ngt-card--tutor, .ngt-tutor-card').first();
    if ((await target.count()) === 0) {
      test.skip(true, 'No tutor card on page');
    }
    await expect(target).toBeVisible({ timeout: 15_000 });
    const cta = target.locator('.ngt-btn--primary').first();
    if ((await cta.count()) > 0) {
      await expect(cta).toBeVisible();
    }
    const badLegacy = await page.locator('.ngt-btn-primary:not(.ngt-btn--primary)').count();
    expect(badLegacy, 'legacy ngt-btn-primary without BEM modifier').toBe(0);
  });

  test('key marketing pages healthy without overflow', async ({ page }) => {
    const subset = PUBLIC_SYSTEM_PAGES.filter((p) =>
      ['/find-a-tutor/', '/pricing/', '/about/'].includes(p.path)
    );
    for (const pg of subset) {
      await expectPageHealthy(page, BASE + pg.path, {
        name: pg.name,
        mustMatch: pg.mustMatch,
      });
      await assertNoHorizontalOverflow(page, pg.name);
    }
  });

  test('Brand Style Kit admin preview renders', async ({ page }) => {
    await wpLogin(page);
    await page.goto(BASE + '/wp-admin/themes.php?page=bi-brand-style-kit', {
      waitUntil: 'domcontentloaded',
      timeout: 120_000,
    });
    await expect(page.getByRole('heading', { name: /BeyondInfinity Brand Style Kit/i })).toBeVisible({
      timeout: 30_000,
    });
    await expect(page.getByRole('heading', { name: /Contrast audit/i })).toBeVisible();
    await expect(page.locator('.ngt-btn--primary').first()).toBeVisible();
    await page.screenshot({ path: path.join(EVIDENCE, '03-admin-brand-kit.png'), fullPage: false });
  });
});
