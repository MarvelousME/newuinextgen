# Workflow Database Evidence

**Status:** PENDING — no before/after mutation queries were executed in Phases 1–7.

**Date:** 2026-08-14

Static schema names discovered in source (not row counts):

| Concern | Table / option (from PHP) |
|---|---|
| Studio graphs | `{prefix}ngc_studio_workflows` |
| Studio executions | `{prefix}ngc_studio_executions` |
| Durable queue | platform `queue_messages` |
| DLQ | `NGC_Queue_DLQ` table |
| Bookings / sessions / payouts | `ngc_bookings`, sessions schema, `ngc_payouts` |
| Authority flag | `NGC_Platform` options |

No BEFORE/AFTER dumps were captured. No chain-proven DB verification is claimed.
