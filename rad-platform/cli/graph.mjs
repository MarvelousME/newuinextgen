#!/usr/bin/env node
import path from 'node:path';
import { paths } from './lib/paths.mjs';
import { loadManifests, loadDependencyRules, writeJson, writeText } from './lib/load.mjs';

const p = paths();
const manifests = loadManifests(p.manifests);
const rules = loadDependencyRules(p.dependencyRules);

const nodes = manifests.map(({ data }) => ({
  id: data.system.id,
  name: data.system.name,
  version: data.system.version,
  provides: data.capabilities?.provides || [],
  consumes: data.capabilities?.consumes || [],
}));

const edges = [];
for (const { data } of manifests) {
  for (const dep of [...(data.dependencies?.required || []), ...(data.dependencies?.optional || [])]) {
    edges.push({
      from: data.system.id,
      to: dep,
      kind: (data.dependencies?.required || []).includes(dep) ? 'required' : 'optional',
    });
  }
}
for (const e of rules.allow || []) {
  edges.push({ from: e.from, to: e.to, kind: e.kind || 'allow', capability: e.capability || null });
}

const graph = { generatedAt: new Date().toISOString(), nodes, edges };
writeJson(path.join(p.reports, 'dependency-graph.json'), graph);

const lines = ['digraph RAD {', '  rankdir=LR;'];
for (const n of nodes) {
  lines.push(`  "${n.id}" [label="${n.id}\\n${n.version}"];`);
}
for (const e of edges) {
  lines.push(`  "${e.from}" -> "${e.to}" [label="${e.kind}"];`);
}
lines.push('}');
writeText(path.join(p.reports, 'dependency-graph.dot'), lines.join('\n'));

console.log(`graph: ${nodes.length} nodes, ${edges.length} edges`);
console.log(`graph: wrote architecture/reports/dependency-graph.json`);
