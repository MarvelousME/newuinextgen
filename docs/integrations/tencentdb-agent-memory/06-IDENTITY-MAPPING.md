# 06 — Identity Mapping

## Decision

**HIDE upstream auth behind Bridge.**  
Bridge remains the primary identity authority. Upstream `user_key` / team / agent / task IDs are **service-side mapped identities**, not end-user login authorities.

Do **not** use upstream identity directly for WP users.  
Do **not** federate interactive Tencent logins into WP admin for normal operators.

## Mapping model

| Bridge principal | Tencent entity | Notes |
|------------------|----------------|-------|
| `BridgeTenantId` (`NGC_Tenant_Context`) | `x-tdai-service-id` **or** Team under shared service | Prefer **one service_id per Bridge tenant** for hard isolation |
| `WP User ID` | Tencent `user_id` + stored `user_key` (secret) | Created idempotently via `/v3/meta/user/create` using service account |
| Bridge Agent (`NGC_AI_Agents` / Control Plane agent id) | Tencent `agent_id` | Created under mapped team |
| Bridge Team / org unit (if any) | Tencent `team_id` | Default team per tenant if Bridge lacks teams |
| Bridge conversation / chat session id | Tencent `session_id` | Client-generated UUID |
| Bridge workflow / ops task (optional) | Tencent `task_id` | Create when Task dimension needed (Hermes requires it; Bridge WP path optional) |

## Mapping store (planned table)

`wp_ngc_memory_identity_map`

| Column | Purpose |
|--------|---------|
| bridge_type | tenant\|user\|agent\|team\|task\|session |
| bridge_id | Bridge stable id |
| tenant_id | Bridge tenant |
| provider | `tencentdb` |
| remote_id | Tencent id |
| remote_meta | JSON (non-secret) |
| created_at / updated_at | Audit |
| unique(provider, bridge_type, bridge_id, tenant_id) | Idempotency |

**Secrets:** Tencent `user_key` values stored only via `NGC_Secret_Vault` / encrypted options — never in mapping table plaintext if avoidable; if required, encrypt at rest.

## Auth to Core

Preferred production:

1. Bridge service identity: Gateway Bearer (`TDAI_GATEWAY_API_KEY`) from Bridge secrets.
2. Per-operation: mapped `x-tdai-user-key` **or** metadata ops with admin key only for provisioning.
3. Always send `x-tdai-service-id` = tenant-mapped instance.

Local/dev may use empty Bearer only inside private Docker network (upstream default) — **forbidden** for exposed ports.

## Provisioning flow

```text
First memory use for (tenant, user, agent)
  → Policy allow memory.*
  → Ensure tenant service_id / team
  → Ensure remote user + key (once)
  → Ensure remote agent
  → Persist mapping
  → Proceed with MemoryClient isolation context
```

Never recreate entities on every request.
