# Architecture

NextGenTutors-AI-Integration is a signed, asynchronous bridge between the Companion plugin's domain events and the external agents-api. It never makes domain decisions itself: agents return *recommendations*, and everything that mutates the platform stays behind policy gates and human approval.

## Data flow

```
Companion hooks (ngc_domain_event, ngc_match_requested)
        │
        ▼
NGTAI_Event_Mapper ──► NGTAI_Redactor (profile + allowlist)
        │
        ▼
NGTAI_Policy_Gate ──DENY──► audit + drop
        │ ALLOW / REQUIRE_APPROVAL
        ▼
NGTAI_Delivery_Repository (durable outbox, wp_ngtai_deliveries)
        │  cron: ngtai_outbox_flush
        ▼
NGTAI_Outbox_Bridge::dispatch_batch ──► NGTAI_Api_Client (signed HTTPS POST /v1/events)
        │ 2xx → delivered   5xx/408/429 → retry_pending → dead_letter (5 attempts)
        ▼
agents-api ──signed callback──► REST /ngtai/v1/callbacks/agent-result
        │  NGTAI_Signature::verify + NGTAI_Nonce_Store + NGTAI_Idempotency_Store
        ▼
NGTAI_Callback_Controller ──► NGTAI_Policy_Gate ──► NGTAI_Result_Repository
        │  REQUIRE_APPROVAL → wp_ngtai_approvals (human decision required)
        ▼
do_action( 'ngtai_agent_result_applied', $subject_id, $payload )
```

## Key components

| Component | Responsibility |
| --- | --- |
| `NGTAI_Event_Mapper` | Maps Companion event names (e.g. `MatchRequested`) to versioned contract types (`match.requested`) and builds validated `NGTAI_Event_Envelope` objects. |
| `NGTAI_Redactor` | Applies redaction profiles (`default`, `minor`, `finance`, `safeguarding`), per-event payload allowlists, and candidate eligibility filtering. |
| `NGTAI_Policy_Gate` | Denies prohibited execution actions, honours the global pause, enforces `external_delivery_allowed` / `policy_required` per event schema. |
| `NGTAI_Delivery_Repository` | Durable outbox with claim locking, retry scheduling (0/30/120/600/1800s), dead-lettering, and lock recovery. |
| `NGTAI_Api_Client` | Signed WordPress HTTP transport with HTTPS enforcement, host allowlisting, timeout bounds, and a circuit breaker (5 failures → 60s open). |
| `NGTAI_Signature` | v1.1 HMAC contract for outbound and inbound requests (see `docs/SECURITY.md`). |
| `NGTAI_Nonce_Store` / `NGTAI_Idempotency_Store` | Replay protection (transient cache + unique durable row) and exactly-once callback processing. |
| `NGTAI_Callback_Controller` | Validates the `NGTAI_Agent_Result` contract, re-evaluates policy, stores versioned results, and routes gated actions to approvals. |
| `NGTAI_Audit` / `NGTAI_Logger` | Scrubbed structured logging and a durable audit trail (`wp_ngtai_audit`, plus Companion `NGC_Audit` when present). |

## Storage

Four primary tables (see `NGTAI_Database::tables()`), all prefixed `wp_ngtai_`:

- `callback_nonces` — unique nonce claims with expiry.
- `deliveries` — outbox rows; unique `event_id`; status machine `pending → processing → delivered | retry_pending | failed | dead_letter | cancelled`.
- `agent_results` — unique `(agent_run_id, result_version)`; stores policy decision and approval linkage.
- `approvals` — unique `approval_id`; `pending → approved | denied`, decided only by a human with a reason.

An `ngtai_idempotency` table and `ngtai_audit` table support exactly-once semantics and audit.

## Design invariants

- Agents recommend; the platform decides. Execution actions (`finance.payout.release`, `tutor.approve`, …) are always denied.
- Every outbound payload passes redaction and an explicit allowlist before leaving WordPress.
- Every inbound callback is signature-verified, replay-protected, idempotent, and policy-gated.
- All state transitions are auditable with correlation IDs end to end.
