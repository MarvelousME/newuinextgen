# ADR-005 — Enterprise Platform Kernel (Companion SSOT)

## Status

Accepted — 2026-08-01

## Context

NextGen Tutors runs multiple workflow producers (Studio, Orchestrator, Automation Hub, AutomatorWP) that can fire the same side effects twice. Payments/wallet lack a double-entry ledger and reconciliation. Audit logs are mutable table rows. Fraud and safeguarding engines exist but lack durable evidence + SLA queue integration. Ops needs MySQL-durable work queues that run on Docker/Coolify without Redis or Action Scheduler.

## Decision

1. **SSOT kernel** lives under `NextGenTutors-Companion/includes/platform/` (`NGC_Platform`).
2. **Workflow authority** (`ngc_workflow_authority_v1`, default ON) makes Hub/Studio/Orchestrator/AutomatorWP *producers* that enqueue `workflow.execute` jobs; kill switch `ngc_workflow_authority_kill` restores legacy in-process execution.
3. **Durable queue** uses MySQL tables (`ngc_queue_messages`, `ngc_queue_dlq`) with leases, exponential+jitter backoff, cron tick `ngc_queue_tick`, and CLI `wp ngc queue work|stats|dlq-replay`.
4. **Idempotency** via `ngc_idempotency_keys` UNIQUE(tenant_id, idem_key); wraps payments settle, bookings create, workflow authority executions.
5. **Ledger** double-entry (`ngc_gl_*`) posts on payment settle/refund/payout/wallet credit; daily `ngc_reconciliation_daily`.
6. **Immutable audit** hash-chain (`ngc_audit_chain`) dual-writes from `NGC_Audit::log`; WORM export + `wp ngc audit verify`.
7. **Soft tenancy** `tenant_id = get_current_blog_id()` (default 1) — no Multisite rewrite.
8. **Authz matrix** maps Student/Parent/Tutor/Admin/Finance/Support/Moderator/Affiliate/Franchise/SuperAdmin to `ngc_*` caps; safeguarding/fraud admin use `ngc_manage_safeguarding` / `ngc_manage_fraud`.
9. **Observability** extends `NGC_Metrics` + W3C `traceparent` on REST + Intelligence alert channels.

## Consequences

- Side-effect latency shifts to queue worker/cron when authority is ON.
- Schema migrations are dbDelta-only; existing `ngc/v1` routes retained with additive platform routes.
- Grafana/Tempo remain ops compose profiles, not PHP rewrites.

## Alternatives considered

- Redis / Action Scheduler queue — rejected for Coolify WP portability.
- WordPress Multisite SaaS tenancy — out of scope this pass.
- External ML fraud SaaS — hooks only; local rules operational.
