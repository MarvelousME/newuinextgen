# 11 — Functional Capability Matrix (excerpt)

| Capability | Status | Evidence | Risk | Remediation | Tests |
| ---------- | ------ | -------- | ---- | ----------- | ----- |
| Tutor matching | PARTIAL | `NGC_Matching`, Hub matching | Medium | Unify scoring weights | Companion unit PARTIAL |
| Payments PayFast ITN | PARTIAL | `class-ngc-payfast-gateway.php` | HIGH | Replay tests NOT VERIFIED this run | PHPUnit exists |
| Payouts | FINANCIAL CONTROL RISK | Dual cron Companion+Hub | HIGH | Disable Hub cron when Companion active | NOT VERIFIED |
| Consent / cookies | PARTIAL | `NGC_Platform_Tracking`, consent_log | Medium | POPIA legal review | Verification checks |
| Fraud engine | PARTIAL | `NGC_Fraud_Engine` (new) | HIGH | Expand rules + UI cases | Policy tests VERIFIED |
| Safeguarding | PARTIAL | `NGC_Safeguarding` (new) | HIGH | Moderator workflows incomplete | Redaction unit pending |
| AI agents (BYOK) | PARTIAL | `NGC_AI_Agents`, `BIA_Policy` | Medium | No autonomous tool loop | Presence checks |
| Agent control plane | PARTIAL | `NGC_Agent_Control_Plane` (new) | Medium | Evaluation suite missing | 5 policy tests VERIFIED |
| RTM / SSE | PARTIAL | Hub RTM | Low | SSE under load NOT VERIFIED | — |
| Dashboards live data | PARTIAL | Hub + Companion bridge | Low | — | Docker prior smoke |
| Git baseline | NOT VERIFIED | No `.git` | Medium | Init git if desired | — |

Full domain matrix continues in Phase 2 backlog (`.agent-audit/15-remediation-backlog.md`).
