# TEST-VERIFICATION-REPORT.md

| Suite | Command | Result |
| --- | --- | --- |
| Agentic governance | `php NextGenTutors-Companion/tests/agentic-governance.php` | **PASS** 12/12 (2026-08-03) |
| PHP lint (new admin/agentic/fluentcrm) | `php -l …` | **PASS** |
| Headed Playwright agentic menus | — | **UNVERIFIED** — not executed this pass |
| Live FluentCRM upsert | — | **UNVERIFIED** — requires FluentCRM runtime |
| Live OAuth round-trip | — | **UNVERIFIED** — INPUTS-REQUIRED |

Protected-trait exclusion tests: **PASS**  
Scheduling multi-time preview: **PASS** (unit)  
MCP SSRF guards: **PASS** (unit)
