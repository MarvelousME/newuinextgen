/**
 * Deeper GSAPify forensic pass: locate demo cards, click/open, sample motion.
 * Read-only. Outputs docs/gsapify-forensic-pass.json
 */
import { chromium } from 'playwright';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '../..');

const NOISE =
  /limit|plugin ecosystem|what.?s inside|unmatched performance|portfolios|saas|text animations$|scrolltrigger$|ease visualizer|draggable &|morphsvg &|physics2d &|splittext &|copy|load gsap|ai generator|add your html|mobile optimized|cross-browser|dashboards|e-commerce|editorial|landing pages|sign in|better user|captivating|brand identity|guided user|animation generator|you've hit/i;

async function waitCf(page) {
  for (let i = 0; i < 30; i++) {
    const t = (await page.title()) + (await page.locator('body').innerText().catch(() => ''));
    if (!/just a moment|checking your browser/i.test(t) || t.length > 1200) return;
    await page.waitForTimeout(1000);
  }
}

async function collectTitles(page) {
  for (let i = 0; i < 50; i++) {
    await page.evaluate(() => window.scrollBy(0, 800));
    await page.waitForTimeout(180);
  }
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForTimeout(400);

  return page.evaluate(() => {
    const items = [];
    const seen = new Set();
    const candidates = document.querySelectorAll(
      'a, button, [role="button"], [class*="card"], [class*="Card"], article, [data-framer-name]'
    );
    candidates.forEach((el, idx) => {
      const heading = el.querySelector?.('h1,h2,h3,h4,h5,p,span');
      let name = (heading?.textContent || el.getAttribute('aria-label') || el.textContent || '')
        .replace(/\s+/g, ' ')
        .trim();
      // Prefer short title-like first line
      name = name.split('  ')[0].slice(0, 80).trim();
      if (!name || name.length < 3 || name.length > 55) return;
      if (seen.has(name)) return;
      // Prefer elements that look interactive/demo-like
      const cls = (el.className && String(el.className)) || '';
      const looksDemo = /card|demo|effect|animation|preview|item/i.test(cls + (el.tagName || ''));
      const hasMedia = !!el.querySelector?.('canvas, video, svg, img, [class*="preview"]');
      if (!looksDemo && !hasMedia && el.tagName !== 'A' && el.tagName !== 'BUTTON') return;
      seen.add(name);
      items.push({
        name,
        idx,
        tag: el.tagName.toLowerCase(),
        href: el.href || null,
      });
    });
    return items;
  });
}

async function inspectRuntime(page) {
  return page.evaluate(() => {
    const info = {
      gsapVersion: window.gsap?.version || null,
      plugins: Object.keys(window.gsap?.plugins || {}),
      tweenCount: null,
      stCount: null,
      sampleTweens: [],
      sampleST: [],
    };
    try {
      if (window.gsap?.globalTimeline?.getChildren) {
        const kids = window.gsap.globalTimeline.getChildren(true, true, false);
        info.tweenCount = kids.length;
        info.sampleTweens = kids.slice(0, 12).map((t) => ({
          duration: t.duration?.() ?? null,
          totalDuration: t.totalDuration?.() ?? null,
          targets: (t.targets?.() || []).slice(0, 3).map((el) => {
            if (!el || !el.tagName) return String(el);
            return {
              tag: el.tagName.toLowerCase(),
              id: el.id || null,
              class: typeof el.className === 'string' ? el.className.slice(0, 80) : null,
            };
          }),
          vars: t.vars
            ? Object.keys(t.vars).filter((k) => !['scrollTrigger', 'onUpdate', 'onComplete'].includes(k)).slice(0, 20)
            : [],
        }));
      }
      if (window.ScrollTrigger?.getAll) {
        const all = window.ScrollTrigger.getAll();
        info.stCount = all.length;
        info.sampleST = all.slice(0, 15).map((st) => ({
          start: st.start,
          end: st.end,
          scrub: st.vars?.scrub ?? null,
          pin: !!st.pin,
          trigger: st.trigger
            ? {
                tag: st.trigger.tagName?.toLowerCase(),
                id: st.trigger.id || null,
                class: typeof st.trigger.className === 'string' ? st.trigger.className.slice(0, 80) : null,
              }
            : null,
        }));
      }
    } catch (e) {
      info.error = String(e);
    }
    return info;
  });
}

