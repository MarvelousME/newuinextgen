# ARCHITECTURE-RISKS

**Last Updated:** 2026-09-16

| Risk | Severity | Blast radius | Mitigation | Status |
|------|----------|--------------|------------|--------|
| Automation Hub vs Companion duplication | HIGH | finance, matching, dashboards | Quiet-domain when `NGC_Plugin` active | Mitigated (TD-RAD-004) |
| Dual theme trees (root overlays + BeyondInfinity) | MEDIUM | UI drift | Canonical edit root = BeyondInfinity; see THEME-TUTORFABULOUS.md | Documented |
| Missing architecture CI | HIGH | silent coupling | `rad-architecture` job: validate + gate | Mitigated |
| God-sized Companion module list | MEDIUM | change risk | Internal modules + Policy Bridge on core mutations | Partial |
| Incomplete tenant isolation tests | HIGH | multi-tenant data | expand conformance | Open |
| Agent autonomy Level 0–1 | MEDIUM | ops trust | policy + evaluation harness | Ongoing |
| Secrets in defaults / option store only | HIGH | credential leak | compose requires env; vault `env:` + option ciphertext | Mitigated (TD-RAD-005); external SM optional |
| `trusted_system` payment bypass misuse | MEDIUM | unauthorized settle | Restrict to WC hooks / cron / explicit PayFast context | Hardened 2026-09-16 |
| OTEL not full OTLP | LOW | observability gap | `export_span` hook + endpoint env | Partial (TD-RAD-006) |
| matching.propose perm = `read` | MEDIUM | over-broad propose | Tighten to role-specific cap when parent roles finalize | Open |

Prioritize by risk, not convenience. Source of truth for debt IDs: `TECHNICAL-DEBT-REGISTER.md`.
