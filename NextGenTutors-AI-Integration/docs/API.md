# API

## Outbound (WordPress → agents-api)

All requests are signed (see `docs/SECURITY.md`) and sent by `NGTAI_Api_Client`.

| Method | Path | Purpose |
| --- | --- | --- |
| `POST` | `/v1/events` | Deliver an event envelope from the outbox. Idempotency key: `evt:{event_id}`. |
| `POST` | `/v1/agents/tasks` | Submit a task (e.g. approval decisions). Idempotency key: `task:{task_id}`. |
| `GET` | `/v1/health` | Upstream health probe. |

### Event envelope (`NGTAI_Event_Envelope`, schema_version 1)

```json
{
  "event_id": "evt-…",             // ^[A-Za-z0-9._:-]{8,128}$, unique
  "event_type": "match.requested", // dotted lowercase contract type
  "schema_version": 1,
  "occurred_at": "2026-07-21T08:00:00Z",
  "tenant_id": "nextgentutors",
  "source": "nextgentutors-companion",
  "subject_type": "match",
  "subject_id": "MATCH-500",
  "correlation_id": "…",
  "causation_id": "",
  "data_classification": "restricted",
  "consent_context": null,
  "payload": { }                    // redacted + allowlisted per config/event-schemas.php
}
```

Supported event types and their payload allowlists are defined in `config/event-schemas.php`.

## Inbound (agents-api → WordPress REST)

Namespace `ngtai/v1`. Every callback must carry valid v1.1 signature headers; nonces are single-use and `Idempotency-Key` is honoured.

| Route | Purpose |
| --- | --- |
| `POST /wp-json/ngtai/v1/callbacks/agent-result` | Deliver an agent result. |
| `POST /wp-json/ngtai/v1/callbacks/approval-request` | Request a human approval. |
| `GET /wp-json/ngtai/v1/health` | Integration health (capability-gated). |

### Agent result (`NGTAI_Agent_Result`)

Required: `agent_run_id`, `result_version` (int ≥ 1), `event_id`, `correlation_id`, `agent_name`, `action_name`, `status` (`succeeded|failed|partial`), `result` (object), `completed_at`. Optional: `error`, `approval_id`.

Rules enforced by `NGTAI_Callback_Controller`:

- `(agent_run_id, result_version)` is unique — replays return `{"success":true,"idempotent":true}`.
- Dotted action names (e.g. `match.recommendation`) are preserved.
- `match.recommendation` rankings must contain only eligible candidates and require a non-empty `explanation`.
- Results containing forbidden mutation keys (`approve_tutor`, `price`, `refund`, `payout`, `delete_user`, …) are rejected with HTTP 422.
- Policy `DENY` → HTTP 403 `ngtai_policy_denied`; `REQUIRE_APPROVAL` → stored as `pending_approval` and an approval row is created.

### Error codes

| Code | HTTP | Meaning |
| --- | --- | --- |
| `ngtai_missing_header` | 401 | Required signature header absent. |
| `ngtai_invalid_timestamp` / `ngtai_timestamp_skew` | 401 | Malformed or out-of-window timestamp. |
| `ngtai_body_digest_mismatch` | 401 | `X-NGT-Body-SHA256` does not match the body. |
| `ngtai_unknown_key` | 401 | Unrecognized `X-NGT-Key-Id`. |
| `ngtai_signature_mismatch` | 401 | HMAC verification failed. |
| `ngtai_invalid_result` | 422 | Agent result contract violation. |
| `ngtai_ineligible_candidate` / `ngtai_explanation_required` | 422 | Recommendation guardrails. |
| `ngtai_policy_denied` | 403 | Policy gate denied the action. |
