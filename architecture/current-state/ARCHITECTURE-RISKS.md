# ARCHITECTURE-RISKS

| Risk | Severity | Blast radius | Mitigation |
|------|----------|--------------|------------|
| Automation Hub vs Companion duplication | HIGH | finance, matching, dashboards | Strangler; prefer Companion |
| Dual theme trees (root + BeyondInfinity) | MEDIUM | UI drift | Canonical BeyondInfinity path |
| Missing architecture CI (historical) | HIGH | silent coupling | RAD gate in CI |
| God-sized Companion module list | MEDIUM | change risk | future capability extraction |
| Incomplete tenant isolation tests | HIGH | multi-tenant data | expand conformance |
| Agent autonomy Level 0–1 | MEDIUM | ops trust | policy + evaluation harness |

Prioritize by risk, not convenience.
