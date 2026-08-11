# DUPLICATION-REPORT

| Area | Duplicates | Recommendation |
|------|------------|----------------|
| Matching / scoring | Companion + Automation Hub | Single capability `matching.score` in Companion |
| Payouts / finance cron | Companion + Hub | Disable Hub when Companion authority ON |
| Admin consoles | Companion Platform Kernel, Mission Control, Plugin Manager, Hub | Attach RAD views to Platform Kernel only |
| Theme trees | Repo root theme files + BeyondInfinity | Canonical BeyondInfinity |
| Capability catalogues | Authz matrix, tool gateway, admin catalog, agent registry | Unify behind `NGC_Capability_Registry` |
| Policy | Agent policy + BIA_Policy + AI gate | Bridge via `NGC_Policy_Bridge`; do not fork |

Intentional duplication must be documented in ADRs.
