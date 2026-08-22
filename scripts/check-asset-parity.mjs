/**
 * Fail CI when repo-root assets/ and the WordPress theme assets/ copy diverge.
 * Canonical runtime copy is NextGenTutors-BeyondInfinity/assets/.
 * Compare both directions: extras in either tree fail the gate.
 * Do not delete the root copy until this gate has been green in CI.
 */
import { createHash } from "node:crypto";
import { readdirSync, readFileSync, statSync } from "node:fs";
import { join, relative } from "node:path";
import { fileURLToPath } from "node:url";

const root = fileURLToPath(new URL("..", import.meta.url));
const srcDir = join(root, "assets");
const themeDir = join(root, "NextGenTutors-BeyondInfinity", "assets");

function walk(dir) {
  /** @type {string[]} */
  const files = [];
  for (const name of readdirSync(dir)) {
    const full = join(dir, name);
    const st = statSync(full);
    if (st.isDirectory()) {
      files.push(...walk(full));
    } else {
      files.push(full);
    }
  }
  return files;
}

function md5(path) {
  return createHash("md5").update(readFileSync(path)).digest("hex");
}

/**
 * @param {string} dir
 * @returns {Map<string, string>}
 */
function index(dir) {
  const map = new Map();
  for (const file of walk(dir)) {
    const rel = relative(dir, file).replaceAll("\\", "/");
    map.set(rel, md5(file));
  }
  return map;
}

const src = index(srcDir);
const theme = index(themeDir);
const all = new Set([...src.keys(), ...theme.keys()]);
/** @type {string[]} */
const problems = [];
let diverged = 0;
let missingInTheme = 0;
let missingInRoot = 0;

for (const rel of [...all].sort()) {
  const inSrc = src.has(rel);
  const inTheme = theme.has(rel);
  if (!inSrc) {
    missingInRoot += 1;
    problems.push(`missing in root: ${rel}`);
    continue;
  }
  if (!inTheme) {
    missingInTheme += 1;
    problems.push(`missing in theme: ${rel}`);
    continue;
  }
  if (src.get(rel) !== theme.get(rel)) {
    diverged += 1;
    problems.push(`hash mismatch: ${rel}`);
  }
}

if (problems.length) {
  console.error(
    `Asset parity failed: ${diverged} diverged, ${missingInTheme} missing in theme, ${missingInRoot} missing in root (union ${all.size} files).`
  );
  for (const line of problems.slice(0, 40)) {
    console.error("  " + line);
  }
  if (problems.length > 40) {
    console.error(`  …and ${problems.length - 40} more`);
  }
  process.exit(1);
}

console.log(
  `Asset parity OK: ${all.size} identical files (root assets/ ↔ theme assets/, both directions).`
);
