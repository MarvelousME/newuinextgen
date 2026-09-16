#!/usr/bin/env php
<?php
/**
 * Fail CI/local checks when known-bad Docker secret defaults reappear,
 * or when docker/.env is tracked by git.
 *
 * Usage (from newuinextgen root):
 *   php scripts/check-no-default-secrets.php
 *
 * Exit 0 = clean, 1 = violations found.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$failed = false;

function fail(string $message): void {
	global $failed;
	$failed = true;
	fwrite(STDERR, "FAIL: {$message}\n");
}

function ok(string $message): void {
	fwrite(STDOUT, "OK: {$message}\n");
}

/**
 * Patterns that must not appear in tracked compose / .env.example files.
 * MYSQL_USER=wordpress and MYSQL_DATABASE=wordpress are allowed (non-secret names).
 */
$forbidden = array(
	array(
		'label' => 'MYSQL_PASSWORD default wordpress',
		'regex' => '/MYSQL_PASSWORD\s*[:=]\s*["\']?wordpress["\']?|\$\{MYSQL_PASSWORD:-wordpress\}/i',
	),
	array(
		'label' => 'MYSQL_ROOT_PASSWORD default rootpass',
		'regex' => '/MYSQL_ROOT_PASSWORD\s*[:=]\s*["\']?rootpass["\']?|\$\{MYSQL_ROOT_PASSWORD:-rootpass\}|-prootpass\b/i',
	),
	array(
		'label' => 'staging-local-secret',
		'regex' => '/staging-local-secret/',
	),
	array(
		'label' => 'WORDPRESS_DB_PASSWORD / PMA_PASSWORD compose fallback wordpress',
		'regex' => '/(?:WORDPRESS_DB_PASSWORD|PMA_PASSWORD):\s*\$\{MYSQL_PASSWORD:-wordpress\}/',
	),
);

$scanPaths = array(
	$root . DIRECTORY_SEPARATOR . 'docker' . DIRECTORY_SEPARATOR . 'docker-compose.yml',
	$root . DIRECTORY_SEPARATOR . 'docker' . DIRECTORY_SEPARATOR . '.env.example',
);

$siblings = array(
	dirname($root) . DIRECTORY_SEPARATOR . 'nextgen-tutors' . DIRECTORY_SEPARATOR . 'docker' . DIRECTORY_SEPARATOR . '.env.example',
	dirname($root) . DIRECTORY_SEPARATOR . 'agntix' . DIRECTORY_SEPARATOR . 'docker' . DIRECTORY_SEPARATOR . '.env.example',
);
foreach ($siblings as $sib) {
	if (is_file($sib)) {
		$scanPaths[] = $sib;
	}
}

foreach ($scanPaths as $path) {
	if (!is_file($path)) {
		fail('Missing expected file: ' . $path);
		continue;
	}
	$contents = file_get_contents($path);
	if ($contents === false) {
		fail('Cannot read: ' . $path);
		continue;
	}
	$fileOk = true;
	foreach ($forbidden as $rule) {
		if (preg_match($rule['regex'], $contents)) {
			fail($path . ': forbidden pattern (' . $rule['label'] . ')');
			$fileOk = false;
		}
	}
	if ($fileOk) {
		ok($path);
	}
}

/**
 * Resolve git top-level if available.
 */
function git_toplevel(string $start): ?string {
	$out = array();
	$code = 0;
	exec('git -C ' . escapeshellarg($start) . ' rev-parse --show-toplevel 2>&1', $out, $code);
	if ($code !== 0 || $out === array()) {
		return null;
	}
	return rtrim($out[0]);
}

function git_is_tracked(string $repo, string $relPath): bool {
	$out = array();
	$code = 0;
	exec(
		'git -C ' . escapeshellarg($repo) . ' ls-files --error-unmatch -- ' . escapeshellarg($relPath) . ' 2>&1',
		$out,
		$code
	);
	return $code === 0;
}

$repo = git_toplevel($root);
if ($repo === null) {
	$repo = git_toplevel(dirname($root));
}

if ($repo === null) {
	ok('git not available or not a repo — skipped tracked-.env check');
} else {
	$candidates = array(
		'newuinextgen/docker/.env',
		'docker/.env',
	);
	$trackedHit = false;
	foreach ($candidates as $rel) {
		$abs = $repo . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
		if (!is_file($abs)) {
			continue;
		}
		if (git_is_tracked($repo, $rel)) {
			fail('Tracked secret file must be untracked: ' . $rel);
			$trackedHit = true;
			break;
		}
	}
	if (!$trackedHit) {
		ok('docker/.env is not tracked by git (or absent from index)');
	}
}

$gitignore = $root . DIRECTORY_SEPARATOR . 'docker' . DIRECTORY_SEPARATOR . '.gitignore';
if (!is_file($gitignore)) {
	fail('docker/.gitignore missing');
} else {
	$gi = file_get_contents($gitignore);
	if ($gi === false || !preg_match('/^\s*\.env\s*$/m', $gi)) {
		fail('docker/.gitignore must list .env');
	} else {
		ok('docker/.gitignore lists .env');
	}
}

if ($failed) {
	fwrite(STDERR, "\ncheck-no-default-secrets: FAILED\n");
	exit(1);
}

fwrite(STDOUT, "\ncheck-no-default-secrets: PASSED\n");
exit(0);
