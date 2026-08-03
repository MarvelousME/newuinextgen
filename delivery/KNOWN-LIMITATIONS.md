# KNOWN-LIMITATIONS.md

1. OAuth **authorization URL** generation is implemented; live **token exchange** against Meta/X/LinkedIn is deferred until app credentials exist (callback records state only).
2. Social **publish** records sandbox results when live tokens are absent.
3. A2A **execution** is not embedded in WordPress — durable task rows + pin store only.
4. MCP **capability discovery** stores caller-provided discovery payloads; full Streamable HTTP client is not yet a production worker.
5. Scheduling **preview** is RFC 5545 subset; durable lease/lock publisher worker is not yet host-specific.
6. Education screens are **PARTIAL** live directories — not a full SIS/LMS.
7. Headed E2E for every new agentic menu: **UNVERIFIED** in this pass (governance unit suite PASS).
8. Disk/host constraints may block large Playwright video evidence packs.
