# RELEASE-NOTES.md

## Agentic foundation (Companion) + Agent Gateway (2026-08-03)

- Separate `services/ngt-agent-gateway` using official `@a2a-js/sdk` (health reports `official-a2a-js-sdk`).
- HMAC-authenticated task + MCP allowlist endpoints; SSRF expansions; first-party diagnostics agent E2E tests **12/12**.
- Durable `NGC_Publish_Worker` with lease/idempotency/DLQ (unit anti-dupe PASS).
- Lean Companion package: `delivery/installable-packages/NextGenTutors-Companion-v1.9.5-agentic.zip`.

## Decision

**STAGING ONLY** — independent review criteria for CONTROLLED PILOT are not met (production build, WP install/E2E, live OAuth/CRM/leads, full security suite).

