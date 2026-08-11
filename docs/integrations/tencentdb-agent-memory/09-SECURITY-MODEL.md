# 09 — Security Model

## Principles

1. Default **DENY** via `NGC_Policy_Bridge` for all `memory.*` capabilities.
2. Upstream ACLs are **defense-in-depth**, not Bridge authorization replacement.
3. Stored memory is **untrusted** — cannot override policies, tool allowlists, or autonomy limits.
4. Secrets only in Bridge secret vault / env — never git, never browser localStorage.
5. Private network for Core/Knowledge/Proxy; no public exposure of 8420/8424/8096 by default.
6. Hub `:8125` only if intentionally admin-exposed behind WP auth or VPN.

## Policy context dimensions

`Actor, ActorType, Tenant, User, Agent, Team, Capability, MemoryClass, Visibility, Operation, Purpose, Environment`

Example denials:

- Agent cannot `memory.retrieve` another user’s `private` mapped memories
- Tenant A service_id cannot read Tenant B
- `memory.knowledge.ingest` requires admin + feature flag
- FORBIDDEN/SENSITIVE classes never write

## PII / minors

Tutoring platform may process children’s data. Stage 2 must:

- Classify writes
- Redact via `BIA_Policy` patterns before L0
- Default-deny long-term memory for minor-linked actors until compliance sign-off
- Audit all writes/deletes

## Memory poisoning defenses

- Provenance metadata: source, actor, timestamp, confidence, class, correlation id
- Retrieval labeled as “memory context” in prompts (not system policy)
- Max items/tokens/timeouts
- Human approval for Skill publish/assign (Phase D)

## Threat tests (required Stage 2)

Cross-user deny, cross-tenant deny, deleted memory not retrieved, DISABLED provider does not break checkout/booking, no secret leakage in logs.
