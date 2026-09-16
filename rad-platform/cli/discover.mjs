#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { paths } from './lib/paths.mjs';
import { writeJson, writeText } from './lib/load.mjs';
import { scanLockfiles } from './lib/scan-lockfiles.mjs';

const p = paths();
const stamp = new Date().toISOString();

const packageInventory = Object.entries(p.packages).map(([id, folder]) => {
  const abs = path.join(p.repoRoot, folder);
  return {
    id,
    folder,
    exists: fs.existsSync(abs),
    entrypoints: guessEntrypoints(abs, id),
  };
});

const auditFeeds = [
  '.agent-audit/01-repository-inventory.md',
  '.agent-audit/02-architecture-current-state.md',
  '.agent-audit/11-functional-capability-matrix.md',
  'ARCHITECTURE.md',
].map((rel) => ({
  path: rel,
  exists: fs.existsSync(path.join(p.repoRoot, rel)),
}));

const dependencyLocks = scanLockfiles(p.repoRoot);
const lockSummary = {
  total: dependencyLocks.length,
  npm: dependencyLocks.filter((x) => x.ecosystem === 'npm').length,
  composer: dependencyLocks.filter((x) => x.ecosystem === 'composer').length,
  withLockfile: dependencyLocks.filter((x) => x.lockPresent).length,
};

const inventory = {
  generatedAt: stamp,
  mode: 'BROWNFIELD',
  packages: packageInventory,
  auditFeeds,
  architectureDirs: {
    manifests: fs.existsSync(p.manifests),
    capabilities: fs.existsSync(p.capabilities),
    dependencyRules: fs.existsSync(p.dependencyRules),
  },
  dependencyLocks,
  dependencyLockSummary: lockSummary,
};

writeJson(path.join(p.currentState, 'discover-snapshot.json'), inventory);

const lockRows = dependencyLocks
  .map(
    (x) =>
      `| ${x.ecosystem} | \`${x.path}\` | ${x.name || '—'} | ${x.lockPresent ? 'yes' : 'no'} | ${x.directDeps.length} |`
  )
  .join('\n');

const md = `# SYSTEM-INVENTORY (discover)

Generated: ${stamp}

## Operating mode

**BROWNFIELD** — existing WordPress tutoring platform with Companion domain plugin and BeyondInfinity theme.

## Packages

| ID | Folder | Present |
|----|--------|---------|
${packageInventory.map((x) => `| ${x.id} | \`${x.folder}/\` | ${x.exists ? 'yes' : 'NO'} |`).join('\n')}

## Dependency locks (Composer / npm)

Scanned manifests: **${lockSummary.total}** (npm ${lockSummary.npm}, composer ${lockSummary.composer}; lockfile present ${lockSummary.withLockfile}).

| Ecosystem | Manifest | Name | Lockfile | Direct deps |
|-----------|----------|------|----------|-------------|
${lockRows || '| — | — | — | — | — |'}

## Audit feeders

${auditFeeds.map((f) => `- ${f.exists ? 'OK' : 'MISSING'} \`${f.path}\``).join('\n')}

## Notes

- Discovery refreshes machine snapshot only; curated inventories in \`architecture/current-state/*.md\` remain authoritative narrative.
- Lockfile scan inventories \`composer.json\` / \`package.json\` (+ lock presence); it does not resolve full transitive graphs.
- Run \`node rad-platform/cli/gate.mjs\` after design/implement changes.
`;

writeText(path.join(p.currentState, 'SYSTEM-INVENTORY.generated.md'), md);
console.log(`discover: wrote ${path.relative(p.repoRoot, path.join(p.currentState, 'discover-snapshot.json'))}`);
console.log(`discover: wrote ${path.relative(p.repoRoot, path.join(p.currentState, 'SYSTEM-INVENTORY.generated.md'))}`);
console.log(`discover: dependencyLocks=${lockSummary.total} (lockfiles=${lockSummary.withLockfile})`);

function guessEntrypoints(abs, id) {
  if (!fs.existsSync(abs)) return [];
  const candidates = {
    beyondinfinity: ['functions.php', 'style.css'],
    companion: ['nextgencompanion.php'],
    'ai-integration': ['nextgentutors-ai-integration.php'],
    'html-importer': ['revamp-html-importer.php', 'nextgentutors-html-importer.php'],
    'plugin-manager': ['NextGenTutors-Plugin-Manager.php'],
  };
  return (candidates[id] || []).filter((f) => fs.existsSync(path.join(abs, f)));
}
