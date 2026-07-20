# Content Enhancement Consolidation — Final Report

**Generated:** 2026-07-20  
**Source:** `content-enhancement/`  
**Solution root:** `newuinextgen/`  
**Destructive dual-activation:** **REJECTED**

Machine inventory: `audit-reports/content-enhancement/file-inventory.json` (117 rows including `_extracted`).  
Decisions JSON: `audit-reports/content-enhancement/consolidation-decisions.json`.

---

## 1. Executive implementation summary

`content-enhancement` is an archive kit of **older standalone NextGen plugins** (Core v2, Plugin/Mission Control v1, platform seeder importer), a **deprecated SQL schema**, and an **implementation dashboard HTML**. It is **not** a theme and must **not** be installed alongside Companion.

Canonical owners remain:

| Responsibility | Owner |
|----------------|--------|
| Marketplace, matching, wallet, bookings, CPT, workflows, Studio | **NextGenTutors-Companion** |
| Dependency install / health | **NextGenTutors-Plugin-Manager** |
| Static HTML → pages | **NextGenTutors-Html-Importer** |
| Theme pages / defaults | **BeyondInfinity** (`inc/defaults`, `page-*.php`) |

**Controlled implementation performed (safe only):**

1. Full recursive inventory + extraction for analysis.  
2. Decision matrix: REJECT activation of all four plugins; KEEP dashboard as docs.  
3. Copied POPIA/transactional email HTML into Companion as **reference layouts** (not live overrides).  
4. Quarantined chat export JSON (PII risk).  
5. Documented DO-NOT-ACTIVATE in `content-enhancement/README.md`.  
6. **`NGC_Legacy_Plugin_Guard`** — strips/denies co-activation of Core/Plugin/Importer; admin notice + action-link block.  
7. **Plugin-Manager** `activate_plugin_file` deny-list for the same packages.  
8. Gap analysis: `functions-enhanced.php` → QUARANTINE (see `functions-enhanced-gap-analysis.md`).

**Not done (correctly):** merging Core PHP into Companion, replacing email defaults, running Core schema, activating plugins.

---

## 2. Directory inventory summary

### Top-level (6 files)

| File | Bytes | Role |
|------|------:|------|
| `nextgen-tutors-core-ready.zip` | 54 319 | Slim Core plugin v2 |
| `nextgen-tutors-core.zip` | 553 701 | Core v2 + resources/theme kit |
| `nextgen-tutors-plugin.zip` | 19 133 | Mission Control v1 |
| `nextgen-tutors-importer.zip` | 3 905 | Single-file platform seeder |
| `nextgen-tutors-schema.sql.deprecated` | 6 190 | Manual `wp_ngt_*` tables |
| `nextgentutors-implementation-dashboard.html` | 80 906 | Staging ops cockpit |

### Extracted (`_extracted/`)

| Package | Files | Languages |
|---------|------:|-----------|
| nextgen-tutors-core | 60 | PHP 41, HTML 6, sh/json/… |
| nextgen-tutors-core-ready | 37 | PHP 34 |
| nextgen-tutors-plugin | 13 | PHP 9 |
| nextgen-tutors-importer | 1 | PHP 1 |

**Empty/corrupt:** none found.  
**Secrets/PII:** chat export flagged → **quarantined**.  
**Hard-coded:** deprecated SQL uses `wp_` prefix; dashboard references SmartHead / older stack names.

---

## 3. Functional capability matrix

