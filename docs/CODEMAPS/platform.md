# Platform / Domain Codemap

**Last Updated:** 2026-09-16  
**Entry Points:**
- `NextGenTutors-Companion/nextgencompanion.php`
- `NextGenTutors-BeyondInfinity/functions.php` (Docker: `nextgentutors-tutorfabulous`)
- `services/ngt-agent-gateway/src/server.js`
- `rad-platform/cli/discover.mjs`

## Architecture

```
Browser / Elementor
        │
        ▼
 TutorFabulous theme (BeyondInfinity package)
        │  [ngc_*] shortcodes / ngc/v1 REST
        ▼
 Companion (NGC_Module_Registry)
        ├─ matching  ── Policy Bridge: matching.propose
        ├─ payments  ── Policy Bridge: payment.authorize
        ├─ bookings  ── Policy Bridge: booking.create
        ├─ ai / agentic
        ├─ integrations / adapters
        └─ platform (Capability Registry, Authz, Observability, Vault)
                │
                ├─ Agent Gateway :8787 (SQLite task store)
                ├─ Talent NLP / Memory (optional overlays)
                └─ ecosystem-platform :8790 (Bearer API)
```

## Key Modules

| Module | Path | Purpose |
|--------|------|---------|
| Module registry | `includes/modules/class-ngc-module-registry.php` | Lazy bootstraps: matching, payments, ai, integrations, platform |
| Matching | `includes/matching/` | Propose/score matches |
| Payments | `includes/payments/` | WC settle + payouts |
| Bookings | `includes/class-ngc-bookings.php` | Session lifecycle (+ Amelia adapter) |
| Policy Bridge | `includes/platform/class-ngc-policy-bridge.php` | Capability authorize (default DENY) |
| Authz matrix | `includes/platform/class-ngc-authz-matrix.php` | Cap → permission audit |
| Secret Vault | `includes/agentic/class-ngc-secret-vault.php` | `env:` + encrypted options |
| Observability | `includes/platform/class-ngc-platform-observability.php` | traceparent + OTEL hook |
| Capability registry | `includes/platform/` + `architecture/capabilities/*.json` | Sacred capability contracts |
| Discover locks | `rad-platform/cli/lib/scan-lockfiles.mjs` | npm/composer inventory |

## Data Flow (privileged)

1. Domain mutate → `NGC_Policy_Bridge::authorize_domain(capabilityId)`  
2. Unknown capability → DENY  
3. Human → requiredPermissions / Authz matrix  
4. Payment settle → WC hook / cron / explicit `trusted_system` only (not bare uid=0)

## Related

- [companion-domain.md](companion-domain.md) — REST, shortcodes, tables  
- [agentic.md](agentic.md) — agents / MCP / gateway  
- [rad.md](rad.md) — governance CLI  
- [TECHNICAL-DEBT-REGISTER.md](../../architecture/current-state/TECHNICAL-DEBT-REGISTER.md)  
- [CAPABILITY-INVENTORY.md](../../architecture/current-state/CAPABILITY-INVENTORY.md)  
