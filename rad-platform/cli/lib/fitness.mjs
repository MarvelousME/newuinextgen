import fs from 'node:fs';
import path from 'node:path';
import { readJson, loadManifests, loadCapabilities, loadDependencyRules } from './load.mjs';
import { validateAgainstSchema } from './validate-schema.mjs';

/**
 * Run architecture fitness checks. Returns { ok, findings[] }.
 */
export function runFitness(p, options = {}) {
  const findings = [];
  const manifestSchema = readJson(path.join(p.schemas, 'subsystem-manifest.schema.json'));
  const capabilitySchema = readJson(path.join(p.schemas, 'capability.schema.json'));

  const manifests = loadManifests(p.manifests);
  const capabilities = loadCapabilities(p.capabilities);
  const rules = loadDependencyRules(p.dependencyRules);
  const capIds = new Set(capabilities.map((c) => c.data.capabilityId).filter(Boolean));

  // Unique subsystem IDs + schema
  const ids = new Map();
  for (const { file, data } of manifests) {
    const errs = validateAgainstSchema(data, manifestSchema);
    for (const e of errs) {
      findings.push({ severity: 'error', code: 'MANIFEST_SCHEMA', file, message: e });
    }
    const id = data?.system?.id;
    if (id) {
      if (ids.has(id)) {
        findings.push({
          severity: 'error',
          code: 'ARCH-UNIQUE-ID',
          file,
          message: `Duplicate subsystem id "${id}" also in ${ids.get(id)}`,
        });
      } else {
        ids.set(id, file);
      }
    }
  }

  // Capability schema + permissions (ARCH-004)
  for (const { file, data } of capabilities) {
    const errs = validateAgainstSchema(data, capabilitySchema);
    for (const e of errs) {
      findings.push({ severity: 'error', code: 'CAPABILITY_SCHEMA', file, message: e });
    }
    if (!Array.isArray(data.requiredPermissions)) {
      findings.push({
        severity: 'error',
        code: 'ARCH-004',
        file,
        message: `Capability ${data.capabilityId || '?'} missing requiredPermissions`,
      });
    }
  }

  // ARCH-003: consumes ⊆ registry; provides have contract refs
  for (const { file, data } of manifests) {
    const provides = data?.capabilities?.provides || [];
    const consumes = data?.capabilities?.consumes || [];
    for (const c of consumes) {
      if (!capIds.has(c)) {
        findings.push({
          severity: 'error',
          code: 'ARCH-003',
          file,
          message: `Consumes undeclared capability "${c}"`,
        });
      }
    }
    for (const c of provides) {
      if (!capIds.has(c)) {
        findings.push({
          severity: 'error',
          code: 'ARCH-003',
          file,
          message: `Provides capability "${c}" not found in architecture/capabilities`,
        });
      } else {
        const cap = capabilities.find((x) => x.data.capabilityId === c)?.data;
        if (cap && (!cap.contract || String(cap.contract).trim() === '')) {
          findings.push({
            severity: 'error',
            code: 'CONTRACT_REF',
            file: capabilities.find((x) => x.data.capabilityId === c).file,
            message: `Capability "${c}" missing contract file ref`,
          });
        }
      }
    }

    // ARCH-009 health fields
    if (!data?.health?.readiness || !data?.health?.liveness) {
      findings.push({
        severity: 'error',
        code: 'ARCH-009',
        file,
        message: 'Missing health.readiness or health.liveness',
      });
    }
  }

  // ARCH-019 dependency graph edges + cycles
  const depGraph = buildDepGraph(manifests, rules);
  for (const cycle of detectCycles(depGraph)) {
    findings.push({
      severity: 'error',
      code: 'DEP_CYCLE',
      file: p.dependencyRules,
      message: `Circular dependency: ${cycle.join(' -> ')}`,
    });
  }

  // Undeclared required subsystem deps
  for (const { file, data } of manifests) {
    const id = data.system.id;
    for (const dep of data.dependencies?.required || []) {
      if (!ids.has(dep) && !isExternalDep(dep)) {
        findings.push({
          severity: 'error',
          code: 'ARCH-019',
          file,
          message: `Required dependency "${dep}" is not a registered subsystem`,
        });
      }
    }
  }

  // Package boundary scan (ARCH-001/018/024) — static PHP cross-package requires
  if (!options.skipBoundaryScan) {
    findings.push(...scanPackageBoundaries(p, rules, ids));
  }

  // ARCH-002 theme writing ngc_ tables (heuristic)
  findings.push(...scanThemeDataOwnership(p));

  // ARCH-021/022 vendor denylist in Domain/Application paths
  findings.push(...scanVendorDenylist(p));

  const errors = findings.filter((f) => f.severity === 'error');
  return {
    ok: errors.length === 0,
    findings,
    stats: {
      manifests: manifests.length,
      capabilities: capabilities.length,
      errors: errors.length,
      warnings: findings.filter((f) => f.severity === 'warning').length,
    },
    depGraph,
  };
}

function isExternalDep(dep) {
  return ['wordpress', 'woocommerce', 'php', 'mysql', 'composer'].includes(dep);
}

