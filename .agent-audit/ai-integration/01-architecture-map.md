# AI Integration — Architecture map

## Position in the five-package solution

```text
Browser ──> BeyondInfinity theme (presentation only)
                │
                ▼
        NextGenTutors-Companion  ←— domain source of truth (ngc_* tables, ngc/v1)
                │  ngc_domain_event / ngc_match_requested (actions)
                ▼
      NextGenTutors-AI-Integration  ←— THIS PACKAGE (ngtai_* tables, ngtai/v1)
                │  HMAC-SHA256 signed POST /v1/events (outbound)
                ▼
          agents-api (Coolify-hosted: LiteLLM, RAGFlow/Qdrant, orchestrator)
                │  HMAC-SHA256 signed callbacks (inbound)
                ▼
      ngtai/v1/callbacks/* → verify → nonce claim → idempotency → policy gate
                │
                ▼  do_action('ngtai_agent_result_applied') / approvals UI
        Companion domain services apply validated results
```

## Internal component map

| Layer | Classes | Responsibility |
|-------|---------|----------------|
| Bootstrap | `NGTAI_Plugin`, `NGTAI_Activator`, `NGTAI_Deactivator` | Guarded init, Companion soft dependency, degraded mode |
| Storage | `NGTAI_Database`, `NGTAI_Migrator` (`ngtai_db_version`) | 4 versioned dbDelta tables: callback_nonces, deliveries, agent_results, approvals (+ legacy idempotency/audit) |
| Security | `NGTAI_Crypto`, `NGTAI_Signature`, `NGTAI_Nonce_Store`, `NGTAI_Idempotency_Store`, `NGTAI_Access` | Encrypted secret at rest; canonical `ts\nnonce\nMETHOD\npath\nsha256(body)`; dual-layer replay (transient + durable row); business idempotency |
| Pipeline | `NGTAI_Event_Envelope`, `NGTAI_Event_Mapper`, `NGTAI_Redactor`, `NGTAI_Policy_Gate`, `NGTAI_Delivery_Repository`, `NGTAI_Outbox_Bridge`, `NGTAI_Api_Client` | Companion event → contract envelope → allowlist/redaction → policy → durable delivery row → signed async dispatch with bounded retry/dead-letter |
| Inbound | `NGTAI_Rest_*`, `NGTAI_Callback_Controller`, `NGTAI_Result_Repository` | Verified machine callbacks, agent-result application via Companion hooks only, approval workflow |
| Ops | `NGTAI_Cron`, `NGTAI_CLI`, `NGTAI_Health`, `NGTAI_Audit`, `NGTAI_Logger`, admin pages | Flush/purge/health/lock-recovery ticks, `wp ngtai *`, scrubbed structured logs, Companion audit forwarding |

## Boundary guarantees

- No HMAC/client/callback code in the theme; no `wp_ngc_*` writes from this package where a Companion domain service/hook exists.
- AI cannot approve/reject tutors, change prices, confirm payments, refund, release payouts, delete users, or close safeguarding cases — enforced in `NGTAI_Policy_Gate` (fail-closed) and the `NGTAI_Agent_Result` contract (forbidden-key rejection).
- Companion remains fully functional when this plugin or agents-api is offline; deliveries queue and retry.
