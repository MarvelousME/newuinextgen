#!/usr/bin/env node
/**
 * Cross-platform theme package sync — copies monorepo root overlays into
 * NextGenTutors-BeyondInfinity/ so the BI package boots without Docker binds.
 *
 * Usage: node scripts/sync-beyondinfinity-theme.mjs
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');
const themeDir = path.join(root, 'NextGenTutors-BeyondInfinity');

const dirCopies = [
  'inc',
  'assets',
  'templates',
  'template-parts',
  'page-templates',
  'prototypes',
  'content',
  'automations',
  'tests',
];

const rootFiles = [
  'style.css',
  'functions.php',
  'header.php',
  'footer.php',
  'index.php',
  'front-page.php',
  'home.php',
  'page.php',
  'single.php',
  'single-tutors.php',
  'archive.php',
  'archive-tutors.php',
  'search.php',
  'searchform.php',
  'comments.php',
  '404.php',
  'admin-dashboard.php',
  'screenshot.png',
];

function copyRecursive(src, dst) {
  if (!fs.existsSync(src)) {
    return { copied: 0, skipped: 1 };
  }
  fs.mkdirSync(dst, { recursive: true });
  let copied = 0;
  for (const ent of fs.readdirSync(src, { withFileTypes: true })) {
    const s = path.join(src, ent.name);
    const d = path.join(dst, ent.name);
    if (ent.isDirectory()) {
      copied += copyRecursive(s, d).copied;
    } else if (ent.isFile()) {
      fs.copyFileSync(s, d);
      copied += 1;
    }
  }
  return { copied };
}

function copyFile(src, dst) {
  if (!fs.existsSync(src)) {
    return false;
  }
  fs.mkdirSync(path.dirname(dst), { recursive: true });
  fs.copyFileSync(src, dst);
  return true;
}

fs.mkdirSync(themeDir, { recursive: true });

let totalCopied = 0;
for (const dir of dirCopies) {
  const src = path.join(root, dir);
  const dst = path.join(themeDir, dir);
  if (!fs.existsSync(src)) {
    console.log(`SKIP missing dir ${dir}`);
    continue;
  }
  const { copied } = copyRecursive(src, dst);
  totalCopied += copied;
  console.log(`COPY dir ${dir} (${copied} files)`);
}

for (const name of fs.readdirSync(root)) {
  if (name.startsWith('page-') && name.endsWith('.php')) {
    rootFiles.push(name);
  }
}

for (const file of [...new Set(rootFiles)]) {
  const src = path.join(root, file);
  const dst = path.join(themeDir, file);
  if (copyFile(src, dst)) {
    totalCopied += 1;
    console.log(`COPY file ${file}`);
  }
}

const marker = path.join(themeDir, '.ngt-theme-package');
fs.writeFileSync(
  marker,
  [
    '# Packaged theme root for Docker / distribution',
    '# Source of truth: monorepo root theme files.',
    `# Synced: ${new Date().toISOString()}`,
    '# Generator: scripts/sync-beyondinfinity-theme.mjs',
  ].join('\n') + '\n'
);

console.log(`THEME_PACKAGE=${themeDir}`);
console.log(`SYNC_OK files_copied=${totalCopied}`);
