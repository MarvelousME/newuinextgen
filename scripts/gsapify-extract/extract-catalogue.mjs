/**
 * Live browser extraction of GSAPify catalogues (Cloudflare-resistant via real Chromium).
 * Read-only inspection — does not modify the remote site.
 */
import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.resolve(__dirname, '../../docs');

async function waitForCloudflare(page, timeoutMs = 45000) {
  const start = Date.now();
  while (Date.now() - start < timeoutMs) {
    const title = await page.title().catch(() => '');
    const body = await page.locator('body').innerText().catch(() => '');
    if (/just a moment|checking your browser|cloudflare/i.test(title + body) && body.length < 800) {
      await page.waitForTimeout(1500);
      continue;
    }
    return true;
  }
  return false;
}

function unique(arr) {
  const seen = new Set();
  return arr.filter((x) => {
    const k = JSON.stringify(x);
    if (seen.has(k)) return false;
    seen.add(k);
    return true;
  });
}

async function extractCards(page, pageKind) {
  // Expand lazy content by scrolling
  for (let i = 0; i < 40; i++) {
    await page.evaluate(() => window.scrollBy(0, Math.max(600, window.innerHeight * 0.85)));
    await page.waitForTimeout(250);
  }
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForTimeout(500);

  const payload = await page.evaluate((kind) => {
    const results = [];
    const push = (name, category, el, extra = {}) => {
      const n = (name || '').replace(/\s+/g, ' ').trim();
      if (!n || n.length < 2 || n.length > 120) return;
      if (/^(home|docs|blog|pricing|login|sign up|get started|contact)$/i.test(n)) return;
      results.push({
        name: n,
        category: (category || '').trim() || null,
        href: el?.closest?.('a')?.href || el?.href || null,
        textSnippet: (el?.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 240),
        tag: el?.tagName?.toLowerCase?.() || null,
        classes: el?.className && typeof el.className === 'string' ? el.className.slice(0, 160) : null,
        ...extra,
      });
    };

    // Strategy A: common card/grid patterns
    const cardSelectors = [
      '[class*="animation"]',
      '[class*="effect"]',
      '[class*="card"]',
      '[class*="demo"]',
      '[class*="example"]',
      '[data-animation]',
      '[data-effect]',
      'article',
      '.w-dyn-item',
      '[role="listitem"]',
    ];
    for (const sel of cardSelectors) {
      document.querySelectorAll(sel).forEach((el) => {
        const heading = el.querySelector('h1,h2,h3,h4,h5,.title,[class*="title"],[class*="name"]');
        const name = heading?.textContent || el.getAttribute('data-animation') || el.getAttribute('aria-label');
        const cat =
          el.closest('section')?.querySelector('h2,h3')?.textContent ||
          el.getAttribute('data-category') ||
          null;
        if (name) push(name, cat, el, { strategy: 'card' });
      });
    }

    // Strategy B: collection headings near demos
    document.querySelectorAll('h2,h3,h4').forEach((h) => {
      const t = (h.textContent || '').trim();
      if (/animation|effect|collection|entrance|scroll|text|hover|stagger/i.test(t) && t.length < 80) {
        push(t, 'section-heading', h, { strategy: 'heading' });
      }
    });

    // Strategy C: links that look like effect deep-links
    document.querySelectorAll('a[href*="animation"], a[href*="effect"], a[href*="#"]').forEach((a) => {
      const t = (a.textContent || '').trim();
      if (t && t.length > 2 && t.length < 80) push(t, 'link', a, { strategy: 'link' });
    });

    // Strategy D: Framer / CMS item titles
    document.querySelectorAll('[data-framer-name], [data-framer-component-type]').forEach((el) => {
      const name = el.getAttribute('data-framer-name');
      if (name && !/Frame|Stack|Stack$|Desktop|Mobile|Container/i.test(name)) {
        push(name, 'framer', el, { strategy: 'framer-name' });
      }
    });

    // Runtime GSAP inspection (read-only)
    let gsapInfo = null;
    try {
      if (window.gsap) {
        const tl = window.gsap.globalTimeline;
        gsapInfo = {
          version: window.gsap.version || null,
          tweens: typeof tl?.getChildren === 'function' ? tl.getChildren(true, true, false).length : null,
          scrollTriggers:
            window.ScrollTrigger && typeof window.ScrollTrigger.getAll === 'function'
              ? window.ScrollTrigger.getAll().length
              : null,
          plugins: Object.keys(window.gsap.plugins || {}),
        };
      }
    } catch (e) {
      gsapInfo = { error: String(e) };
    }

    const title = document.title;
    const h1 = document.querySelector('h1')?.textContent?.trim() || null;
    const bodyLen = (document.body?.innerText || '').length;

    return {
      kind,
      url: location.href,
      title,
      h1,
      bodyLen,
      gsapInfo,
      rawCount: results.length,
      results,
    };
  }, pageKind);

  payload.results = unique(payload.results);
  return payload;
}

async function extractPage(browser, url, kind) {
  const page = await browser.newPage({
    userAgent:
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
    viewport: { width: 1440, height: 1100 },
  });
  const report = {
    kind,
    url,
    fetchedAt: new Date().toISOString(),
    httpStatus: null,
    cloudflareCleared: false,
    error: null,
    data: null,
  };
  try {
    const resp = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 90000 });
    report.httpStatus = resp?.status() ?? null;
    report.cloudflareCleared = await waitForCloudflare(page);
    await page.waitForTimeout(2500);
    report.data = await extractCards(page, kind);
    // Screenshot evidence
    const shot = path.join(outDir, `gsapify-${kind}-screenshot.png`);
    await page.screenshot({ path: shot, fullPage: false });
    report.screenshot = path.relative(path.resolve(__dirname, '../..'), shot).replace(/\\/g, '/');
  } catch (e) {
    report.error = String(e);
  } finally {
    await page.close().catch(() => {});
  }
  return report;
}

async function main() {
  const browser = await chromium.launch({ headless: true });
  const general = await extractPage(browser, 'https://gsapify.com/gsap-animations/', 'general');
  const text = await extractPage(
    browser,
    'https://gsapify.com/gsap-text-animations/#text-animation-collection',
    'text'
  );
  await browser.close();

  const rawPath = path.join(outDir, 'gsapify-live-extract.json');
  fs.writeFileSync(rawPath, JSON.stringify({ general, text }, null, 2), 'utf8');
  console.log(JSON.stringify({
    ok: true,
    rawPath: path.relative(path.resolve(__dirname, '../..'), rawPath).replace(/\\/g, '/'),
    generalStatus: general.httpStatus,
    generalCount: general.data?.results?.length ?? 0,
    generalError: general.error,
    textStatus: text.httpStatus,
    textCount: text.data?.results?.length ?? 0,
    textError: text.error,
    generalTitle: general.data?.title,
    textTitle: text.data?.title,
  }, null, 2));
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
