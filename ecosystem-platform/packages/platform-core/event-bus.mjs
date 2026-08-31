import crypto from 'node:crypto';

export class EventBus {
  /** @param {{ audit?: import('./audit-log.mjs').AuditLog }} deps */
  constructor(deps = {}) {
    this.audit = deps.audit;
    this.handlers = new Map();
    this.rabbitUrl = process.env.ECOSYSTEM_RABBITMQ_URL || '';
  }

  /** @param {string} type @param {Function} fn */
  on(type, fn) {
    if (!this.handlers.has(type)) {
      this.handlers.set(type, []);
    }
    this.handlers.get(type).push(fn);
  }

  /**
   * @param {object} event
   */
  async emit(event) {
    const enriched = {
      eventId: event.eventId || crypto.randomUUID(),
      eventType: event.eventType,
      tenantId: event.tenantId || '',
      correlationId: event.correlationId || '',
      causationId: event.causationId || '',
      timestamp: event.timestamp || new Date().toISOString(),
      version: event.version || '1',
      payload: event.payload || {},
    };

    if (this.audit) {
      this.audit.append({
        action: 'event.emit',
        resource: enriched.eventType,
        tenantId: enriched.tenantId,
        correlationId: enriched.correlationId,
        result: 'ok',
        meta: enriched,
      });
    }

    const handlers = this.handlers.get(enriched.eventType) || [];
    for (const fn of handlers) {
      await fn(enriched);
    }
    return enriched;
  }
}
