#!/usr/bin/env node
/**
 * Scan HTML research artifacts + old theme → UI library inventories.
 * Output: docs/ui-library/inventories/*.json + ANALYSIS-SUMMARY.json
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'docs', 'ui-library', 'inventories');

const HTML_SOURCES = [
  {
    file: 'C:/Users/marvi/Desktop/nextgentutors-full-website/Implementation/Research-Artifacts/nextgentutors_final.html',
    label: 'nextgentutors_final',
  },
  {
    file: 'C:/Users/marvi/Desktop/nextgentutors-full-website/Implementation/Research-Artifacts/nextgentutors_complete (1).html',
    label: 'nextgentutors_complete',
  },
  {
    file: 'C:/Users/marvi/Desktop/nextgentutors-full-website/Implementation/Research-Artifacts/nextgen_tutors_redesign.html',
    label: 'nextgen_tutors_redesign',
  },
  {
    file: 'C:/Users/marvi/Desktop/nextgentutors-full-website/Implementation/Research-Artifacts/nextgen_tutors_full_redesign_v2.html',
    label: 'nextgen_tutors_full_redesign_v2',
  },
];

const OLD_THEME = 'C:/Users/marvi/Desktop/WP-THEME-NEXT-GEN/nextgen-tutors-theme-original-top-ui';

const PAGE_KEYWORDS = [
  'home', 'find', 'tutor', 'marketplace', 'pricing', 'about', 'contact', 'blog',
  'dashboard', 'subject', 'math', 'science', 'accounting', 'english', 'faq',
  'privacy', 'terms', 'cookie', 'safety', 'vetting', 'calendar', 'profile',
];

function readSafe(p) {
  try {
    return fs.readFileSync(p, 'utf8');
  } catch {
    return null;
  }
}

function extractTitle(html) {
  const m = html.match(/<title[^>]*>([^<]+)<\/title>/i);
  return m ? m[1].trim() : '';
}

function extractCssVars(html) {
  const vars = {};
  const rootMatch = html.match(/:root\s*\{([^}]+)\}/s);
  if (!rootMatch) return vars;
  const re = /--([a-zA-Z0-9_-]+)\s*:\s*([^;]+);/g;
  let m;
  while ((m = re.exec(rootMatch[1])) !== null) {
    vars[`--${m[1]}`] = m[2].trim();
  }
  return vars;
}

function extractSections(html) {
  const sections = [];
  const re = /<section[^>]*(?:id=["']([^"']+)["'])?[^>]*(?:class=["']([^"']+)["'])?[^>]*>/gi;
  let m;
  while ((m = re.exec(html)) !== null) {
    const snippet = html.slice(m.index, m.index + 800);
    const h = snippet.match(/<h[1-3][^>]*>([^<]{3,120})/i);
    sections.push({
      id: m[1] || null,
      classes: m[2] ? m[2].split(/\s+/).slice(0, 8) : [],
      heading: h ? h[1].replace(/\s+/g, ' ').trim() : null,
    });
  }
  return sections;
}

function extractClassPrefixes(html, limit = 30) {
  const counts = new Map();
  const re = /class=["']([^"']+)["']/gi;
  let m;
  while ((m = re.exec(html)) !== null) {
    for (const cls of m[1].split(/\s+/)) {
      const prefix = cls.match(/^([a-z][a-z0-9]*)(?:[-_]|$)/i)?.[1] ?? cls;
      if (prefix.length < 2) continue;
      counts.set(prefix, (counts.get(prefix) || 0) + 1);
    }
  }
  return [...counts.entries()]
    .sort((a, b) => b[1] - a[1])
    .slice(0, limit)
    .map(([prefix, count]) => ({ prefix, count }));
}

function detectDynamicFields(html) {
  const patterns = [
    { field: 'tutor_name', re: /(?:tutor|instructor)[^<]{0,40}(?:name|title)/gi },
    { field: 'rating', re: /(?:★|⭐|rating|stars?)\s*[\d.]+/gi },
    { field: 'price', re: /R\s?\d{2,4}(?:\.\d{2})?/g },
    { field: 'phone', re: /\+27\s?\d{2}\s?\d{3}\s?\d{4}/g },
    { field: 'email', re: /[a-z0-9._%+-]+@nextgentutors[a-z.]+/gi },
    { field: 'stats', re: /\d{1,3}(?:,\d{3})+\+?\s*(?:students|tutors|lessons|reviews)/gi },
    { field: 'review_text', re: /class=["'][^"']*review[^"']*["'][^>]*>[^<]{20,}/gi },
  ];
  const found = {};
  for (const { field, re } of patterns) {
    const matches = [...new Set((html.match(re) || []).slice(0, 8))];
    if (matches.length) found[field] = matches;
  }
  return found;
}

function extractHeadings(html, limit = 40) {
  const headings = [];
  const re = /<h([1-3])[^>]*>([^<]{3,200})/gi;
  let m;
  while ((m = re.exec(html)) !== null && headings.length < limit) {
    headings.push({ level: Number(m[1]), text: m[2].replace(/\s+/g, ' ').trim() });
  }
  return headings;
}

function guessPageType(html, label) {
  const lower = (extractTitle(html) + ' ' + label + ' ' + html.slice(0, 5000)).toLowerCase();
  for (const kw of PAGE_KEYWORDS) {
    if (lower.includes(kw)) {
      if (kw === 'tutor' && lower.includes('find')) return 'find-a-tutor';
      if (kw === 'tutor' && lower.includes('become')) return 'become-a-tutor';
      if (kw === 'tutor' && lower.includes('market')) return 'tutor-marketplace';
      if (kw === 'dashboard') return 'dashboard';
      if (kw === 'home' || lower.includes('hero')) return 'home';
      return kw;
    }
  }
  return 'multi-page' + (html.length > 200000 ? '-mega' : '');
}

function scanOldTheme(dir) {
  if (!fs.existsSync(dir)) return { error: 'path not found', path: dir };

  const walk = (d, acc = []) => {
    for (const ent of fs.readdirSync(d, { withFileTypes: true })) {
      const full = path.join(d, ent.name);
      if (ent.isDirectory() && !['node_modules', '.git'].includes(ent.name)) walk(full, acc);
      else if (ent.isFile()) acc.push(full);
    }
    return acc;
  };

  const files = walk(dir);
  const byExt = {};
  for (const f of files) {
    const ext = path.extname(f).toLowerCase() || '(none)';
    byExt[ext] = (byExt[ext] || 0) + 1;
  }

  const templates = files.filter((f) => /template|page-|front-|single-|archive/.test(path.basename(f)));
  const css = files.filter((f) => f.endsWith('.css'));
  const js = files.filter((f) => f.endsWith('.js'));
  const partials = files.filter((f) => f.includes('template-parts'));

  let tokens = {};
  const tokenFile = path.join(dir, 'assets/css/tokens.css');
  const tokenContent = readSafe(tokenFile);
  if (tokenContent) tokens = extractCssVars(tokenContent);

  const shortcodeRefs = [];
  for (const f of files.filter((x) => x.endsWith('.php'))) {
    const c = readSafe(f);
    if (!c) continue;
    const matches = c.match(/add_shortcode\s*\(\s*['"]([^'"]+)['"]/g);
    if (matches) {
      for (const m of matches) {
        const sc = m.match(/['"]([^'"]+)['"]/)?.[1];
        if (sc) shortcodeRefs.push({ file: path.relative(dir, f), shortcode: sc });
      }
    }
  }

  return {
    path: dir,
    fileCount: files.length,
    byExt,
    templates: templates.map((f) => path.relative(dir, f)),
    cssFiles: css.map((f) => path.relative(dir, f)),
    jsFiles: js.map((f) => path.relative(dir, f)),
    partials: partials.map((f) => path.relative(dir, f)),
    tokens,
    shortcodes: shortcodeRefs,
  };
}

function analyzeHtml(source) {
  const html = readSafe(source.file);
  if (!html) return { label: source.label, file: source.file, error: 'not found' };

  const sections = extractSections(html);
  const sectionPurposes = sections.map((s) => s.heading || s.id || s.classes[0] || 'unnamed');

  return {
    label: source.label,
    file: source.file,
    sizeKb: Math.round(html.length / 1024),
    title: extractTitle(html),
    pageType: guessPageType(html, source.label),
    sectionCount: sections.length,
    sections: sections.slice(0, 60),
    sectionPurposes: [...new Set(sectionPurposes)].slice(0, 40),
    cssVars: extractCssVars(html),
    classPrefixes: extractClassPrefixes(html),
    headings: extractHeadings(html),
    dynamicFields: detectDynamicFields(html),
    forms: (html.match(/<form\b/gi) || []).length,
    hasNav: /<nav\b/i.test(html),
    hasFooter: /<footer\b/i.test(html),
    fonts: [...new Set((html.match(/fonts\.googleapis\.com[^"']+/g) || []))],
    risks: [
      html.length > 500000 ? 'mega-file-performance' : null,
      Object.keys(detectDynamicFields(html)).includes('tutor_name') ? 'hardcoded-tutor-data' : null,
      Object.keys(detectDynamicFields(html)).includes('price') ? 'hardcoded-pricing' : null,
    ].filter(Boolean),
  };
}

function buildDuplicateReport(htmlAnalyses) {
  const headingMap = new Map();
  for (const a of htmlAnalyses) {
    if (a.error) continue;
    for (const h of a.headings || []) {
      const key = h.text.toLowerCase();
      if (!headingMap.has(key)) headingMap.set(key, []);
      headingMap.get(key).push({ source: a.label, level: h.level });
    }
  }
  const duplicates = [];
  for (const [text, sources] of headingMap) {
    if (sources.length > 1 && text.length > 8) {
      duplicates.push({
        item: text,
        sources: sources.map((s) => s.source),
        similarity: 'exact-heading',
        action: 'merge-to-single-backend-field',
      });
    }
  }
  return duplicates.slice(0, 80);
}

function main() {
  fs.mkdirSync(OUT, { recursive: true });

  const htmlAnalyses = HTML_SOURCES.map(analyzeHtml);
  const oldTheme = scanOldTheme(OLD_THEME);
  const duplicates = buildDuplicateReport(htmlAnalyses);

  const mergedTokens = {};
  for (const a of htmlAnalyses) {
    if (a.cssVars) Object.assign(mergedTokens, a.cssVars);
  }
  if (oldTheme.tokens) Object.assign(mergedTokens, oldTheme.tokens);

  const summary = {
    generatedAt: new Date().toISOString(),
    htmlFiles: htmlAnalyses.map((a) => ({
      label: a.label,
      found: !a.error,
      pageType: a.pageType,
      sections: a.sectionCount,
      risks: a.risks,
    })),
    oldThemeFound: !oldTheme.error,
    oldThemeFiles: oldTheme.fileCount,
    duplicateHeadings: duplicates.length,
    tokenCount: Object.keys(mergedTokens).length,
  };

  fs.writeFileSync(path.join(OUT, 'html-artifacts.json'), JSON.stringify(htmlAnalyses, null, 2));
  fs.writeFileSync(path.join(OUT, 'old-theme.json'), JSON.stringify(oldTheme, null, 2));
  fs.writeFileSync(path.join(OUT, 'duplicates.json'), JSON.stringify(duplicates, null, 2));
  fs.writeFileSync(path.join(OUT, 'design-tokens-raw.json'), JSON.stringify(mergedTokens, null, 2));
  fs.writeFileSync(path.join(OUT, 'ANALYSIS-SUMMARY.json'), JSON.stringify(summary, null, 2));

  console.log(JSON.stringify(summary, null, 2));
}

main();
