import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { createTaskStore } from '../src/task-store.js';

test('durable store survives recreate (id + idempotency)', () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'ngt-gw-store-'));
  const dbPath = path.join(dir, 'tasks.sqlite');

  const store1 = createTaskStore({ dbPath });
  const created = store1.create({
    id: 'task_durable_1',
    idempotency_key: 'idem-durable-abc',
    status: 'submitted',
    message: 'hello',
    created_at: '2026-01-01T00:00:00.000Z',
  });
  store1.update(created.id, { status: 'completed', result: 'ok' });
  store1.close();

  const store2 = createTaskStore({ dbPath });
  const byId = store2.get('task_durable_1');
  const byIdem = store2.getByIdempotency('idem-durable-abc');
  store2.close();

  assert.equal(byId?.id, 'task_durable_1');
  assert.equal(byId?.status, 'completed');
  assert.equal(byId?.result, 'ok');
  assert.equal(byIdem?.id, 'task_durable_1');
  assert.equal(byIdem?.idempotency_key, 'idem-durable-abc');

  fs.rmSync(dir, { recursive: true, force: true });
});

test('in-memory store supports clear and idempotency', () => {
  const store = createTaskStore({ dbPath: ':memory:' });
  store.create({ id: 't1', idempotency_key: 'k1', status: 'submitted' });
  assert.equal(store.getByIdempotency('k1').id, 't1');
  store.clear();
  assert.equal(store.get('t1'), null);
  assert.equal(store.getByIdempotency('k1'), null);
  store.close();
});
