# Non-Functional Requirements Specification (NFRS)

## NFR Matrix

| NFR Domain | Requirement | Implementation Evidence | Status |
|---|---|---|---|
| Security | nonce + RBAC + sanitization + escaping | security module + REST checks | VERIFIED |
| Privacy | consent-aware tracking + public data minimization | tracking + public calendar shaping | VERIFIED |
| Reliability | verification + self-healing + workflow retries | verification/self-healing/retry queue | VERIFIED |
| Maintainability | plugin/theme boundary + adapter architecture | architecture docs + code layout | VERIFIED |
| Observability | audit logs + workflow logs + health reports | audit/workflow/admin screens | VERIFIED |
| Extensibility | modular services/repositories/adapters | `includes/` architecture | VERIFIED |
| Performance | indexed custom tables and scoped queries | db schema + repository patterns | PARTIAL |
| Portability | plugin-owned data layer | implementation motivations | VERIFIED |
| Compliance | privacy/consent logging | consent log + policy controls | PARTIAL |
| Availability | cron-based checks and recoveries | health + repair actions | PARTIAL |

