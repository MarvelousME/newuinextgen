# 11 — Migration Plan

## Starting point

Bridge has **no durable product memory** to migrate. Client chat histories are ephemeral — no bulk import required for Day 1.

## Strategy

| Track | Approach |
|-------|----------|
| Greenfield memory | Enable provider empty; opt-in agents |
| Optional import | Manual Hub import of docs/repos (Wiki/CodeGraph) — Phase E/F |
| External RAG (agents-api) | Parallel validation if/when present; federated search — do not destructive cutover |
| Dual-read | Feature flag `memory.retrieve.compare_noop` for shadow metrics only |

## Rollout phases (aligned to prompt)

| Phase | Scope | Flag |
|-------|-------|------|
| A | Deploy Core (+ optional Hub internal), health, DISABLED default | `memory.enabled=false` initially then health-only |
| B | Read-only retrieve/search wired into chat (empty OK) | `memory.retrieve.enabled` |
| C | Controlled L0 writes + forget/correct | `memory.write.enabled` |
| D | Skills search/assign behind approval | `memory.skills.enabled` |
| E | Wiki | `memory.wiki.enabled` |
| F | CodeGraph | `memory.codegraph.enabled` |
| G | Broader agent/MCP exposure | per-agent allowlists |

## Rollback

See `13-ROLLBACK-PLAN.md`. Migration is additive; rollback = flags off + stop containers; WP domain data untouched.