function buildDepGraph(manifests, _rules) {
  // Cycle detection uses required subsystem deps only.
  // Optional + allow-list contract edges are bidirectional by design and excluded.
  const graph = {};
  for (const { data } of manifests) {
    const id = data.system.id;
    const required = (data.dependencies?.required || []).filter((d) => !isExternalDep(d));
    graph[id] = new Set(required);
  }
  return graph;
}

function detectCycles(graph) {
  const cycles = [];
  const visiting = new Set();
  const visited = new Set();
  const stack = [];

  function dfs(node) {
    if (visiting.has(node)) {
      const idx = stack.indexOf(node);
      cycles.push([...stack.slice(idx), node]);
      return;
    }
    if (visited.has(node)) return;
    visiting.add(node);
    stack.push(node);
    for (const next of graph[node] || []) {
      if (graph[next] || graph[node]) dfs(next);
    }
    stack.pop();
    visiting.delete(node);
    visited.add(node);
  }

  for (const node of Object.keys(graph)) dfs(node);
  return cycles;
}

const PACKAGE_OWNERS = [
  { id: 'beyondinfinity', dir: 'NextGenTutors-BeyondInfinity', prefixes: ['BI_', 'BeyondInfinity'] },
  { id: 'companion', dir: 'NextGenTutors-Companion', prefixes: ['NGC_', 'BIA_'] },
  { id: 'ai-integration', dir: 'NextGenTutors-AI-Integration', prefixes: ['NGTAI_', 'NGT_AI_'] },
  { id: 'html-importer', dir: 'NextGenTutors-Html-Importer', prefixes: ['NGTHI_', 'Revamp_Html'] },
  { id: 'plugin-manager', dir: 'NextGenTutors-Plugin-Manager', prefixes: ['NGCPM_', 'NGTPM_'] },
];

function scanPackageBoundaries(p, rules, ids) {
  const findings = [];
  const allow = new Set((rules.allow || []).map((e) => `${e.from}->${e.to}`));

  // Forbidden: Companion internals imported from theme via require of Companion paths
  const themeDir = path.join(p.repoRoot, 'NextGenTutors-BeyondInfinity');
  if (fs.existsSync(themeDir)) {
    walkPhp(themeDir, (file, content) => {
      if (/NextGenTutors-Companion[\\/]includes/.test(content) || /require.*Companion.*includes/.test(content)) {
        const edge = 'beyondinfinity->companion';
        if (!allow.has(edge) && !allow.has('beyondinfinity->companion:internal')) {
          findings.push({
            severity: 'error',
            code: 'ARCH-001',
            file,
            message: 'Theme requires Companion internal includes — use shortcodes/REST contracts only',
          });
        }
      }
      // ARCH-002: direct $wpdb writes to ngc_
      if (/\$wpdb->(insert|update|delete|query)\s*\([^)]*ngc_/i.test(content) || /INSERT\s+INTO\s+[`']?[^`'\s]*ngc_/i.test(content)) {
        findings.push({
          severity: 'error',
          code: 'ARCH-002',
          file,
          message: 'Theme appears to write ngc_* tables (data ownership violation)',
        });
      }
    });
  }

  // Root theme mirror (legacy dual tree)
  const rootFunctions = path.join(p.repoRoot, 'functions.php');
  if (fs.existsSync(rootFunctions)) {
    const content = fs.readFileSync(rootFunctions, 'utf8');
    if (/NextGenTutors-Companion[\\/]includes/.test(content)) {
      findings.push({
        severity: 'error',
        code: 'ARCH-001',
        file: rootFunctions,
        message: 'Root theme requires Companion internals',
      });
    }
  }

  return findings;
}

function scanThemeDataOwnership(p) {
  // Covered in boundary scan; keep hook for future SQL AST
  return [];
}

function scanVendorDenylist(p) {
  const findings = [];
  const denylist = ['\\\\Stripe\\\\', 'Aws\\\\Sdk', 'PayFast\\\\Client'];
  const roots = [
    path.join(p.repoRoot, 'NextGenTutors-Companion', 'includes', 'domain'),
    path.join(p.repoRoot, 'NextGenTutors-Companion', 'Domain'),
  ];
  for (const root of roots) {
    if (!fs.existsSync(root)) continue;
    walkPhp(root, (file, content) => {
      for (const pat of denylist) {
        if (new RegExp(pat).test(content)) {
          findings.push({
            severity: 'error',
            code: 'ARCH-022',
            file,
            message: `Domain/application path references vendor SDK pattern ${pat}`,
          });
        }
      }
    });
  }
  return findings;
}

function walkPhp(dir, fn) {
  if (!fs.existsSync(dir)) return;
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const ent of entries) {
    const full = path.join(dir, ent.name);
    if (ent.isDirectory()) {
      if (['vendor', 'node_modules', '.git'].includes(ent.name)) continue;
      walkPhp(full, fn);
    } else if (ent.name.endsWith('.php')) {
      try {
        fn(full, fs.readFileSync(full, 'utf8'));
      } catch {
        /* skip unreadable */
      }
    }
  }
}
