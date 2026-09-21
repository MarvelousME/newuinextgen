/**
 * Durable SQLite task store (node:sqlite DatabaseSync) with idempotency keys.
 * Survives process restart when backed by a file under NGT_GATEWAY_DATA_DIR.
 */

import fs from 'node:fs';
import path from 'node:path';
import { DatabaseSync } from 'node:sqlite';

function resolveDbPath(options = {}) {
  if (options.dbPath) return options.dbPath;
  if (process.env.NGT_GATEWAY_SQLITE_PATH) return process.env.NGT_GATEWAY_SQLITE_PATH;
  const dataDir =
    options.dataDir || process.env.NGT_GATEWAY_DATA_DIR || path.join(process.cwd(), 'data', 'gateway');
  return path.join(dataDir, 'tasks.sqlite');
}

function rowToTask(row) {
  if (!row?.doc) return null;
  try {
    return JSON.parse(row.doc);
  } catch {
    return null;
  }
}

/**
 * @param {{ dataDir?: string, dbPath?: string }} [options]
 */
export function createTaskStore(options = {}) {
  const dbPath = resolveDbPath(options);
  if (dbPath !== ':memory:') {
    fs.mkdirSync(path.dirname(path.resolve(dbPath)), { recursive: true });
  }

  const db = new DatabaseSync(dbPath);
  db.exec(`
    PRAGMA journal_mode = WAL;
    CREATE TABLE IF NOT EXISTS tasks (
      id TEXT PRIMARY KEY NOT NULL,
      idempotency_key TEXT NOT NULL UNIQUE,
      doc TEXT NOT NULL
    );
  `);

  const insertStmt = db.prepare(
    'INSERT INTO tasks (id, idempotency_key, doc) VALUES (?, ?, ?)',
  );
  const getByIdStmt = db.prepare('SELECT doc FROM tasks WHERE id = ?');
  const getByIdemStmt = db.prepare('SELECT doc FROM tasks WHERE idempotency_key = ?');
  const updateStmt = db.prepare(
    'UPDATE tasks SET idempotency_key = ?, doc = ? WHERE id = ?',
  );
  const allStmt = db.prepare('SELECT doc FROM tasks');

  return {
    dbPath,
    create(task) {
      const doc = { ...task };
      insertStmt.run(doc.id, doc.idempotency_key, JSON.stringify(doc));
      return { ...doc };
    },
    get(id) {
      return rowToTask(getByIdStmt.get(id));
    },
    getByIdempotency(key) {
      return rowToTask(getByIdemStmt.get(key));
    },
    update(id, patch) {
      const cur = this.get(id);
      if (!cur) return null;
      const next = { ...cur, ...patch };
      updateStmt.run(next.idempotency_key, JSON.stringify(next), id);
      return { ...next };
    },
    all() {
      return allStmt.all().map((row) => rowToTask(row)).filter(Boolean);
    },
    clear() {
      db.exec('DELETE FROM tasks');
    },
    close() {
      db.close();
    },
  };
}
