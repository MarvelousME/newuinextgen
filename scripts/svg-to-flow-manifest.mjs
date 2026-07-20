#!/usr/bin/env node
/**
 * Parse enterprise blueprint workflow SVGs into a machine-readable flow manifest.
 * Usage: node scripts/svg-to-flow-manifest.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { BLUEPRINT_TO_INTEGRATE, RUNTIME_BINDINGS } from './lib/flow-runtime-registry.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const svgDir = path.join(
  root,
  'NextGenTutors-BeyondInfinity',
  'docs',
  'enterprise-blueprint',
  'diagrams',
  'workflows'
);
const outJson = path.join(root, 'docs', 'workflows', 'flow-manifest.json');

function extractTextNodes(svg) {
  const nodes = [];
  const re = /<text\b([^>]*)>([\s\S]*?)<\/text>/gi;
  let m;
  while ((m = re.exec(svg))) {
    const attrs = m[1];
    const inner = m[2];
    const y = Number((attrs.match(/\by="(\d+)"/) || [])[1] || 0);
    const x = Number((attrs.match(/\bx="(\d+)"/) || [])[1] || 0);
    const text = inner
      .replace(/<tspan[^>]*>/gi, '\n')
      .replace(/<\/tspan>/gi, '')
      .replace(/<[^>]+>/g, '')
      .replace(/`/g, '')
      .split('\n')
      .map((s) => s.trim())
      .filter(Boolean)
      .join(' | ');
    if (text) nodes.push({ x, y, text });
  }
  return nodes;
}

function parseWorkflowSvg(filePath) {
  const svg = fs.readFileSync(filePath, 'utf8');
  const base = path.basename(filePath, '.svg');
  const idMatch = base.match(/^WF-(\d{2})-/);
  const id = idMatch ? idMatch[1] : null;
  const texts = extractTextNodes(svg);

  const titleNode = texts.find((t) => /^WF-\d{2}:/.test(t.text));
  const title = titleNode?.text?.replace(/^WF-\d{2}:\s*/, '') || base;

  const statusNode = texts.find(
    (t) => t.y >= 30 && t.y <= 45 && /VERIFIED|PARTIAL|NOT VERIFIED/i.test(t.text)
  );
  const diagramStatus = statusNode?.text || 'UNKNOWN';

  const placeholder = texts.some((t) => /not implemented in theme runtime/i.test(t.text));

  const steps = texts
    .filter((t) => t.y >= 115 && t.y <= 155 && t.x >= 40 && t.x <= 1000)
    .filter((t) => !/^(Actor|Notify|DB|Audit)$/i.test(t.text.split(' | ')[0]))
    .map((t) => t.text)
    .filter((s) => !/^WF-\d{2}:/.test(s) && !/BPMN flow/i.test(s));

  const decisions = texts
    .filter((t) => t.y >= 200 && t.y <= 250)
    .map((t) => t.text)
    .filter((s) => /\?|valid|exists|judgment/i.test(s));

  const exceptions = texts
    .filter((t) => t.y >= 250 && t.y <= 300)
    .map((t) => t.text)
    .filter((s) => /403|error|invalid|missing/i.test(s));

  function lane(label) {
    const node = texts.find((t) => t.y >= 300 && t.y <= 420 && t.text.startsWith(`${label} |`));
    if (node) return node.text.replace(`${label} | `, '');
    const head = texts.find((t) => t.y >= 320 && t.y <= 340 && t.text === label);
    if (!head) return null;
    const detail = texts.find((t) => t.y > head.y && t.y < head.y + 30 && t.x > head.x - 80 && t.x < head.x + 80);
    return detail?.text || null;
  }

  const actor = lane('Actor');
  const notify = lane('Notify');
  const db = lane('DB');
  const audit = lane('Audit');

  const trigger = steps[0] || null;
  const runtime = id ? RUNTIME_BINDINGS[id] : null;
  const integrateId = id ? BLUEPRINT_TO_INTEGRATE[id] : null;

  return {
    id: id ? `WF-${id}` : base,
    file: path.basename(filePath),
    title,
    diagramStatus,
    placeholder,
    trigger,
    steps,
    decisions,
    exceptions,
    lanes: { actor, notify, db, audit },
    integrateSpec: integrateId,
    runtimeBinding: runtime,
  };
}

if (!fs.existsSync(svgDir)) {
  console.error('SVG directory missing:', svgDir);
  process.exit(1);
}

const files = fs
  .readdirSync(svgDir)
  .filter((f) => /^WF-\d{2}-.+\.svg$/.test(f))
  .sort();

const workflows = files.map((f) => parseWorkflowSvg(path.join(svgDir, f)));

const manifest = {
  schema: 'ngt_flow_manifest',
  version: '1.0.0',
  generatedAt: new Date().toISOString(),
  source: 'NextGenTutors-BeyondInfinity/docs/enterprise-blueprint/diagrams/workflows',
  count: workflows.length,
  workflows,
};

fs.mkdirSync(path.dirname(outJson), { recursive: true });
fs.writeFileSync(outJson, JSON.stringify(manifest, null, 2) + '\n');
console.log(`Wrote ${outJson} (${workflows.length} workflows)`);
