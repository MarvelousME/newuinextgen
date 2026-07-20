# 14 — Agent Autonomy Design

**Status:** PARTIAL — control plane implemented; full autonomous remediation loops NOT IMPLEMENTED (by design until evaluation gates pass).

## Implemented (evidence)

| Component | Path | Status |
|-----------|------|--------|
| Policy engine | `includes/agents/class-ngc-agent-policy-engine.php` | VERIFIED (25 unit tests) |
| Control plane + kill switches | `includes/agents/class-ngc-agent-control-plane.php` | PARTIAL — tables + registry + task queue |
| Event envelope | `includes/agents/class-ngc-agent-event-envelope.php` | PARTIAL |
| Fraud engine | `includes/agents/class-ngc-fraud-engine.php` | PARTIAL — signals + cases; not all rules |
| Safeguarding | `includes/agents/class-ngc-safeguarding.php` | PARTIAL — cases + redaction |
| Ops Centre UI | `includes/admin/class-ngc-agent-ops-admin.php` | PARTIAL — admin UI |
| Existing BYOK AI | `includes/ai/*`, `BIA_Policy` | PARTIAL — Level 0–1 chat |

## Agent registry (seeded)

See `NGC_Agent_Control_Plane::seed_registry()` — 16 agents with autonomy L0–L3 defaults. Level 4+ actions always `REQUIRE_APPROVAL` or `DENY`.

## Autonomy enforcement

- Global kill switch: `ngc_agent_global_pause`
- Per-agent pause: `ngc_agent_paused_ids`
- Tool allowlists per agent
- Prohibited: secret exfiltration, audit disable, self-grant permissions, unrestricted shell

## Explicitly NOT autonomous yet

- Remediation Agent source edits
- Production deploy
- Refund/payout execution
- Tutor approve/reject without human
- Full evaluation suite / prompt-injection harness
- Model-driven tool loops (AI Suite still supervised chat)

## Production readiness for agents

**NOT APPROVED** for unsupervised operation. Approved for observe/recommend/case-create with kill switches.
