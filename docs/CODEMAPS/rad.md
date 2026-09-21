# RAD / Architecture Governance Codemap

**Last Updated:** 2026-09-16  
**Entry Points:**
- `rad-platform/cli/discover.mjs`
- `rad-platform/cli/validate.mjs`
- `rad-platform/cli/gate.mjs`
- `architecture/capabilities/*.json`
- `architecture/current-state/*`

## Architecture

```
 Source tree + lockfiles
        │
        ▼
 discover.mjs  (+ scan-lockfiles.mjs)
        │  → discover-snapshot.json / SYSTEM-INVENTORY.generated.md
        ▼
 validate.mjs
        │  → validate-report.json
        ▼
 gate.mjs
        │  → gate-report.{json,md}  (CI: rad-architecture job)
        ▼
 PASS / FAIL sacred contracts
```

## Key Modules

| Module | Purpose | Outputs |
|--------|---------|---------|
| `discover.mjs` | Inventory packages, manifests, dependency locks | `architecture/current-state/discover-snapshot.json` |
| `lib/scan-lockfiles.mjs` | Composer/npm lock presence | `dependencyLocks` on snapshot |
| `validate.mjs` | Contract checks | `architecture/reports/validate-report.json` |
| `gate.mjs` | CI gate | `architecture/reports/gate-report.*` |
| Sacred packages | Capability / package contracts | `architecture/capabilities/sacred-packages.json` |
| Debt register | TD-RAD tracking | `TECHNICAL-DEBT-REGISTER.md` |

## Data Flow

1. Change architecture-sensitive code  
2. Run discover → validate → gate locally or in CI  
3. Update capability inventory / ownership matrix when contracts change  
4. Never bypass gate for “temporary” domain shortcuts

## TD-RAD snapshot (2026-09-16)

| ID | Status |
|----|--------|
| TD-RAD-001 Policy Bridge on core mutations | Mitigated |
| TD-RAD-002 Designer UIs | Deferred |
| TD-RAD-003 Lockfile discover | Done |
| TD-RAD-004 Hub quiet-domain | Mitigated |
| TD-RAD-005 Vault `env:` + option crypto | Mitigated |
| TD-RAD-006 OTEL export | Partial (hook; full OTLP ops) |

## Related Areas

- [platform.md](platform.md)  
- [INDEX.md](INDEX.md)  
- `rad-platform/README.md`  
- ADR-0006 (`architecture/decisions/ADR-0006-rad-platform-kit.md`)  
