# Subsystem Definition of Done

A subsystem is NOT complete until:

- [ ] manifest exists
- [ ] manifest validates (`node rad-platform/cli/validate.mjs`)
- [ ] owner identified
- [ ] boundaries documented
- [ ] capabilities declared (provides)
- [ ] consumed capabilities declared
- [ ] contracts versioned / referenced
- [ ] authorization declared on capabilities
- [ ] data ownership documented
- [ ] direct illegal dependencies removed
- [ ] logs / metrics / tracing addressed (or gap documented)
- [ ] readiness / health declared
- [ ] retries / idempotency / DLQ where appropriate
- [ ] configuration and secrets externalized
- [ ] unit / integration / contract / architecture tests as applicable
- [ ] `node rad-platform/cli/gate.mjs` PASS
- [ ] dependency graph updated
- [ ] documentation / evidence generated
