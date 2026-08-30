import fs from 'node:fs';
import path from 'node:path';

export class AuditLog {
  /** @param {string} filePath */
  constructor(filePath) {
    this.filePath = filePath;
    fs.mkdirSync(path.dirname(filePath), { recursive: true });
  }

  /**
   * @param {object} entry
   */
  append(entry) {
    const line = JSON.stringify({
      ...entry,
      timestamp: entry.timestamp || new Date().toISOString(),
    });
    fs.appendFileSync(this.filePath, line + '\n');
  }

  /** @param {number} [limit] */
  tail(limit = 50) {
    if (!fs.existsSync(this.filePath)) {
      return [];
    }
    const lines = fs.readFileSync(this.filePath, 'utf8').trim().split('\n').filter(Boolean);
    return lines.slice(-limit).map((l) => JSON.parse(l));
  }
}
