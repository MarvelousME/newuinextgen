# 12 — Deployment Plan

## Modes

| Mode | Description |
|------|-------------|
| DISABLED | Noop provider; zero impact |
| BRIDGE_NATIVE | Companion `bridge_rules_v1` scorer only |
| HYBRID | Bridge structured + optional Python text similarity |
| MAINTENANCE | Reads last evaluations; no new scoring |

## Feature flags (default OFF)

```text
talent.enabled
talent.evaluate_applications
talent.rank_find_tutor
talent.nlp_sidecar_enabled
talent.agent_tools_enabled
```

## Optional Python sidecar

```text
services/ngt-talent-intelligence/
  Dockerfile
  requirements.lock
  app/api /v1/health /v1/ready /v1/similarity
```

**Not** Streamlit. **Not** required for Stage 2 MVP if Bridge-native ships first.

## Compose

Optional profile `talent` — internal port only. Document SQLite/HA N/A (stateless scorer).

## Rollback

1. Flags OFF  
2. Stop sidecar profile  
3. Leave evaluation tables (read-only history)  
4. Find-a-Tutor falls back to existing marketplace order
