# MCP-SERVER-REGISTRY

**Last Updated:** 2026-08-03

## Implementation

| Item | Path |
| --- | --- |
| Registry | `NextGenTutors-Companion/includes/agentic/mcp/class-ngc-mcp-registry.php` |
| SSRF guards | `.../mcp/class-ngc-mcp-ssrf.php` |
| WP → Gateway client | `.../agentic/class-ngc-agent-gateway-client.php` (`mcp_discover`, `mcp_execute`) |
| Gateway MCP client | `services/ngt-agent-gateway/src/mcp-client.js` |
| Admin UI | `.../admin/class-ngc-agentic-admin.php` → `render_mcp` / `handle_mcp_upsert` |
| Secrets | `.../agentic/class-ngc-secret-vault.php` |
| Staging seed | `config/mcp-staging-servers.json` + `docker/scripts/seed-mcp-staging.ps1` |
| Cursor IDE MCPs | `.cursor/mcp.json` (developer plane only) |
| Operator guide | `docs/GUIDES/MCP-SERVERS-FREE-CONFIG.md` |

## Behaviour

- Default inventory: **empty** until staging seed (or admin upsert).
- Enable requires `capabilities_approved`.
- HTTPS required unless local override under `WP_DEBUG` / staging `allow_local`.
- Private/metadata hosts and link-local IPs blocked unless staging local override.
- Health probe is non-mutating HTTP GET after SSRF checks (`redirection => 0`).
- Gateway execute requires `tool_approved` + allowlist: `ping`, `business.profile.get`, `health.summary`.
- `danger.shell` may appear in discovery fixtures; execute always blocked.

## Two planes (do not conflate)

| Plane | Servers |
| --- | --- |
| Cursor / Docker MCP Toolkit | filesystem, memory, sequential-thinking, fetch, playwright, github (+ optional ai-dev-jobs, deepwiki, brave) |
| Product runtime on :8890 | **Only** `ngt_firstparty_gateway` first-party allowlist |

## Verified

| Check | Result | Evidence |
| --- | --- | --- |
| Metadata IP blocked | PASS | `tests/agentic-governance.php` |
| Non-HTTPS remote blocked | PASS | same |
| HTTPS public allowed | PASS | same |
| Allowlist blocks `danger.shell` | PASS | `services/ngt-agent-gateway/tests/*.test.js` |
| Live remote MCP handshake in production | UNVERIFIED — stub client; staging seed uses first-party gateway only |
| Staging seed + Compose gateway | APPLY via `docker/scripts/seed-mcp-staging.ps1` after `docker compose up` |

## MCP servers configured (staging target)

| ID | Enabled | Tools |
| --- | --- | --- |
| `ngt_firstparty_gateway` | yes (after seed) | ping, business.profile.get, health.summary |
