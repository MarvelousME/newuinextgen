# Troubleshooting Guide

## Issue Matrix

| Issue | Symptom | Likely Cause | Diagnostic Steps | Recovery | Status |
|---|---|---|---|---|---|
| Login issues | users cannot sign in | credential/reset/session mismatch | verify WP users + reset flow | reset password, clear cache | VERIFIED |
| Registration issues | parent/student/tutor forms fail | nonce/validation/user conflict | inspect form queue + logs | correct payload, retry | VERIFIED |
| Tutor approval failures | approve action errors | missing mapped WP user | inspect application email vs users | create/map user then approve | VERIFIED |
| Amelia failures | calendar sync unavailable | plugin/API key missing | Amelia status screen + verify | configure key or use internal fallback | PARTIAL |
| MasterStudy failures | no LMS mapping | plugin unavailable | verification dashboard | install plugin/configure | BLOCKED |
| FluentCRM failures | contact sync missing | plugin unavailable/method mismatch | workflow logs + CRM status | install/upgrade plugin | BLOCKED |
| Payment failures | payment hooks inactive | WooCommerce absent | admin notices + logs | install/configure Woo | PARTIAL |
| Booking failures | slot rejected/conflict | overlapping booking | check booking conflict guard | choose different slot | VERIFIED |
| Email failures | no delivery | mail transport issue | test send + workflow logs | SMTP config/retry queue | PARTIAL |
| Analytics failures | empty metrics | low/no events or tracking disabled | platform analytics + consent settings | enable tracking/seed demo | VERIFIED |
| Dashboard failures | dashboard shows error | REST route/config mismatch | route verification + console | fix namespace/path/config | VERIFIED |
| Verification failures | checks red | missing table/role/route/files | health/data checks pages | run self-healing, re-verify | VERIFIED |
| Self-healing failures | repair incomplete | external dependency or hard failure | inspect repair output + logs | manual intervention/escalation | PARTIAL |

## Escalation Path

1. Capture screenshot + exact error.
2. Record recent audit/workflow log entries.
3. Run verification and collect failed checks.
4. Attempt safe self-healing.
5. Escalate to engineering with evidence bundle.

