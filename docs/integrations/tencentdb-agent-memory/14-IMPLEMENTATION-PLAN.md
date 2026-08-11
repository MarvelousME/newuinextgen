# 14 — Implementation Plan

**Stage 2 starts only after explicit approval of this document.**

## Recommendation summary

See closing section: **PROCEED WITH CONDITIONS**.

## Phased work

| Phase | Change | Files/Services | Risk | Tests | Rollback |
| ----- | ------ | -------------- | ---- | ----- | -------- |
| A | Optional Docker profile + health capability + flags default off | `docker/docker-compose.yml`, `docker/.env.example`, new `docker/memory/*` overlay docs | Med — image pull/LLM keys | health integration | stop profile |
| A | RAD manifest + capabilities (disabled lifecycle) | `architecture/manifests/bridge-memory-tencentdb.json`, `architecture/capabilities/memory-tencentdb.json`, `architecture/dependency-rules/edges.json`, `architecture/contracts/memory.v1.json` | Low | `rad-platform/cli/gate.mjs` | revert manifests |
| A | Settings + secret refs | `NextGenTutors-Companion/includes/memory/class-ngc-memory-settings.php` (new), wire admin under Platform Kernel / AI | Low | unit settings | flags off |
| B | `IMemoryProvider` + Noop provider + service façade | `includes/memory/interface-ngc-memory-provider.php`, `class-ngc-memory-noop-provider.php`, `class-ngc-memory-service.php` | Low | unit | unused if flag off |
| B | Tencent adapter (HTTP/SDK) | `includes/memory/class-ngc-tencent-memory-adapter.php` (+ optional Node helper **only if PHP SDK insufficient**) | Med — API drift | contract fixtures | flag off |
| B | Identity mapping schema | `includes/class-ngc-database.php` migration / `includes/memory/class-ngc-memory-identity-map.php` | Med — schema | unit idempotency | leave table |
| B | Policy bridge hooks for `memory.*` | `class-ngc-policy-bridge.php` (extend), `architecture/policies/*` | Med | policy tests | deny all |
| B | Read path in chat | `includes/ai/class-ngc-ai-chat.php` retrieve inject | Med — latency | integration | retrieve flag off |
| C | Async write via durable queue | `includes/memory/class-ngc-memory-ingestion-worker.php`, hook `NGC_Durable_Queue` / outbox | Med | failure tests | write flag off |
| C | Forget/correct APIs + REST | `includes/rest/class-ngc-rest-memory.php` (new) | Med | security E2E | flag off |
| D | Skills adapter ↔ `NGC_AI_Agents` | memory skill methods + admin approval UI | High — duplication risk | skills E2E | skills flag off |
| E | Knowledge adapter | adapter KS client | High — ops complexity | wiki E2E | wiki flag off |
| F | CodeGraph adapter | adapter KS client | High | code E2E | codegraph flag off |
| G | MCP tools registration | `includes/agentic/mcp/*` allowlisted tools | Med | MCP permission tests | unregister tools |
| * | Admin Memory Center panel | `includes/platform/class-ngc-platform-kernel-admin.php` or `includes/admin/class-ngc-memory-admin.php` | Low | UI smoke | hide menu |
| * | Observability metrics | `NGC_Metrics` / Intelligence events | Low | metric asserts | n/a |
| * | Evidence pack | `docs/integrations/tencentdb-agent-memory/FINAL-INTEGRATION-EVIDENCE-REPORT.md` | — | — | — |

## Exact file change register (Stage 2)

### New files (planned)

