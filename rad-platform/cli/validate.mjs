#!/usr/bin/env node
import path from 'node:path';
import { paths } from './lib/paths.mjs';
import { readJson, loadManifests, loadCapabilities, writeJson } from './lib/load.mjs';
import { validateAgainstSchema } from './lib/validate-schema.mjs';

const p = paths();
const manifestSchema = readJson(path.join(p.schemas, 'subsystem-manifest.schema.json'));
const capabilitySchema = readJson(path.join(p.schemas, 'capability.schema.json'));

const findings = [];
for (const { file, data } of loadManifests(p.manifests)) {
  for (const e of validateAgainstSchema(data, manifestSchema)) {
    findings.push({ file, message: e });
  }
}
for (const { file, data } of loadCapabilities(p.capabilities)) {
  for (const e of validateAgainstSchema(data, capabilitySchema)) {
    findings.push({ file, message: e });
  }
}

const report = {
  ok: findings.length === 0,
  findings,
  generatedAt: new Date().toISOString(),
};
writeJson(path.join(p.reports, 'validate-report.json'), report);

if (!report.ok) {
  console.error('validate: FAIL');
  for (const f of findings) console.error(`  - ${f.file}: ${f.message}`);
  process.exit(1);
}
console.log(`validate: PASS (${loadManifests(p.manifests).length} manifests, ${loadCapabilities(p.capabilities).length} capabilities)`);
