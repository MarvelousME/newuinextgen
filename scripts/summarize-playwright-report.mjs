#!/usr/bin/env node
/**
 * Build markdown summary from Playwright JSON report.
 * Usage: node scripts/summarize-playwright-report.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const jsonPath = path.join(root, 'e2e', 'reports', 'results.json');
const outPath = path.join(root, 'e2e', 'reports', 'SUMMARY.md');

if (!fs.existsSync(jsonPath)) {
  console.error('Missing e2e/reports/results.json — run Playwright first.');
  process.exit(1);
}

const report = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));
const suites = report.suites || [];

const rows = [];

function walk(suite, prefix = '') {
  const title = prefix ? `${prefix} › ${suite.title}` : suite.title;
  for (const spec of suite.specs || []) {
    for (const test of spec.tests || []) {
      const results = test.results || [];
      const last = results[results.length - 1] || {};
      rows.push({
        workflow: title,
        test: spec.title,
        status: test.status || last.status || 'unknown',
        duration: results.reduce((s, r) => s + (r.duration || 0), 0),
      });
    }
  }
  for (const child of suite.suites || []) {
    walk(child, title);
  }
}

for (const suite of suites) {
  walk(suite);
}

const passed = rows.filter((r) => r.status === 'expected' || r.status === 'passed').length;
const failed = rows.filter((r) => r.status === 'failed' || r.status === 'unexpected').length;
const skipped = rows.filter((r) => r.status === 'skipped').length;
const total = rows.length;

const lines = [
  '# NextGen Tutors — Playwright Workflow E2E Summary',
  '',
  `**Generated:** ${new Date().toISOString()}`,
  `**Base URL:** ${process.env.BASE_URL || 'http://localhost:8899'}`,
  '',
  '## Results',
  '',
  `| Metric | Count |`,
  `|--------|------:|`,
  `| Total | ${total} |`,
  `| Passed | ${passed} |`,
  `| Failed | ${failed} |`,
  `| Skipped | ${skipped} |`,
  '',
  '## Workflow Tests',
  '',
  '| Workflow | Test | Status | Duration (ms) |',
  '|----------|------|--------|--------------:|',
];

for (const row of rows) {
  const status =
    row.status === 'expected' || row.status === 'passed'
      ? 'PASS'
      : row.status === 'skipped'
        ? 'SKIP'
        : 'FAIL';
  lines.push(
    `| ${row.workflow} | ${row.test} | ${status} | ${Math.round(row.duration)} |`
  );
}

lines.push('', '## Artifacts', '', '- HTML report: `e2e/reports/html/index.html`', '- JSON: `e2e/reports/results.json`', '');

fs.mkdirSync(path.dirname(outPath), { recursive: true });
fs.writeFileSync(outPath, lines.join('\n'));
console.log(`Wrote ${outPath}`);
console.log(`PASS ${passed} / FAIL ${failed} / SKIP ${skipped} / TOTAL ${total}`);
process.exit(failed > 0 ? 1 : 0);