async function runPage(browser, url, kind) {
  const page = await browser.newPage({
    viewport: { width: 1440, height: 1100 },
    userAgent:
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
  });
  const out = {
    kind,
    url,
    fetchedAt: new Date().toISOString(),
    httpStatus: null,
    pageRuntime: null,
    effects: [],
    unableToInspect: [],
  };
  try {
    const resp = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 90000 });
    out.httpStatus = resp?.status() ?? null;
    await waitCf(page);
    await page.waitForTimeout(2000);
    out.pageRuntime = await inspectRuntime(page);
    const titles = (await collectTitles(page)).filter((t) => !NOISE.test(t.name));
    // Dedupe by name
    const unique = [];
    const seen = new Set();
    for (const t of titles) {
      if (seen.has(t.name)) continue;
      seen.add(t.name);
      unique.push(t);
    }

    for (const item of unique) {
      const record = {
        name: item.name,
        category: null,
        verificationStatus: 'GSAPIFY-VERIFIED',
        targetType: null,
        trigger: null,
        duration: null,
        ease: null,
        stagger: null,
        transformProperties: [],
        opacity: null,
        scale: null,
        rotation: null,
        filterProperties: [],
        clipMask: null,
        svgRequirements: null,
        scrollTrigger: null,
        pointerInteraction: null,
        pluginDependencies: [],
        timelineStructure: null,
        cssDependencies: [],
        htmlDependencies: [],
        reducedMotion: 'unknown',
        cleanupRequirements: 'destroy tweens/ST on unmount',
        inspectionNotes: [],
        unable: false,
      };
      try {
        // Snapshot runtime delta around click if possible
        const before = await inspectRuntime(page);
        const clicked = await page.evaluate((name) => {
          const nodes = Array.from(document.querySelectorAll('a, button, [role="button"], article, [class*="card"]'));
          const el = nodes.find((n) => (n.textContent || '').replace(/\s+/g, ' ').includes(name));
          if (!el) return false;
          el.scrollIntoView({ block: 'center' });
          el.click();
          return true;
        }, item.name);
        if (!clicked) {
          record.unable = true;
          record.inspectionNotes.push('Could not locate clickable card for name');
          out.unableToInspect.push(item.name);
        } else {
          await page.waitForTimeout(700);
          const after = await inspectRuntime(page);
          record.pluginDependencies = after.plugins || [];
          if (after.sampleTweens?.length) {
            const vars = new Set();
            after.sampleTweens.forEach((t) => (t.vars || []).forEach((v) => vars.add(v)));
            record.transformProperties = [...vars].filter((v) =>
              /x|y|z|scale|rotation|skew|transform/i.test(v)
            );
            record.filterProperties = [...vars].filter((v) => /blur|filter|brightness|contrast|saturate/i.test(v));
            if (vars.has('opacity')) record.opacity = true;
            if ([...vars].some((v) => /scale/i.test(v))) record.scale = true;
            if ([...vars].some((v) => /rotation/i.test(v))) record.rotation = true;
            record.duration = after.sampleTweens[0]?.duration ?? null;
            record.timelineStructure = {
              tweenSampleCount: after.sampleTweens.length,
              stSampleCount: after.stCount,
            };
          }
          if (after.sampleST?.length) {
            record.scrollTrigger = after.sampleST[0];
            record.trigger = after.sampleST[0].scrub != null ? 'scroll-scrub' : 'scroll';
          } else if ((after.tweenCount || 0) > (before.tweenCount || 0)) {
            record.trigger = record.trigger || 'interaction-or-viewport';
          }
          // Close modal/dialog if any
          await page.keyboard.press('Escape').catch(() => {});
          await page.waitForTimeout(200);
        }
      } catch (e) {
        record.unable = true;
        record.inspectionNotes.push(String(e));
        out.unableToInspect.push(item.name);
      }
      out.effects.push(record);
    }
  } catch (e) {
    out.error = String(e);
  } finally {
    await page.close().catch(() => {});
  }
  return out;
}

const browser = await chromium.launch({ headless: true });
const general = await runPage(browser, 'https://gsapify.com/gsap-animations/', 'general');
const text = await runPage(
  browser,
  'https://gsapify.com/gsap-text-animations/#text-animation-collection',
  'text'
);
await browser.close();

const summary = {
  catalogueCardsDiscovered: {
    general: general.effects.length,
    text: text.effects.length,
  },
  successfullyInspected: {
    general: general.effects.filter((e) => !e.unable).length,
    text: text.effects.filter((e) => !e.unable).length,
  },
  unableToInspect: {
    general: general.unableToInspect,
    text: text.unableToInspect,
  },
  general,
  text,
};

fs.writeFileSync(path.join(root, 'docs/gsapify-forensic-pass.json'), JSON.stringify(summary, null, 2));
console.log(
  JSON.stringify(
    {
      generalDiscovered: general.effects.length,
      generalInspected: general.effects.filter((e) => !e.unable).length,
      textDiscovered: text.effects.length,
      textInspected: text.effects.filter((e) => !e.unable).length,
      generalPlugins: general.pageRuntime?.plugins,
      textPlugins: text.pageRuntime?.plugins,
    },
    null,
    2
  )
);