| Functional Area | Feature | Source Files | Current Equivalent | Status | Decision | Canonical Owner | Dependencies | Risk |
| --------------- | ------- | ------------ | ------------------ | ------ | -------- | --------------- | ------------ | ---- |
| Plugin bootstrap | Core v2 | `nextgen-tutors-core.php` | Companion bootstrap | DUPLICATE | REJECT | Companion | WP | CRITICAL if co-activated |
| Plugin bootstrap | Mission Control v1 | `nextgen-tutors.php` | Companion + NGCPM | DUPLICATE | REJECT | Companion / Plugin-Manager | WP | CRITICAL |
| REST | `ngt/v1` Core | `class-ngt-api.php` | `NGC_Rest` + legacy alias | CONFLICTING | REJECT | Companion | WP REST | CRITICAL |
| REST | `ngt/v1` Plugin | `rest-api.php` | same | CONFLICTING | REJECT | Companion | WP REST | CRITICAL |
| Database | `ngt_*` Core ×9 | `class-ngt-database.php` / SQL | `ngc_*` via `NGC_Database` | CONFLICTING | REJECT | Companion | MySQL | CRITICAL |
| Database | Plugin tables ×5 | `activation.php` | Companion + Core clash | CONFLICTING | REJECT | Companion | MySQL | CRITICAL |
| Marketplace | — | absent | `NGC_Marketplace` | VERIFIED (current) | KEEP | Companion | CPT tutors | — |
| Matching | — | absent | `NGC_Smart_Matching` | VERIFIED | KEEP | Companion | — | — |
| Wallet | — | absent | `NGC_Wallet` | VERIFIED | KEEP | Companion | — | — |
| Bookings | seed/Amelia refs | resources / importer | `NGC_Bookings` + Amelia | PARTIAL in source | KEEP current | Companion | Amelia | — |
| Earnings/payouts | Core + Plugin | payout classes | Companion payments/PayFast | DUPLICATE | REJECT source | Companion | PayFast | HIGH |
| POPIA consent | Core | verifier/consent | `ngc_consent_log` + platform admin | DUPLICATE concepts | KEEP current; EXTEND layouts | Companion | — | MEDIUM |
| Email HTML | Core templates | `templates/emails/*` | `NGC_Workflow_Email_Templates` | PARTIAL overlap | EXTEND reference copy | Companion assets | — | LOW |
| Platform seed | Importer AJAX | `nextgen-tutors-importer.php` | Companion integrate/seeder | DUPLICATE goals | REJECT plugin | Companion | FluentCRM/Amelia/… | HIGH |
| HTML page import | — | absent | Html-Importer | VERIFIED | KEEP | Html-Importer | — | — |
| Plugin install UX | Plugin REST | `/plugins/install` | Plugin-Manager | DUPLICATE | REJECT | Plugin-Manager | — | HIGH |
| Theme helpers | `functions-enhanced.php` | resources | BeyondInfinity + `ngc_*` shortcodes | DEPRECATED pattern | QUARANTINE/reference | Theme/Companion | SmartHead-era | HIGH |
| Ops dashboard | HTML | dashboard.html | Companion health/admin | NOT INTEGRATED | KEEP as docs | content-enhancement | — | LOW |
| Schema SQL | deprecated | `.sql.deprecated` | `NGC_Database` | DEPRECATED | REJECT | Companion | — | HIGH |

---

## 4. Duplicate and conflict report

| Conflict ID | Type | Implementation A | Implementation B | Behavioural Overlap | Risk | Resolution |
| ----------- | ---- | ---------------- | ---------------- | ------------------- | ---- | ---------- |
| CE-REST-001 | REST namespace | Core/Plugin `ngt/v1` | Companion legacy `ngt/v1` | Full route collision | CRITICAL | REJECT A |
| CE-DB-001 | Tables | Core `ngt_logs`/`ngt_earnings` | Plugin same names, different shape; Companion `ngc_*` | Schema corruption risk | CRITICAL | REJECT A & Plugin |
| CE-INSTALL-001 | Plugin installer | Plugin `/plugins/install` | Plugin-Manager | Same responsibility | HIGH | KEEP B |
| CE-SEED-001 | Platform seed | NGT Importer | Companion integrate/seeder | Pages/CRM/Amelia/Woo seed | HIGH | REJECT Importer plugin |
| CE-PAY-001 | Payouts | Core + Plugin payout | Companion PayFast/payments | Earnings batching | HIGH | KEEP Companion |
| CE-SHORT-001 | Shortcode names | Setup refs `ngt_tutor_grid` etc. | Companion `ngc_*` (legacy carousel only) | Naming confusion | MEDIUM | Do not register Core shortcodes |
| CE-PII-001 | Import artifact | chat-export JSON | — | Conversation/PII | HIGH | QUARANTINE |

---

## 5. Consolidation decisions

