#!/usr/bin/env node
/**
 * Phase 14 automation evidence export — writes audit evidence without requiring
 * a live WordPress stack. For full demo journey evidence use:
 *   wp ngc demo_export_evidence
 *
 * Usage: node scripts/phase14-export-evidence.mjs
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');
const evidenceRoot = path.join(root, '.agent-audit', 'evidence', 'demo');
const journeyId = 'audit-automation-20260830';
const journeyDir = path.join(evidenceRoot, journeyId);

function run(cmd, args, cwd = root) {
  const r = spawnSync(cmd, args, { cwd, encoding: 'utf8', shell: process.platform === 'win32' });
  return {
    ok: r.status === 0,
    status: r.status ?? 1,
    stdout: (r.stdout || '').trim(),
    stderr: (r.stderr || '').trim(),
  };
}

fs.mkdirSync(journeyDir, { recursive: true });

const checks = [];

const gate = run('node', ['rad-platform/cli/gate.mjs']);
checks.push({
  name: 'rad_gate',
  command: 'node rad-platform/cli/gate.mjs',
  ok: gate.ok,
  output: gate.stdout.split('\n').slice(-3).join('\n'),
});

const ecosystemTest = run('npm', ['test'], path.join(root, 'ecosystem-platform'));
checks.push({
  name: 'ecosystem_platform_tests',
  command: 'npm test (ecosystem-platform)',
  ok: ecosystemTest.ok,
  output: ecosystemTest.stdout.split('\n').slice(-5).join('\n'),
});

const pluginMgrTest = run('php', [
  path.join(root, 'NextGenTutors-Plugin-Manager', 'tests', 'run.php'),
]);
checks.push({
  name: 'plugin_manager_tests',
  command: 'php NextGenTutors-Plugin-Manager/tests/run.php',
  ok: pluginMgrTest.ok,
  output: pluginMgrTest.stdout.split('\n').slice(-5).join('\n'),
});

const publishWorker = run('php', [
  path.join(root, 'NextGenTutors-Companion', 'tests', 'publish-worker-restart.php'),
]);
checks.push({
  name: 'publish_worker_tests',
  command: 'php NextGenTutors-Companion/tests/publish-worker-restart.php',
  ok: publishWorker.ok,
  output: publishWorker.stdout.split('\n').slice(-5).join('\n'),
});

const biSecurity = fs.existsSync(
  path.join(root, 'NextGenTutors-BeyondInfinity', 'inc', 'security.php')
);
checks.push({
  name: 'bi_package_security_inc',
  command: 'file exists NextGenTutors-BeyondInfinity/inc/security.php',
  ok: biSecurity,
  output: biSecurity ? 'present' : 'missing — run node scripts/sync-beyondinfinity-theme.mjs',
});

const allOk = checks.every((c) => c.ok);
const now = new Date().toISOString();

const pack = {
  journey_id: journeyId,
  journey_version: '2026-08-30-audit-pass',
  demo_user: 'system',
  start_timestamp: now,
  end_timestamp: now,
  initial_state: { demo_mode: false, audit_reference: 'CODEBASE-AUDIT-SWOT-20260830.md' },
  steps_executed: [
    { action: 'run_rad_gate' },
    { action: 'run_ecosystem_tests' },
    { action: 'run_plugin_manager_tests' },
    { action: 'run_publish_worker_tests' },
    { action: 'verify_bi_package_integrity' },
  ],
  commands_or_api_calls: checks.map((c) => c.command),
  test_result: allOk ? 'PASS' : 'FAIL',
  failure_details: checks.filter((c) => !c.ok).map((c) => ({ name: c.name, output: c.output })),
  checks,
  final_state: {
    phase_14_status: 'COMPLETE WITH LIMITATIONS',
    production_ready: false,
    ecosystem_platform: 'STAGING READY',
  },
  verify: { ok: allOk, checks: checks.length, passed: checks.filter((c) => c.ok).length },
};

const evidencePath = path.join(journeyDir, 'evidence.json');
fs.writeFileSync(evidencePath, JSON.stringify(pack, null, 2));

// all-journeys summary
const allDir = path.join(evidenceRoot, 'all-journeys');
fs.mkdirSync(allDir, { recursive: true });
fs.writeFileSync(path.join(allDir, 'evidence.json'), JSON.stringify(
  {
    journey_id: 'all-journeys',
    end_timestamp: now,
    test_result: allOk ? 'PASS' : 'FAIL',
    automation_export: journeyId,
    checks,
  },
  null,
  2
));

const indexPath = path.join(evidenceRoot, 'INDEX.md');
const line = `| \`${journeyId}\` | ${pack.test_result} | ${now} |\n`;
if (!fs.existsSync(indexPath)) {
  fs.writeFileSync(
    indexPath,
    '# Demo evidence index\n\n| Journey | Result | Exported |\n|---------|--------|----------|\n' + line
  );
} else if (!fs.readFileSync(indexPath, 'utf8').includes(journeyId)) {
  fs.appendFileSync(indexPath, line);
}

fs.writeFileSync(
  path.join(journeyDir, 'README.md'),
  `# Evidence: ${journeyId}\n\nAutomated audit evidence export (${now}).\n\nResult: **${pack.test_result}**\n`
);

console.log(`EVIDENCE_OK path=${evidencePath} result=${pack.test_result}`);
process.exit(allOk ? 0 : 1);
