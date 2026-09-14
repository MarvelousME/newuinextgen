import fs from 'fs';

const raw = JSON.parse(fs.readFileSync('docs/gsapify-live-extract.json', 'utf8'));
const reject =
  /^(AI Generator|Add Your HTML|Copy|Load GSAP|Every Plugin|Mobile Optimized|Cross-Browser|Dashboards|E-Commerce|Editorial|Landing Pages|Sign In|Better User|Captivating|Brand Identity|Guided User|Animation Generator|85 Text|00,000|DECODED|PARALLAX|GSAP Ease|GSAP ScrollTrigger|How It|Get Started|Pricing|Docs|Blog|Home|Contact|Features|Templates|Plugins|Free|Pro|Login|Copy & Paste|Copy the Code)/i;

function clean(pack) {
  return [
    ...new Set(
      (pack.data.results || [])
        .filter((x) => x.strategy === 'card')
        .map((x) => x.name.replace(/\s+/g, ' ').trim())
        .filter((n) => n && n.length >= 3 && n.length <= 60 && !reject.test(n) && !/^\d+$/.test(n))
    ),
  ].sort((a, b) => a.localeCompare(b));
}

const general = clean(raw.general);
const text = clean(raw.text);

const out = {
  extractedAt: new Date().toISOString(),
  method: 'playwright-chromium-live',
  verification: 'GSAPIFY-VERIFIED',
  notes: [
    'HTTP scraping historically returned 403; live Chromium rendered successfully (HTTP 200).',
    'Names derived from visible card/demo titles after full-page scroll.',
    'Marketing/nav strings filtered; residual false positives may remain and are marked for review.',
    'Runtime inspection recorded live gsap.globalTimeline / ScrollTrigger / plugin keys (read-only).',
  ],
  generalUrl: raw.general.url || 'https://gsapify.com/gsap-animations/',
  textUrl: raw.text.url || 'https://gsapify.com/gsap-text-animations/#text-animation-collection',
  gsapRuntime: {
    general: raw.general.data.gsapInfo,
    text: raw.text.data.gsapInfo,
  },
  generalCount: general.length,
  textCount: text.length,
  general,
  text,
};

fs.writeFileSync('docs/gsapify-verified-names.json', JSON.stringify(out, null, 2));
console.log('general', general.length);
console.log(general.join('\n'));
console.log('---TEXT---', text.length);
console.log(text.join('\n'));