| Source Feature | Existing Target | Action | Migration | Compatibility Strategy | Validation |
| -------------- | --------------- | ------ | --------- | ---------------------- | ---------- |
| Core plugin activation | Companion | REJECT | None | Leave zips + `_extracted` offline | Do not appear in `wp plugin list` as active |
| Plugin v1 activation | Companion + NGCPM | REJECT | None | Same | Same |
| Importer plugin | Companion seeder / Html-Importer | REJECT | None | Document seed ownership | — |
| Email HTML layouts | Companion Studio/workflows | EXTEND (reference files) | Copy only | No change to `ngc_email_templates` option | Files present under Companion assets |
| Chat export | — | QUARANTINE | Moved to quarantine | Stub left in extract | Manifest + restore script |
| Deprecated SQL | NGC_Database | REJECT | None | Never run on prod | File marked `.deprecated` |
| Dashboard HTML | Docs | KEEP | None | Not enqueued | Manual read only |
| `functions-enhanced.php` | BeyondInfinity / Companion | QUARANTINE/reference | None | Not required by theme | Not included |

---

## 6. Architecture changes

- **No new bootstrap / service container.**  
- **No second plugin** under `wp-content/plugins` from this kit.  
- Added **reference email layout folder** under Companion assets.  
- Added **analysis extract** under `content-enhancement/_extracted` (never production-loaded).  
- Quarantine path for PII artifact.

---

## 7. Error-handling implementation

| Operation | Failure Modes | Current Handling | Required Handling | User Response | Log Event | Retry |
| --------- | ------------- | ---------------- | ----------------- | ------------- | --------- | ----- |
| Activate Core beside Companion | REST/DB fatals | N/A (blocked by policy) | Prevent activation | Admin: do not install | `CONTENT_CONFLICT_DETECTED` | No |
| Quarantine restore | Missing manifest | `restore-quarantined-files.php` exits 0 if empty | Keep | CLI message | `CONTENT_MIGRATION_*` | No |
| Email reference load | Missing file | Not auto-loaded | N/A until Studio import | — | — | — |

No new silent `catch` blocks introduced. Full Core exception rewrite **not** merged (would duplicate Companion).

---

## 8. Logging implementation

| Event Code | Component | Severity | Trigger | Required Fields | Sensitive Fields to Redact |
| ---------- | --------- | -------- | ------- | --------------- | -------------------------- |
| CONTENT_SCAN_STARTED | content-enhancement audit | INFO | Inventory start | path, file_count | — |
| CONTENT_SCAN_COMPLETED | content-enhancement audit | INFO | Inventory done | rows, packages | — |
| CONTENT_DUPLICATE_DETECTED | consolidation | WARNING | REST/DB clash found | conflict_id, a, b | — |
| CONTENT_CONFLICT_DETECTED | consolidation | CRITICAL | Co-activation risk | conflict_id | — |
| CONTENT_MERGE_COMPLETED | email layouts | INFO | Reference copy | dest_count | — |
| CONTENT_FILE_UNREADABLE | quarantine | WARNING | PII artifact | relative_path | message bodies |
| INTEGRATION_UNAVAILABLE | — | — | Not introduced | — | — |

Canonical logger remains Companion/`NGC` diagnostics — **no second logger** from Core.

---

## 9. Security remediation

| Finding | Severity | Action |
|---------|----------|--------|
| Dual REST `ngt/v1` | CRITICAL | REJECT activation (documented) |
| Dual/incompatible `ngt_*` schemas | CRITICAL | REJECT activation |
| Chat export possible PII | HIGH | QUARANTINED |
| Core verifier `force_check` GET bypass (full Core only) | HIGH | Not merged |
| Plugin capability `ngt_manage` parallel to Companion caps | MEDIUM | REJECT plugin |
| Deprecated SQL with hardcoded prefix | MEDIUM | REJECT |

No WordPress core edits. No third-party plugin edits.

---

## 10. Data migration results

**No database migration executed** (correct — Companion `ngc_*` remains canonical).  
Deprecated SQL **must not** be applied.

---

## 11. Changed-file manifest

| Path | Change |
|------|--------|
| `content-enhancement/README.md` | **Added** — DO-NOT-ACTIVATE |
| `content-enhancement/_extracted/**` | **Added** — analysis extract (not for production load) |
| `content-enhancement/_extracted/.../chat-export-full-next-gen.json` | **Modified** — stub after quarantine |
| `audit-reports/content-enhancement/file-inventory.json` | **Added** |
| `audit-reports/content-enhancement/file-inventory.csv` | **Added** |
| `audit-reports/content-enhancement/consolidation-decisions.json` | **Added** |
| `audit-reports/content-enhancement/FINAL-REPORT.md` | **Added** (this file) |
| `audit-reports/quarantine/content-enhancement-*/chat-export-*.json` | **Added** quarantine |
| `audit-reports/quarantine/manifest-content-enhancement-chat-*.json` | **Added** |
| `NextGenTutors-Companion/assets/email-layouts/content-enhancement-reference/*` | **Added** 6 HTML + README |
| `NextGenTutors-Companion/includes/diagnostics/class-ngc-legacy-plugin-guard.php` | **Added** deny-list guard |
| `NextGenTutors-Companion/includes/class-ngc-plugin.php` | **Modified** — register guard module |
| `NextGenTutors-Plugin-Manager/includes/class-ngcpm-discovery.php` | **Modified** — deny activate of legacy packages |
| `audit-reports/content-enhancement/functions-enhanced-gap-analysis.md` | **Added** |

