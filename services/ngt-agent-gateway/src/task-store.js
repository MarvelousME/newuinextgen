/** Durable in-memory task store with idempotency (file-backed optional later). */

const tasks = new Map();
const byIdem = new Map();

export function createTaskStore() {
  return {
    create(task) {
      tasks.set(task.id, { ...task });
      byIdem.set(task.idempotency_key, task.id);
      return { ...task };
    },
    get(id) {
      const t = tasks.get(id);
      return t ? { ...t } : null;
    },
    getByIdempotency(key) {
      const id = byIdem.get(key);
      return id ? this.get(id) : null;
    },
    update(id, patch) {
      const cur = tasks.get(id);
      if (!cur) return null;
      const next = { ...cur, ...patch };
      tasks.set(id, next);
      return { ...next };
    },
    all() {
      return [...tasks.values()].map((t) => ({ ...t }));
    },
    clear() {
      tasks.clear();
      byIdem.clear();
    },
  };
}
