/**
 * Resolve repo root and architecture paths for RAD CLI.
 * Works when invoked from repo root or rad-platform/.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const KIT_ROOT = path.resolve(__dirname, '../..');

export function findRepoRoot(start = process.cwd()) {
  let cur = path.resolve(start);
  for (let i = 0; i < 12; i++) {
    if (
      fs.existsSync(path.join(cur, 'architecture')) &&
      fs.existsSync(path.join(cur, 'rad-platform'))
    ) {
      return cur;
    }
    if (fs.existsSync(path.join(cur, 'ARCHITECTURE.md')) && fs.existsSync(path.join(cur, 'rad-platform'))) {
      return cur;
    }
    const parent = path.dirname(cur);
    if (parent === cur) break;
    cur = parent;
  }
  // Fallback: kit is inside repo
  const fromKit = path.resolve(KIT_ROOT, '..');
  if (fs.existsSync(path.join(fromKit, 'architecture'))) return fromKit;
  return fromKit;
}

export function paths(repoRoot = findRepoRoot()) {
  return {
    repoRoot,
    kitRoot: path.join(repoRoot, 'rad-platform'),
    architecture: path.join(repoRoot, 'architecture'),
    manifests: path.join(repoRoot, 'architecture', 'manifests'),
    capabilities: path.join(repoRoot, 'architecture', 'capabilities'),
    contracts: path.join(repoRoot, 'architecture', 'contracts'),
    policies: path.join(repoRoot, 'architecture', 'policies'),
    dependencyRules: path.join(repoRoot, 'architecture', 'dependency-rules'),
    currentState: path.join(repoRoot, 'architecture', 'current-state'),
    reports: path.join(repoRoot, 'architecture', 'reports'),
    schemas: path.join(repoRoot, 'rad-platform', 'schemas'),
    fixtures: path.join(repoRoot, 'rad-platform', 'fixtures'),
    packages: {
      beyondinfinity: 'NextGenTutors-BeyondInfinity',
      companion: 'NextGenTutors-Companion',
      'ai-integration': 'NextGenTutors-AI-Integration',
      'html-importer': 'NextGenTutors-Html-Importer',
      'plugin-manager': 'NextGenTutors-Plugin-Manager',
    },
  };
}

export { KIT_ROOT };
