import fs from 'node:fs';
import path from 'node:path';
import yaml from 'yaml';

export class BlueprintRegistry {
  /** @param {string} dir */
  constructor(dir) {
    this.dir = dir;
    this._cache = null;
  }

  _loadAll() {
    if (this._cache) {
      return this._cache;
    }
    const map = {};
    if (!fs.existsSync(this.dir)) {
      this._cache = map;
      return map;
    }
    for (const file of fs.readdirSync(this.dir)) {
      if (!file.endsWith('.yaml') && !file.endsWith('.yml')) {
        continue;
      }
      const raw = fs.readFileSync(path.join(this.dir, file), 'utf8');
      const doc = yaml.parse(raw);
      if (doc?.id) {
        map[doc.id] = doc;
      }
    }
    this._cache = map;
    return map;
  }

  list() {
    return Object.values(this._loadAll());
  }

  /** @param {string} id */
  get(id) {
    return this._loadAll()[id] || null;
  }
}
