#!/usr/bin/env node
/**
 * Architecture gate — exit 0 on pass, 1 on fail.
 * --fixtures: validate that invalid fixture fails (self-test).
 */
import fs from 'node:fs';
import path from 'node:path';
import { paths } from './lib/paths.mjs';
import { readJson, writeJson, writeText } from './lib/load.mjs';
import { validateAgainstSchema } from './lib/validate-schema.mjs';
import { runFitness } from './lib/fitness.mjs';

const args = process.argv.slice(2);
const p = paths();

if (args.includes('--fixtures')) {
  const fixtureDir = path.join(p.fixtures, 'invalid-manifest');
  const schema = readJson(path.join(p.schemas, 'subsystem-manifest.schema.json'));
  const files = fs.existsSync(fixtureDir)
    ? fs.readdirSync(fixtureDir).filter((f) => f.endsWith('.json'))
    : [];
  if (files.length === 0) {
    console.error('gate --fixtures: no fixtures found');
    process.exit(1);
  }
  let failedAsExpected = 0;
  for (const f of files) {
    const data = readJson(path.join(fixtureDir, f));
    const errs = validateAgainstSchema(data, schema);
    if (errs.length > 0) {
      failedAsExpected += 1;
      console.log(`fixture ${f}: correctly invalid (${errs.length} errors)`);
    } else {
      console.error(`fixture ${f}: expected invalid but passed schema`);
      process.exit(1);
    }
  }
  console.log(`gate --fixtures: PASS (${failedAsExpected} fixtures failed as expected)`);
  process.exit(0);
}

const result = runFitness(p);
const report = {
  generatedAt: new Date().toISOString(),
  ok: result.ok,
  stats: result.stats,
  findings: result.findings,
};
writeJson(path.join(p.reports, 'gate-report.json'), report);

const md = [
  '# Architecture Gate Report',
  '',
  `Generated: ${report.generatedAt}`,
  '',
  `**Result:** ${result.ok ? 'PASS' : 'FAIL'}`,
  '',
  `Manifests: ${result.stats.manifests} | Capabilities: ${result.stats.capabilities} | Errors: ${result.stats.errors}`,
  '',
  '## Findings',
  '',
];
if (result.findings.length === 0) {
  md.push('_No findings._');
} else {
  for (const f of result.findings) {
    md.push(`- **${f.severity}** \`${f.code}\` — ${f.message}`);
    md.push(`  - file: \`${f.file}\``);
  }
}
writeText(path.join(p.reports, 'gate-report.md'), md.join('\n'));

if (!result.ok) {
  console.error('gate: FAIL');
  for (const f of result.findings.filter((x) => x.severity === 'error')) {
    console.error(`  [${f.code}] ${f.message}`);
  }
  process.exit(1);
}
console.log('gate: PASS');
console.log('gate: wrote architecture/reports/gate-report.json');
