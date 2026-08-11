# 07 — Data and Tenant Boundaries

## Isolation strategy

**Hard boundary:** `x-tdai-service-id` == Bridge tenant (recommended).  
**Soft boundary (defense-in-depth):** Tencent team/user/agent/session + Bridge policy.

Never rely on Hub ACLs alone.

## Memory classification (Bridge-side before write)

| Class | Default persist? | Level target |
|-------|------------------|--------------|
| SESSION | Optional L0 | L0 |
| SHORT_TERM | Yes L0 | L0 |
| LONG_TERM | Policy gated | L1/L2 |
| USER_PREFERENCE | Policy gated | L1/L3 |
| PROJECT | Opt-in | L2 |
| DOMAIN | Opt-in | L2/Wiki |
| SKILL | Approval gated | Skill asset |
| KNOWLEDGE | Opt-in ingest | Wiki |
| CODE | Opt-in | CodeGraph |
| SENSITIVE | Redact / deny | — |
| FORBIDDEN | Never | — |

## Forbidden persistence

Credentials, API keys, tokens, payment instrument data, raw minor safeguarding case notes (unless explicit compliance-approved pathway), secrets from vault.

Reuse `BIA_Policy` redaction where applicable.

## Ownership

| Data | Owner |
|------|-------|
| Mapping rows, flags, Bridge audit | Companion |
| L0–L3 content, skills blobs | Tencent Memory Core volumes |
| Wiki/CodeGraph content | Knowledge volumes |
| WP chat UI state | Ephemeral / optional Bridge session table later |

## Retention

Bridge config: max age per class; scheduled forget jobs calling provider delete APIs.  
Verify Tencent cascade behavior before claiming GDPR erasure completeness (Stage 2 test).

## Tutoring domain boundary

`wp_ngc_sessions` (classroom) **must not** be aliased to Tencent Task without explicit product decision. Default: separate.
