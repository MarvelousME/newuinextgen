import fs from 'node:fs';
import path from 'node:path';

export function readJson(file) {
  return JSON.parse(fs.readFileSync(file, 'utf8'));
}

export function writeJson(file, data) {
  fs.mkdirSync(path.dirname(file), { recursive: true });
  fs.writeFileSync(file, JSON.stringify(data, null, 2) + '\n', 'utf8');
}

export function writeText(file, text) {
  fs.mkdirSync(path.dirname(file), { recursive: true });
  fs.writeFileSync(file, text.endsWith('\n') ? text : text + '\n', 'utf8');
}

export function listJsonFiles(dir) {
  if (!fs.existsSync(dir)) return [];
  return fs
    .readdirSync(dir)
    .filter((f) => f.endsWith('.json'))
    .map((f) => path.join(dir, f))
    .sort();
}

export function loadManifests(manifestsDir) {
  return listJsonFiles(manifestsDir).map((file) => ({
    file,
    data: readJson(file),
  }));
}

export function loadCapabilities(capabilitiesDir) {
  const caps = [];
  if (!fs.existsSync(capabilitiesDir)) return caps;
  for (const file of listJsonFiles(capabilitiesDir)) {
    const data = readJson(file);
    if (Array.isArray(data)) {
      for (const item of data) caps.push({ file, data: item });
    } else if (data.capabilities && Array.isArray(data.capabilities)) {
      for (const item of data.capabilities) caps.push({ file, data: item });
    } else {
      caps.push({ file, data });
    }
  }
  return caps;
}

export function loadDependencyRules(dir) {
  const file = path.join(dir, 'edges.json');
  if (!fs.existsSync(file)) {
    return { allow: [], deny: [], file: null };
  }
  return { ...readJson(file), file };
}
