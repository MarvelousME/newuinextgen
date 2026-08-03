# AGENTIC-WEBSITE-GAP-ANALYSIS

## Operational definition used

A website that can observe verified events, plan under policy, discover approved tools, elicit missing info, request human approval, execute least-privilege tools, keep durable task state, monitor outcomes, retry safely, escalate, audit, and learn only from approved feedback.

## Scores (0–5)

| Capability | Before (evidence) | Score | After (this delivery) | Score | Gap | Risk |
| --- | --- | --- | ---: | --- | ---: | --- | --- |
| Event perception | Domain events / demo journeys exist | 2 | Unchanged + agent hub metrics | 2 | Unified agent event bus incomplete | Medium |
| Constrained planning | Agent ops registry only | 1 | Tool gateway allowlist | 2 | No durable planner worker | Medium |
| Tool discovery (MCP) | Missing | 0 | Dynamic registry + SSRF + cap approval | 3 | Live capability discovery client | High if misconfigured |
| Human approval | Agent ops approvals | 2 | Content approve + tool approval flags | 3 | Cross-agent approval UX | Medium |
| Least-privilege execution | Partial agent ops | 1 | `NGC_Tool_Gateway` allowlist (no SQL/shell/browser-login) | 3 | Separate gateway runtime | High |
| Durable tasks (A2A) | Missing | 0 | Pinned cards + task store | 2 | Official SDK execution service | High |
| Social OAuth publishing | Missing / password anti-pattern avoided | 0 | OAuth begin + vault refs + no password fields | 2 | App credentials + live exchange | High |
| Scheduling | WP-Cron heavy elsewhere | 1 | RRULE preview + multi-time clarity | 2 | Durable lease worker | Medium |
| Ethical tutor leads | Missing | 0 | Criteria guards + source blocklist + FluentCRM upsert | 3 | Approved partner APIs | High (compliance) |
| Observability | Observability service exists | 2 | Audit hooks on vault/MCP/A2A/social/leads | 3 | Traces across gateway | Medium |
| Secrets | NGC_Crypto present | 2 | `NGC_Secret_Vault` refs | 3 | Rotation UX | High |
| Child safety / privacy | Safeguarding modules exist | 3 | Lead retention + suppression gates | 3 | POPIA DPIA for outreach | High |

**Weighted maturity (security/privacy heavier):** before ≈ **22%** → after ≈ **48%** (engineering estimate from table; not a production certification).

## Non-negotiable controls shipped

1. No social username/password fields — OAuth only (`NGC_Social_Connections`).
2. No ethnicity/gender/age targeting or inference in lead criteria (`NGC_Lead_Criteria` + tests).
3. No scraping / browser-login harvest sources (`NGC_Tutor_Leads::source_policy`, Bing Search retired blocked).
4. No unverified MCP servers enabled by default; capability approval required before enable.
5. A2A external agents treated as untrusted; execution deferred to separate gateway/SDK.
