# Security

## Request signing (v1.1 contract)

Both directions use HMAC-SHA256 over a canonical string:

```
{timestamp}\n{nonce}\n{METHOD}\n{path}\n{sha256(body)}
```

- `timestamp` — Unix seconds; requests outside the configured skew (default 300s, bounded 30–900s) are rejected.
- `nonce` — ≥ 16 chars, single use; replays are rejected by `NGTAI_Nonce_Store` (transient cache backed by a unique durable row retained for `nonce_retention_days`).
- `METHOD` — uppercased HTTP method.
- `path` — the request path exactly as signed.
- `sha256(body)` — lowercase hex digest of the raw body.

### Headers

| Header | Content |
| --- | --- |
| `X-NGT-Timestamp` | Unix timestamp used in the canonical string. |
| `X-NGT-Nonce` | Single-use nonce. |
| `X-NGT-Signature` | `v1=` + lowercase hex HMAC (prefix optional on verify). |
| `X-NGT-Key-Id` | Signing key identifier; compared in constant time. |
| `X-NGT-Body-SHA256` | Body digest; verified against the received body when present. |
| `X-NGT-Correlation-ID` / `X-NGT-Request-ID` | Tracing. |
| `Idempotency-Key` | Exactly-once processing (auto-generated for outbound POSTs). |

Verification failures return 401 with specific codes: `ngtai_missing_header`, `ngtai_invalid_timestamp`, `ngtai_timestamp_skew`, `ngtai_invalid_nonce`, `ngtai_unknown_key`, `ngtai_body_digest_mismatch`, `ngtai_signature_mismatch`. All comparisons use `hash_equals`.

## Secret handling

- The signing secret is read from the `NGTAI_AGENTS_API_SECRET` constant first; otherwise from the `ngtai_agents_api_secret_encrypted` option, encrypted at rest by `NGTAI_Crypto`.
- Secrets never appear in logs: `NGTAI_Logger::scrub()` masks keys matching secret/token/password/authorization/cookie/email/phone/id_number before any write.

## Transport hardening (`NGTAI_Api_Client`)

- HTTPS required (plain HTTP only for localhost in demo/dev mode).
- Destination host must be on the configured allowlist.
- Redirects disabled, unsafe URLs rejected, TLS verification on, bounded timeout (2–60s).
- Circuit breaker: 5 retryable failures within 5 minutes opens the circuit for 60 seconds.

## Data protection

- Outbound payloads pass a redaction profile then a per-event allowlist (`config/payload-allowlists.php`, `config/event-schemas.php`).
- The `minor` profile removes never-send fields (`sa_id`, `id_number`, `address`, `guardian_phone`, …) and pseudonymizes learner identifiers to `learner_{hash}`.
- Match candidates are only sent if verified and eligible.

## Governance boundaries

- Execution actions (`finance.refund.execute`, `finance.payout.release`, `tutor.approve`, `tutor.reject`, `user.delete`, `deploy.production`) are unconditionally denied by `NGTAI_Policy_Gate`.
- A global pause (`ngtai_global_pause` or Companion control plane) stops all delivery.
- Agent results may never mutate the domain directly: forbidden keys in results are rejected at the contract level, and gated actions require a human approval with a recorded reason. Self-approval is blocked.
