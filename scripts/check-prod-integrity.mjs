#!/usr/bin/env node
/**
 * Production-integrity static checks (no fake ITN / no prod claim).
 * Exit 0 only when agent-closable integrity invariants hold.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const fails = [];
const passes = [];

function read(rel) {
  return fs.readFileSync(path.join(root, rel), 'utf8');
}

function assert(name, ok, detail) {
  if (ok) passes.push(name);
  else fails.push(`${name}: ${detail}`);
}

const ci = read('.github/workflows/ci.yml');
assert(
  'ci_no_hardcoded_8900',
  !/\b8900\b/.test(ci),
  'ci.yml must not wait on :8900'
);
assert(
  'ci_uses_wp_port_or_8890',
  /WP_PORT/.test(ci) && /8890/.test(ci),
  'ci.yml must derive wait/BASE_URL from WP_PORT (default 8890)'
);

const envExample = read('docker/.env.example');
assert(
  'env_example_wp_port_8890',
  /^WP_PORT=8890\s*$/m.test(envExample),
  'docker/.env.example WP_PORT must be 8890'
);
assert(
  'env_example_documents_prod_overlay',
  /docker-compose\.production\.yml/.test(envExample),
  '.env.example must document production overlay'
);

const prodCompose = read('docker/docker-compose.production.yml');
assert(
  'prod_compose_demo_seed_false',
  /NGC_ALLOW_DEMO_SEED:\s*"0"/.test(prodCompose) && /define\('NGC_ALLOW_DEMO_SEED',\s*false\)/.test(prodCompose),
  'production overlay must force NGC_ALLOW_DEMO_SEED off'
);
assert(
  'prod_compose_wp_env_production',
  /WP_ENVIRONMENT_TYPE:\s*production/.test(prodCompose),
  'production overlay must set WP_ENVIRONMENT_TYPE=production'
);

const compose = read('docker/docker-compose.yml');
assert(
  'compose_maps_wp_port',
  /\$\{WP_PORT:-8890\}:80/.test(compose),
  'compose must publish ${WP_PORT:-8890}:80'
);
assert(
  'compose_has_prometheus',
  /prometheus:/.test(compose) && /NGC_METRICS_TOKEN/.test(compose),
  'compose must include prometheus + NGC_METRICS_TOKEN'
);
assert(
  'compose_has_smtp_env',
  /SMTP_HOST/.test(compose),
  'compose must wire SMTP_* env'
);

const mu = read('docker/mu-plugins/ngt-environment.php');
assert(
  'mu_forbids_demo_on_production',
  /ngt_mu_demo_seed_forbidden/.test(mu) && /production/.test(mu),
  'mu-plugin must forbid demo seed on production'
);

const hubDelegate = read('nextgen-automation-hub/includes/class-ngt-hub-companion-delegate.php');
assert(
  'hub_domain_writes_blocked',
  /domain_writes_blocked/.test(hubDelegate),
  'Hub delegate must expose domain_writes_blocked()'
);

const hubMatch = read('nextgen-automation-hub/includes/class-ngt-hub-matching.php');
assert(
  'hub_match_blocks_local_when_companion',
  /domain_writes_blocked/.test(hubMatch) && /ngt_match_companion_authority/.test(hubMatch),
  'Hub matching must refuse local SoR writes when Companion owns domain'
);

const hubPayout = read('nextgen-automation-hub/includes/class-ngt-hub-payouts.php');
assert(
  'hub_payout_skips_when_companion',
  /Companion owns payout/.test(hubPayout),
  'Hub payouts must skip when Companion present'
);

// Drift hunters for operator scripts (defaults must be 8890).
for (const rel of [
  'docker/start.ps1',
  'docker/init/setup.sh',
  'scripts/run-playwright.ps1',
  'docker/scripts/apply-defaults.ps1',
  'docker/scripts/install-registry-zips.ps1',
]) {
  const body = read(rel);
  assert(
    `no_8900_default_${rel.replace(/[\\/]/g, '_')}`,
    !/\b8900\b/.test(body),
    `${rel} still references :8900`
  );
}

console.log(`prod-integrity: ${passes.length} PASS, ${fails.length} FAIL`);
for (const p of passes) console.log(`  PASS ${p}`);
for (const f of fails) console.log(`  FAIL ${f}`);

if (fails.length) {
  process.exit(1);
}
