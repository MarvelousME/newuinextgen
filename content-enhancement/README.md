# content-enhancement — DO NOT ACTIVATE

**Status:** Analysis complete · dual-activation **REJECTED**  
**Canonical platform owner:** `NextGenTutors-Companion` (+ BeyondInfinity theme, Plugin-Manager, Html-Importer)

## Contents

| Artifact | Decision |
|----------|----------|
| `nextgen-tutors-core-ready.zip` | **REJECT** activation — older Core v2; collides with Companion `ngt/v1` alias + `ngc_*` schema |
| `nextgen-tutors-core.zip` | **REJECT** activation; **EXTEND** Companion selectively from emails/imports (reference only) |
| `nextgen-tutors-plugin.zip` | **REJECT** — Mission Control v1 superseded by Companion + Plugin-Manager |
| `nextgen-tutors-importer.zip` | **REJECT** plugin; seed ideas already covered by Companion integrate/seeder |
| `nextgen-tutors-schema.sql.deprecated` | **REJECT** for production |
| `nextgentutors-implementation-dashboard.html` | **KEEP** as ops documentation only (not loaded by WP) |
| `_extracted/` | Analysis workspace only — **never** enqueue or activate from here |

## Critical collision

Companion already registers a legacy mirror of `ngc/v1` onto **`ngt/v1`**. Activating Core or Plugin alongside Companion causes REST and DB conflicts.

**Runtime guard (VERIFIED):** `NGC_Legacy_Plugin_Guard` in Companion + Plugin-Manager deny-list block activation / force-deactivate.

## Safe reuse already performed

- POPIA / transactional HTML email layouts copied to:  
  `NextGenTutors-Companion/assets/email-layouts/content-enhancement-reference/`  
  (reference assets for Studio/workflows — not auto-replacing live templates)
- Chat export JSON quarantined under `audit-reports/quarantine/` (PII risk)

## Full reports

See `audit-reports/content-enhancement/`.
