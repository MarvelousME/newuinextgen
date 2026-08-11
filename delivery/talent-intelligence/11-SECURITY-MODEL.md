# 11 — Security Model

## Threats

| Threat | Mitigation |
|--------|------------|
| Unauthenticated scoring API | WP REST caps + internal network for Python |
| Cross-tenant data leak | Tenant context + scoped queries |
| PII over-exposure to agents | Capability grants + field minimization |
| Malicious resume upload | Stage 2: no binary uploads; later: type/size/malware scan |
| Pickle RCE | Do not load untrusted pickles; prefer Bridge-native scorer; pin checksums if sidecar needs artifacts |
| Score used as auto-approve | Policy forbid; no code path from TI → lifecycle approve |
| Prompt/injection via free text | Sanitize; structured features preferred |
| Stack traces to UI | Normalize error codes |

## Error taxonomy

```text
VALIDATION_ERROR
INVALID_PROFILE
INSUFFICIENT_DATA
PROVIDER_UNAVAILABLE
TIMEOUT
MODEL_ERROR
CONFIGURATION_ERROR
UNEXPECTED_ERROR
```

## Secrets

- Python service tokens via `NGC_Secret_Vault` / Docker secrets  
- No public exposure of ML service ports  

## AuthZ

| Action | Cap |
|--------|-----|
| View evaluations | `ngc_manage_matches` or admin |
| Run evaluate/rank | same + rate limit |
| Edit weights | `ngc_manage_platform` / manage_options |
| Agent invoke | explicit agent policy allowlist |

## Network

Python sidecar (if enabled): Docker internal only, compose profile `talent`, analogous to memory profile pattern.
