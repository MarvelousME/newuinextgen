/**
 * Scan repo for Composer / npm manifests and lockfiles (TD-RAD-003).
 * Inventory only — does not resolve the full dependency graph.
 */
import fs from 'node:fs';
import path from 'node:path';

const SKIP_DIR = new Set([
	'node_modules',
	'vendor',
	'.git',
	'dist',
	'build',
	'BACKUPS',
	'elementor',
	'elementor-pro',
	'elementor-pro-activator',
]);

/**
 * @param {string} repoRoot
 * @returns {Array<object>}
 */
export function scanLockfiles(repoRoot) {
	const roots = [
		repoRoot,
		path.join(repoRoot, 'services'),
		path.join(repoRoot, 'e2e'),
		path.join(repoRoot, 'rad-platform'),
		path.join(repoRoot, 'NextGenTutors-Companion'),
		path.join(repoRoot, 'NextGenTutors-AI-Integration'),
		path.join(repoRoot, 'NextGenTutors-BeyondMeasure'),
		path.join(repoRoot, 'ui-library'),
	];

	const found = [];
	const seen = new Set();

	for (const root of roots) {
		if (!fs.existsSync(root)) continue;
		walk(root, repoRoot, 0, found, seen);
	}

	return found.sort((a, b) => a.path.localeCompare(b.path));
}

/**
 * @param {string} dir
 * @param {string} repoRoot
 * @param {number} depth
 * @param {Array<object>} out
 * @param {Set<string>} seen
 */
function walk(dir, repoRoot, depth, out, seen) {
	if (depth > 6) return;
	let entries;
	try {
		entries = fs.readdirSync(dir, { withFileTypes: true });
	} catch {
		return;
	}

	const relDir = path.relative(repoRoot, dir).replace(/\\/g, '/') || '.';

	const pkgJson = path.join(dir, 'package.json');
	const pkgLock = path.join(dir, 'package-lock.json');
	const composerJson = path.join(dir, 'composer.json');
	const composerLock = path.join(dir, 'composer.lock');

	if (fs.existsSync(pkgJson)) {
		pushNpm(repoRoot, relDir, pkgJson, pkgLock, out, seen);
	}
	if (fs.existsSync(composerJson)) {
		pushComposer(repoRoot, relDir, composerJson, composerLock, out, seen);
	}

	for (const ent of entries) {
		if (!ent.isDirectory()) continue;
		if (SKIP_DIR.has(ent.name)) continue;
		if (ent.name.startsWith('.')) continue;
		walk(path.join(dir, ent.name), repoRoot, depth + 1, out, seen);
	}
}

function pushNpm(repoRoot, relDir, pkgJson, pkgLock, out, seen) {
	const key = `npm:${relDir}`;
	if (seen.has(key)) return;
	seen.add(key);
	let name = '';
	let version = '';
	/** @type {string[]} */
	let directDeps = [];
	try {
		const raw = JSON.parse(fs.readFileSync(pkgJson, 'utf8'));
		name = String(raw.name || path.basename(relDir === '.' ? repoRoot : relDir));
		version = String(raw.version || '');
		directDeps = [
			...Object.keys(raw.dependencies || {}),
			...Object.keys(raw.devDependencies || {}),
		].sort();
	} catch {
		name = path.basename(relDir);
	}
	out.push({
		path: relDir === '.' ? 'package.json' : `${relDir}/package.json`,
		ecosystem: 'npm',
		name,
		version,
		directDeps,
		lockPresent: fs.existsSync(pkgLock),
		lockPath: fs.existsSync(pkgLock)
			? relDir === '.'
				? 'package-lock.json'
				: `${relDir}/package-lock.json`
			: null,
	});
}

function pushComposer(repoRoot, relDir, composerJson, composerLock, out, seen) {
	const key = `composer:${relDir}`;
	if (seen.has(key)) return;
	seen.add(key);
	let name = '';
	/** @type {string[]} */
	let directDeps = [];
	try {
		const raw = JSON.parse(fs.readFileSync(composerJson, 'utf8'));
		name = String(raw.name || path.basename(relDir));
		directDeps = [
			...Object.keys(raw.require || {}).filter((k) => k !== 'php'),
			...Object.keys(raw['require-dev'] || {}),
		].sort();
	} catch {
		name = path.basename(relDir);
	}
	out.push({
		path: relDir === '.' ? 'composer.json' : `${relDir}/composer.json`,
		ecosystem: 'composer',
		name,
		version: '',
		directDeps,
		lockPresent: fs.existsSync(composerLock),
		lockPath: fs.existsSync(composerLock)
			? relDir === '.'
				? 'composer.lock'
				: `${relDir}/composer.lock`
			: null,
	});
}
