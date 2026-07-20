#!/usr/bin/env node
/**
 * Cross-check SVG flow manifest against Companion/theme runtime; write gap report.
 * Usage: node scripts/audit-flow-gaps.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { AUTOMATIONS, RUNTIME_BINDINGS } from './lib/flow-runtime-registry.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const manifestPath = path.join(root, 'docs', 'workflows', 'flow-manifest.json');
const outMd = path.join(root, 'docs', 'workflows', 'FLOW-GAP-REPORT.md');
const themePackPath = path.join(root, 'NextGenTutors-BeyondInfinity', 'content', 'nextgen-workflow-pack.json');
const integrateDir = path.join(root, 'NextGenTutors-Companion', 'integrate');

function loadJson(p) {
  return JSON.parse(fs.readFileSync(p, 'utf8'));
}

function fileExists(rel) {
  return fs.existsSync(path.join(root, rel));
}

function phpDefines(symbol) {
  const companion = path.join(root, 'NextGenTutors-Companion');
  const files = [];
  function walk(dir) {
    for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
      const full = path.join(dir, ent.name);
      if (ent.isDirectory() && ent.name !== 'node_modules' && ent.name !== 'vendor') walk(full);
      else if (ent.isFile() && ent.name.endsWith('.php')) files.push(full);
    }
  }
  walk(companion);
  for (const f of files) {
    if (fs.readFileSync(f, 'utf8').includes(symbol)) return true;
  }
  return false;
}

function assessWorkflow(wf, themeKeys, integrateSpecs, e2eSpecs) {
  const id = wf.id.replace('WF-', '');
  const binding = RUNTIME_BINDINGS[id];
  const gaps = [];
  const wired = [];

  if (wf.placeholder) {
    gaps.push('SVG marks workflow as not implemented in theme runtime');
  }

  if (binding?.status === 'deferred') {
    gaps.push(binding.gap || 'Workflow deferred');
  }

  if (!binding) {
    gaps.push('No entry in flow-runtime-registry.mjs');
    return { gaps, wired, coverage: 'none' };
  }

  if (binding.themeKey && themeKeys.has(binding.themeKey)) {
    wired.push(`theme pack: ${binding.themeKey}`);
  } else if (binding.themeKey) {
    gaps.push(`theme pack key missing: ${binding.themeKey}`);
  }

  if (binding.orchestrator && phpDefines(`'${binding.orchestrator}'`)) {
    wired.push(`orchestrator: ${binding.orchestrator}`);
  } else if (binding.orchestrator) {
    gaps.push(`orchestrator handler not found: ${binding.orchestrator}`);
  }

  if (binding.module) {
    const mod = binding.module.split('::')[0];
    const phpOk = phpDefines(mod) || (mod.includes('/') && fileExists(mod.replace(/^theme\//, 'NextGenTutors-BeyondInfinity/')));
    if (phpOk) wired.push(`module: ${binding.module}`);
    else if (!binding.module.includes('::')) gaps.push(`PHP module not found: ${binding.module}`);
  }

  if (binding.formId && phpDefines(`'${binding.formId}'`)) {
    wired.push(`form: ${binding.formId}`);
  }

  if (wf.integrateSpec) {
    if (integrateSpecs.has(wf.integrateSpec)) wired.push(`integrate: ${wf.integrateSpec}`);
    else gaps.push(`integrate spec missing: ${wf.integrateSpec}`);
  }

  if (binding.automation && fileExists(path.join('automations', binding.automation))) {
    wired.push(`automation: ${binding.automation}`);
  }

  if (binding.e2e) {
    const specs = binding.e2e.split(',').map((s) => s.trim());
    const hit = specs.some((s) => e2eSpecs.some((f) => f.includes(s)));
    if (hit) wired.push(`e2e: ${binding.e2e}`);
    else gaps.push(`no Playwright spec match for: ${binding.e2e}`);
  }

  if (binding.gap) gaps.push(binding.gap);
  if (binding.note) gaps.push(`note: ${binding.note}`);

  const svgTrigger = (wf.trigger || '').toLowerCase();
  const runtimeTrigger = (binding.trigger || binding.themeEvent || '').toLowerCase();
  if (svgTrigger && runtimeTrigger && !svgTrigger.includes(runtimeTrigger.split('.').pop()) && !runtimeTrigger.includes(svgTrigger.split(' ')[0])) {
    if (!wf.placeholder) {
      gaps.push(`trigger drift: SVG "${wf.trigger}" vs runtime "${binding.trigger || binding.themeEvent}"`);
    }
  }

  let coverage = 'partial';
  if (gaps.length === 0 && wired.length >= 2) coverage = 'full';
  else if (wired.length === 0) coverage = 'none';
  else if (wf.placeholder && wired.length > 0) coverage = 'svg-stale';

  return { gaps, wired, coverage };
}

if (!fs.existsSync(manifestPath)) {
  console.error('Run node scripts/svg-to-flow-manifest.mjs first');
  process.exit(1);
}

const manifest = loadJson(manifestPath);
const themePack = loadJson(themePackPath);
const themeKeys = new Set((themePack.workflows || []).map((w) => w.key));

const integrateSpecs = new Set(
  fs.existsSync(integrateDir)
    ? fs
        .readdirSync(integrateDir)
        .filter((f) => f.startsWith('workflow-') && f.endsWith('.json'))
        .map((f) => f.replace(/\.json$/, ''))
    : []
);

const e2eDir = path.join(root, 'e2e', 'workflows');
const e2eSpecs = fs.existsSync(e2eDir) ? fs.readdirSync(e2eDir) : [];

const rows = manifest.workflows.map((wf) => {
  const { gaps, wired, coverage } = assessWorkflow(wf, themeKeys, integrateSpecs, e2eSpecs);
  return { ...wf, gaps, wired, coverage };
});

const full = rows.filter((r) => r.coverage === 'full').length;
const partial = rows.filter((r) => r.coverage === 'partial' || r.coverage === 'svg-stale').length;
const none = rows.filter((r) => r.coverage === 'none').length;
const staleSvg = rows.filter(
  (r) => r.placeholder && r.wired.length > 0 && !/DEFERRED/i.test(r.diagramStatus)
);

const lines = [
  '# Flow Gap Report — SVG → Runtime',
  '',
  `**Generated:** ${new Date().toISOString()}`,
  `**Source manifest:** \`docs/workflows/flow-manifest.json\``,
  '',
  '## Summary',
  '',
  '| Metric | Count |',
  '|--------|------:|',
  `| Blueprint workflows (SVG) | ${rows.length} |`,
  `| Full runtime coverage | ${full} |`,
  `| Partial / stale | ${partial} |`,
  `| No runtime binding | ${none} |`,
  `| SVG placeholder but runtime exists | ${staleSvg.length} |`,
  '',
  '## Stale SVG diagrams (update recommended)',
  '',
];

if (staleSvg.length === 0) {
  lines.push('_None detected._');
} else {
  for (const r of staleSvg) {
    lines.push(`- **${r.id}** ${r.title} — runtime: ${r.wired.join(', ')}`);
  }
}

lines.push('', '## Per-workflow matrix', '');
lines.push(
  '| WF | Title | SVG status | Coverage | Wired | Gaps |',
  '|----|-------|------------|----------|-------|------|'
);

for (const r of rows) {
  const gapText = r.gaps.length ? r.gaps.slice(0, 2).join('; ') : '—';
  const wiredText = r.wired.length ? r.wired.slice(0, 2).join(', ') : '—';
  lines.push(
    `| ${r.id} | ${r.title} | ${r.diagramStatus} | ${r.coverage} | ${wiredText} | ${gapText} |`
  );
}

lines.push('', '## Automations folder (parallel path)', '');
lines.push('| File | Blueprint WF | External trigger | Companion equivalent |');
lines.push('|------|--------------|------------------|---------------------|');
for (const a of AUTOMATIONS) {
  const b = RUNTIME_BINDINGS[a.blueprint[0]];
  lines.push(
    `| ${a.file} | WF-${a.blueprint.join(', WF-')} | ${a.trigger} | ${b?.module || b?.themeKey || b?.orchestrator || '—'} |`
  );
}

lines.push('', '## Strict translation actions', '');
lines.push('1. **Normalize IDs** — use blueprint `WF-NN` everywhere (rename Playwright `wf01` → blueprint WF-03).');
lines.push('2. **Refresh stale SVGs** — rows marked `svg-stale` (companion implemented, diagram still says not implemented).');
lines.push('3. **Wire WF-09** — automated matching: add orchestrator + Studio template or mark SVG as deferred.');
lines.push('4. **Deprecate or import `automations/`** — replace `REPLACE_WITH_*` or route through Companion adapters only.');
lines.push('5. **Re-run audit** — `node scripts/svg-to-flow-manifest.mjs && node scripts/audit-flow-gaps.mjs`');
lines.push('');

fs.mkdirSync(path.dirname(outMd), { recursive: true });
fs.writeFileSync(outMd, lines.join('\n'));
console.log(`Wrote ${outMd}`);
console.log(`Coverage: full=${full} partial=${partial} none=${none} stale-svg=${staleSvg.length}`);
