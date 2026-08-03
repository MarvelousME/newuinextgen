# Best free MCP servers for this solution

**Last Updated:** 2026-08-03  
**Staging:** Docker WordPress http://localhost:8890 + Agent Gateway :8787

There are **two MCP planes**. Mixing them is a security bug.

| Plane | Purpose | Config |
|-------|---------|--------|
| **A. Cursor / Docker Desktop MCP** | Help developers code, browse docs, drive Playwright against :8890 | `.cursor/mcp.json` + optional Docker MCP Toolkit |
| **B. Product runtime (WP + Gateway)** | Governed tools agents may call in staging | `config/mcp-staging-servers.json` → `ngc_mcp_servers` option |

---

## Code review findings (MCP path)

Ordered by severity — no silent “all green” claim.

### High

1. **Gateway MCP client is still a controlled stub** (`services/ngt-agent-gateway/src/mcp-client.js`). `discover()` invents tools; live remote MCP initialize/list is not implemented. Safe for staging, not production remote MCP.
2. **`NGC_Tool_Gateway` default branch returns `ok: true, queued: true`** for several catalogue tools that have no durable worker yet — callers can mistake acceptance for completion.
3. **Health probe treats HTTP 2xx–4xx as `ok`** in `NGC_Mcp_Registry::health_check` — a 404 can look healthy.

### Medium

4. Product SSRF correctly blocks private hosts unless staging/`allow_local` — keep production HTTPS-only.
5. Discover responses may **list** `danger.shell` for training; execute must keep denying (current allowlist does).
6. Cursor tool sprawl: Playwright + GitHub alone can hit Cursor’s tool ceiling — keep ≤6 IDE servers enabled.

### Low / policy

7. **ai-analyzer** (health metrics skill) is unrelated to this tutoring stack — do not wire.  
8. **ai-dev-jobs** MCP is free/remote and fine for **Cursor market research only**. Never feed it into tutor lead discovery / scraping paths (ethics + `NGC_Lead_Criteria`).

---

## A. Recommended free Cursor MCP set (best fit)

Configured in [`.cursor/mcp.json`](../../.cursor/mcp.json):

| Server | Cost | Why it fits NGT |
|--------|------|-----------------|
| **filesystem** | Free (local) | Repo-scoped reads/writes for Companion + theme |
| **memory** | Free (local) | Persist audit/session facts across chats |
| **sequential-thinking** | Free (local) | Multi-step policy / release planning |
| **fetch** | Free (local) | Pull public docs / Agent Card JSON |
| **playwright** | Free (local) | Headed checks against `http://localhost:8890` |
| **github** | Free (PAT) | PRs/issues; set `GITHUB_PERSONAL_ACCESS_TOKEN` |
| **ai-dev-jobs** (optional) | Free remote | Labour-market research only — not product leads |

### Docker MCP Toolkit equivalents (already catalogued)

If you prefer Docker MCP Gateway instead of npx:

| Catalog name | Use |
|--------------|-----|
| `filesystem` | Paths → this repo root only |
| `memory` | Persistent graph memory |
| `sequentialthinking` | Reasoning aid |
| `playwright` / `playwright-mcp-server` | Browser vs :8890 |
| `github-official` | Needs `github.personal_access_token` |
| `deepwiki` | Free GitHub repo Q&A (no secret) |
| `brave` | Optional search — needs free Brave API key |

**Do not enable for this product’s agents:** unrestricted shell, broad DB write MCP, browser-login scrapers, Maps people-harvest tools.

### Cursor setup

1. Restart Cursor after `.cursor/mcp.json` lands.  
2. Settings → MCP → enable only the servers you need today.  
3. For Playwright tests: `BASE_URL=http://localhost:8890`.  
4. Optional Docker profile: use MCP_DOCKER `mcp-find` / `mcp-add` for `filesystem`, `memory`, `sequentialthinking`, `playwright`.

---

## B. Product runtime MCP (Docker :8890)

**Only first-party controlled tools** are seeded:

| Tool | Mutating | Notes |
|------|----------|-------|
| `ping` | No | Health |
| `business.profile.get` | No | Redacted stub / profile read |
| `health.summary` | No | Gateway summary |
| `danger.shell` | — | Discoverable for deny-tests; **execute blocked** |

Seed file: `config/mcp-staging-servers.json`  
Apply:

```powershell
cd docker
.\scripts\seed-mcp-staging.ps1
```

WordPress reaches the gateway at `http://agent-gateway:8787` (Compose DNS). Host browser uses `http://localhost:8787/health`.

Enable flow (strict):

1. Upsert server with SSRF pass (`allow_local` + staging for Docker DNS)  
2. Discover capabilities → store draft (`capabilities_approved=0`)  
3. Human review deny-list (`danger.shell`, etc.)  
4. Set `capabilities_approved=1` then `enabled=1`  
5. Execute only via Gateway with `tool_approved=true` + allowlist  

---

## Architecture alignment (ai-agents-architect)

| Pattern | How NGT implements it |
|---------|------------------------|
| Tool registry | `NGC_Tool_Gateway` + Gateway MCP allowlist |
| Supervisor / policy | `NGC_Agent_Policy_Engine` + human approvals |
| Fail loudly | WP_Error + audit logs; kill switches |
| No runaway loops | No ReAct loop in PHP; gateway task is single-shot first-party |
| Checkpoint | Publish worker leases / idempotency keys (content path) |

---

## Verification checklist

- [ ] `GET http://localhost:8787/health` → `ok: true`  
- [ ] `seed-mcp-staging.ps1` → `seeded=1` + `gateway_health=OK`  
- [ ] Agentic admin shows first-party MCP server  
- [ ] `danger.shell` execute via gateway returns `tool_not_allowlisted`  
- [ ] Cursor MCP list shows filesystem/memory/sequential/fetch/playwright  

## Related

- [How to use](AGENTIC-HOW-TO-USE.md)  
- [Agentic codemap](../CODEMAPS/agentic.md)  
- [delivery/MCP-SERVER-REGISTRY.md](../../delivery/MCP-SERVER-REGISTRY.md)
