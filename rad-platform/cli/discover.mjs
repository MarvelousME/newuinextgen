#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import { paths } from './lib/paths.mjs';
import { writeJson, writeText } from './lib/load.mjs';

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
};

writeJson(path.join(p.currentState, 'discover-snapshot.json'), inventory);

const md = `# SYSTEM-INVENTORY (discover)

Generated: ${stamp}

## Operating mode

**BROWNFIELD** — existing WordPress tutoring platform with Companion domain plugin and BeyondInfinity theme.

## Packages

| ID | Folder | Present |
|----|--------|---------|
${packageInventory.map((x) => `| ${x.id} | \`${x.folder}/\` | ${x.exists ? 'yes' : 'NO'} |`).join('\n')}

## Audit feeders

${auditFeeds.map((f) => `- ${f.exists ? 'OK' : 'MISSING'} \`${f.path}\``).join('\n')}

## Notes

- Discovery refreshes machine snapshot only; curated inventories in \`architecture/current-state/*.md\` remain authoritative narrative.
- Run \`node rad-platform/cli/gate.mjs\` after design/implement changes.
`;

writeText(path.join(p.currentState, 'SYSTEM-INVENTORY.generated.md'), md);
console.log(`discover: wrote ${path.relative(p.repoRoot, path.join(p.currentState, 'discover-snapshot.json'))}`);
console.log(`discover: wrote ${path.relative(p.repoRoot, path.join(p.currentState, 'SYSTEM-INVENTORY.generated.md'))}`);

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
