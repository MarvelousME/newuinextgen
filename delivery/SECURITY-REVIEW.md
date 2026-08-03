# SECURITY-REVIEW.md

## Controls added

| Control | Location |
| --- | --- |
| Encrypted secret vault refs | `NGC_Secret_Vault` |
| Tool allowlist (no SQL/shell/browser-login) | `NGC_Tool_Gateway` |
| MCP SSRF / HTTPS / metadata block | `NGC_Mcp_Ssrf` |
| MCP enable requires capability approval | `NGC_Mcp_Registry::upsert` |
| Social password field rejection | `NGC_Social_Connections::save_from_oauth` |
| OAuth state + PKCE | `NGC_Social_Oauth` |
| Protected-trait rejection | `NGC_Lead_Criteria` |
| Scraping source blocklist | `NGC_Tutor_Leads::source_policy` |
| Human approval before publish | `NGC_Content_Studio` |
| Admin capability + nonces | `NGC_Agentic_Admin` handlers |

## Critical security defects found in this delivery scope

**0** known critical defects in new code paths covered by governance tests.

## Residual risks

- Misconfigured MCP `allow_local` under debug.
- Operator pasting secrets into wrong fields (UI rejects social passwords; still educate).
- FluentCRM sync without verified consent record on imported lists.