| Path | Reason | Responsibility | Dependencies | Tests |
|------|--------|----------------|--------------|-------|
| `NextGenTutors-Companion/includes/memory/interface-ngc-memory-provider.php` | Port | Provider contract | none | unit mocks |
| `NextGenTutors-Companion/includes/memory/class-ngc-memory-noop-provider.php` | DISABLED/DEGRADED | Safe no-op | interface | unit |
| `NextGenTutors-Companion/includes/memory/class-ngc-memory-service.php` | Façade | Policy + classify + provider resolve | Policy Bridge, Capability Registry | unit/integration |
| `NextGenTutors-Companion/includes/memory/class-ngc-tencent-memory-adapter.php` | Adapter | HTTP to Core/KS | settings, secrets, identity map | contract |
| `NextGenTutors-Companion/includes/memory/class-ngc-memory-identity-map.php` | Mapping | Idempotent IDs | DB | unit |
| `NextGenTutors-Companion/includes/memory/class-ngc-memory-settings.php` | Config | Flags/endpoints | options/vault | unit |
| `NextGenTutors-Companion/includes/memory/class-ngc-memory-ingestion-worker.php` | Async writes | Queue consumer | Durable queue | failure |
| `NextGenTutors-Companion/includes/rest/class-ngc-rest-memory.php` | Admin/agent REST | Bridged memory API | service | REST tests |
| `NextGenTutors-Companion/includes/admin/class-ngc-memory-admin.php` | Memory Center UI | Health/flags/mappings | settings | smoke |
| `architecture/manifests/bridge-memory-tencentdb.json` | Pillar 1 | Subsystem manifest | schemas | gate |
| `architecture/capabilities/memory-tencentdb.json` | Pillar 2 | Capability defs | contracts | gate |
| `architecture/contracts/memory.v1.json` | Contracts | Versioned schemas | — | gate |
| `docker/memory/README.md` | Deploy notes | Compose profile docs | — | manual |
| `NextGenTutors-Companion/tests/phpunit/Memory/*` | Tests | Provider/policy/isolation | — | CI |

### Existing files to modify (planned)

| Path | Existing responsibility | Planned change | Risk | Test impact |
|------|-------------------------|----------------|------|-------------|
| `NextGenTutors-Companion/includes/class-ngc-plugin.php` | Module bootstrap list | Register memory modules | Low | bootstrap |
| `NextGenTutors-Companion/includes/class-ngc-database.php` | Schema | Mapping table | Med | migrate |
| `NextGenTutors-Companion/includes/ai/class-ngc-ai-chat.php` | Chat completion | Optional memory retrieve/write hooks | Med | AI chat tests |
| `NextGenTutors-Companion/includes/platform/class-ngc-platform.php` | Platform init | Init memory settings if needed | Low | platform tests |
| `NextGenTutors-Companion/includes/platform/class-ngc-platform-kernel-admin.php` | Admin kernel | Link/embed Memory Center summary | Low | admin smoke |
| `NextGenTutors-Companion/includes/platform/class-ngc-policy-bridge.php` | Policy | Ensure memory caps evaluated | Med | policy |
| `NextGenTutors-Companion/includes/agentic/mcp/class-ngc-mcp-registry.php` | MCP inventory | Optional memory tools | Med | MCP |
| `docker/docker-compose.yml` | Local stack | `profiles: [memory]` services | Med | docker smoke optional |
| `architecture/dependency-rules/edges.json` | Dep allowlist | companion→memory edges | Low | gate |
| `.github/workflows/ci.yml` | CI | Memory unit tests; optional profile job later | Low | CI time |
| `ARCHITECTURE.md` | Sacred packages | Document memory provider subsystem | Low | docs |

### Explicitly not modified

- Theme business rules / BeyondInfinity templates (except docs)  
- Payment/booking core paths (only prove non-regression)  
- Making Proxy the LLM gateway  
- Vendoring entire Tencent monorepo into PHP

## Conditions before production

1. HA/persistence decision approved (SQLite limits acknowledged).  
2. Tenant=`service_id` mapping security tests green.  
3. Minor/PII write policy signed off.  
4. Proxy remains disabled for Bridge runtime.  
5. Feature flags default safe; progressive enable only.

## Stage 2 execution order after approval

BACKUP → implement Phase A → build/test → B → C → evidence → (D–G by separate approvals if desired).
