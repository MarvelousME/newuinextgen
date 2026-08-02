/**
 * Database evidence capture for E2E CRUD verification.
 * Executes real SQL against the Docker MySQL container and writes
 * timestamped, executable .sql extract files (query + result dump).
 */
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

export type DbEvidenceContext = {
  testCase: string;
  entity: string;
  recordId?: string | number;
  tenant?: string;
  user?: string;
  correlationId?: string;
};

const DEFAULT_CONTAINER = process.env.E2E_DB_CONTAINER || 'newuinextgen-db-1';
const DEFAULT_DB = process.env.E2E_DB_NAME || 'wordpress';
const DEFAULT_USER = process.env.E2E_DB_USER || 'wordpress';
const DEFAULT_PASS = process.env.E2E_DB_PASSWORD || 'wordpress';

function evidenceDir(ctx: DbEvidenceContext): string {
  const stamp = new Date().toISOString().replace(/[:.]/g, '-');
  const safe = `${ctx.entity}-${ctx.testCase}`.replace(/[^a-zA-Z0-9._-]+/g, '_');
  const dir = path.join(
    process.cwd(),
    '..',
    '.agent-audit',
    'evidence',
    'sql',
    stamp.slice(0, 10),
    `${safe}_${stamp}`
  );
  fs.mkdirSync(dir, { recursive: true });
  return dir;
}

function writeMeta(dir: string, ctx: DbEvidenceContext, phase: string) {
  const meta = {
    generated_at: new Date().toISOString(),
    environment: process.env.BASE_URL || 'http://localhost:8900',
    database: DEFAULT_DB,
    container: DEFAULT_CONTAINER,
    phase,
    ...ctx,
  };
  fs.writeFileSync(path.join(dir, 'META.json'), JSON.stringify(meta, null, 2), 'utf8');
}

/**
 * Run a SQL statement via docker exec mysql and return stdout.
 */
export function runSql(sql: string): string {
  const args = [
    'exec',
    '-i',
    DEFAULT_CONTAINER,
    'mysql',
    '-u',
    DEFAULT_USER,
    `-p${DEFAULT_PASS}`,
    DEFAULT_DB,
    '-e',
    sql,
  ];
  try {
    return execFileSync('docker', args, {
      encoding: 'utf8',
      maxBuffer: 10 * 1024 * 1024,
      windowsHide: true,
    });
  } catch (err) {
    const e = err as { stdout?: string; stderr?: string; message?: string };
    const detail = [e.stdout, e.stderr, e.message].filter(Boolean).join('\n');
    throw new Error(`SQL evidence failed: ${detail}`);
  }
}

/**
 * Capture a named SQL extract: writes executable .sql with query + tabulated results.
 */
export function captureSqlExtract(
  ctx: DbEvidenceContext,
  fileName: string,
  sql: string,
  dir?: string
): { dir: string; file: string; output: string } {
  const outDir = dir || evidenceDir(ctx);
  writeMeta(outDir, ctx, fileName.replace(/\.sql$/i, ''));
  const output = runSql(sql);
  const body = [
    `-- NextGen Tutors E2E SQL Evidence`,
    `-- Generated: ${new Date().toISOString()}`,
    `-- Test Case: ${ctx.testCase}`,
    `-- Entity: ${ctx.entity}`,
    `-- Record ID: ${ctx.recordId ?? 'n/a'}`,
    `-- Tenant: ${ctx.tenant ?? 'default'}`,
    `-- User: ${ctx.user ?? 'n/a'}`,
    `-- Correlation: ${ctx.correlationId ?? 'n/a'}`,
    `-- Database: ${DEFAULT_DB}`,
    `-- Environment: ${process.env.BASE_URL || 'http://localhost:8900'}`,
    ``,
    `-- === QUERY ===`,
    sql.trim().endsWith(';') ? sql.trim() : `${sql.trim()};`,
    ``,
    `-- === RESULT ===`,
    ...output.split(/\r?\n/).map((line) => `-- ${line}`),
    ``,
  ].join('\n');
  const file = path.join(outDir, fileName);
  fs.writeFileSync(file, body, 'utf8');
  // Also store raw TSV for machine consumption.
  fs.writeFileSync(path.join(outDir, fileName.replace(/\.sql$/i, '.tsv')), output, 'utf8');
  return { dir: outDir, file, output };
}

export function captureCrudPair(
  ctx: DbEvidenceContext,
  operation: 'Insert' | 'Update' | 'Delete',
  beforeSql: string,
  afterSql: string,
  extras?: Partial<Record<'Verification' | 'Audit' | 'Ledger' | 'Relationship' | 'History', string>>
) {
  const dir = evidenceDir(ctx);
  captureSqlExtract(ctx, `Before_${operation}.sql`, beforeSql, dir);
  captureSqlExtract(ctx, `After_${operation}.sql`, afterSql, dir);
  if (extras) {
    for (const [name, sql] of Object.entries(extras)) {
      if (sql) captureSqlExtract(ctx, `${name}.sql`, sql, dir);
    }
  }
  return dir;
}

/** Common WP / NGC table probes used by enterprise verification. */
export const PROBES = {
  users: (like: string) =>
    `SELECT ID, user_login, user_email, user_registered FROM wp_users WHERE user_email LIKE '${like.replace(/'/g, "''")}' OR user_login LIKE '${like.replace(/'/g, "''")}' ORDER BY ID DESC LIMIT 20;`,
  usermeta: (userId: number) =>
    `SELECT umeta_id, user_id, meta_key, LEFT(meta_value, 200) AS meta_value FROM wp_usermeta WHERE user_id = ${Number(userId)} ORDER BY umeta_id DESC LIMIT 50;`,
  posts: (like: string) =>
    `SELECT ID, post_title, post_type, post_status, post_date FROM wp_posts WHERE post_title LIKE '${like.replace(/'/g, "''")}' OR post_name LIKE '${like.replace(/'/g, "''")}' ORDER BY ID DESC LIMIT 20;`,
  options: (like: string) =>
    `SELECT option_id, option_name, LEFT(option_value, 300) AS option_value FROM wp_options WHERE option_name LIKE '${like.replace(/'/g, "''")}' LIMIT 40;`,
  audit: () =>
    `SHOW TABLES LIKE 'wp_ngc%audit%'; SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name LIKE '%audit%' LIMIT 20;`,
  demoSeed: () =>
    `SELECT option_name, LEFT(option_value, 500) AS option_value FROM wp_options WHERE option_name IN ('ngc_demo_seed_status','ngc_demo_mode','ngc_demo_seed_graph') LIMIT 10;`,
  tableList: () =>
    `SELECT table_name, table_rows FROM information_schema.tables WHERE table_schema = DATABASE() AND (table_name LIKE 'wp_ngc%' OR table_name LIKE 'wp_amelia%' OR table_name LIKE 'wp_fluent%') ORDER BY table_name;`,
};