**Removed from production path:** none (zips retained as source archives).

---

## 12. Automated test results

| Command | Result |
|---------|--------|
| Recursive inventory PowerShell | **VERIFIED** — 117 files |
| Zip extract | **VERIFIED** — 4 packages |
| SHA256 of top-level artifacts | **VERIFIED** |
| Secret/PII pattern scan | **VERIFIED** — 1 hit quarantined |
| `php -l` guard + NGCPM | **VERIFIED** |
| Docker `NGC_Legacy_Plugin_Guard` + `is_denied` | **VERIFIED** (`GUARD_OK` / `DENY_OK`) |
| PHPUnit / full WP smoke for this change | **NOT VERIFIED** this pass (no plugin activation to test) |

---

## 13. Remaining risks

| Risk | Status | Impact | Remediation |
|------|--------|--------|-------------|
| Operator installs Core/Plugin zip | MITIGATED | Was REST/DB break | `NGC_Legacy_Plugin_Guard` + NGCPM deny |
| Someone runs deprecated SQL | OPEN | Wrong tables | Leave `.deprecated`; document REJECT |
| Email reference mistaken for live templates | LOW | Design drift | README in assets folder |
| `_extracted` mistaken for deployable plugin | MEDIUM | Accidental copy | README + quarantine policy |
| Income calculator shortcode (legacy name only) | LOW | Missing UI if product wants it | Build only on explicit requirement |

---

## 14. Rollback procedure

1. Delete `NextGenTutors-Companion/assets/email-layouts/content-enhancement-reference/` if reference copy unwanted.  
2. Restore chat export: `php scripts/restore-quarantined-files.php` (uses newest quarantine manifest).  
3. Remove `content-enhancement/_extracted/` if disk cleanup needed (zips remain source of truth).  
4. Remove `NGC_Legacy_Plugin_Guard` from `$modules` and delete the class file to disable the deny-list.  
5. Revert NGCPM `activate_plugin_file` deny checks.  
6. No DB rollback required (no schema applied).

---

## 15. Final verification matrix

| Item | Status |
|------|--------|
| Every readable file inventoried | **VERIFIED** |
| Features classified vs Companion | **VERIFIED** |
| Duplicate REST/DB conflicts identified | **VERIFIED** |
| Dual-activation prevented by policy/docs | **VERIFIED** |
| Dual-activation hard-block (Companion + Plugin-Manager) | **VERIFIED** |
| One canonical owner per responsibility | **VERIFIED** |
| Safe asset EXTEND (emails) | **VERIFIED** |
| PII quarantine | **VERIFIED** |
| `functions-enhanced` gap analysis | **VERIFIED** (QUARANTINE) |
| Core/Plugin code merged into Companion | **REJECTED** (correct) |
| Error-handling rewrite of Core | **NOT VERIFIED** / not in scope after REJECT |
| Full automated regression suite | **NOT VERIFIED** |
| Production claim “complete merge of all Core features” | **FAILED** if claimed — **not claimed** |

### Unresolved (explicit)

```text
Status: VERIFIED (remediated)
Affected files: class-ngc-legacy-plugin-guard.php; class-ngcpm-discovery.php
Verified cause: Archives could be uploaded manually
Missing evidence: none for deny path (GUARD_OK/DENY_OK in Docker)
Impact: Accidental activation blocked / force-deactivated
Required remediation: none
```

```text
Status: NOT VERIFIED (optional product gap only)
Affected files: resources/functions-enhanced.php / setup refs to [ngt_income_calculator]
Verified cause: Named in setup scripts; Companion uses ngc_* dashboards
Missing evidence: Explicit product requirement for income calculator
Impact: Low
Required remediation: Implement only if product requires it
```
