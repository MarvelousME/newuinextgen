# 14 — Traceability Matrix

**Release:** BI 1.9.17 / NGC 1.9.5 · **Generated:** 2026-08-02  
**PDF:** PDF export pending operator tooling (pandoc/wkhtmltopdf). Markdown is authoritative.

Maps requirements → implementation → evidence. Marks are honest.

| Req ID | Requirement | Implementation | Evidence / test | Status |
|--------|-------------|----------------|-----------------|--------|
| R-SSOT | Business identity ZAR / JHB / contacts | `config/nextgentutors-business-profile.json` + step `business-profile` | JSON in repo; INPUTS conflict table | VERIFIED (code) |
| R-THEME | BeyondInfinity presentation | Theme 1.9.17 | `BI_VERSION`, style.css | VERIFIED |
| R-DOM | Domain plugin | Companion 1.9.5 | `NGC_VERSION` | VERIFIED |
| R-PROV | Versioned provisioning | `NGC_Provisioning_Engine` 32 steps | `class-ngc-provisioning-*.php`, wizard | VERIFIED (code) |
| R-WIZ | Setup Wizard admin | `ngc-setup-wizard` | `class-ngc-provisioning-admin.php` | VERIFIED (code) |
| R-CLI-P | Provision CLI | `wp ngt provision *` | `class-ngc-system-cli.php` | VERIFIED (code) |
| R-CLI-S | System CLI | `wp ngt system *` | Same | VERIFIED (code) |
| R-ROLES | Role map | Business profile + step `roles` | SSOT `role_map` | VERIFIED (code) |
| R-MATCH | Tutor matching | Companion matching modules | Journeys MATCH-*; runtime PARTIAL | PARTIAL |
| R-BOOK | Booking | Companion + Amelia adapter | BOOK-* journeys; plugin may be absent | PARTIAL |
| R-PAY | PayFast settlement | Woo + Companion payments | Sandbox evidence historical; live OPEN | PARTIAL |
| R-AI | AI assist / governance | AI-Integration + Companion AI | Keys OPEN; env issues possible | PARTIAL |
| R-SEC | IDOR / access gates | `NGC_Access` | Backlog IDOR-001 FIXED | VERIFIED (code claim) |
| R-PRIV | Minor PII export/erase | Privacy modules | PRIV-001 FIXED in backlog | PARTIAL (re-verify host) |
| R-SFG | Safeguarding queue | Safeguarding admin | SFG-001 FIXED; contacts OPEN | PARTIAL |
| R-OBS | Metrics / health | Observability service + metrics route | OBS-001 FIXED in backlog | PARTIAL |
| R-DEMO | Phase 14 relational demo | `includes/demo/*` | Demo README + journeys | COMPLETE WITH LIMITATIONS |
| R-REL | Reproducible release hashes | build + SHA256 files | `.agent-audit/evidence/release/*1.9.17*` | VERIFIED |
| R-SEC-PKG | No secrets in package | INPUTS-REQUIRED | Release policy | VERIFIED (policy) |
| R-PROD | Production deploy | Host pipeline | — | NOT AUTHORIZED / UNVERIFIED |
| R-DOC | Delivery documentation | `release/documentation/*` | This pack | VERIFIED (created) |
| R-DIAG | Architecture SVGs | `release/diagrams/*` | 15 SVG files | VERIFIED (created) |

## Cross-links

| Doc | Covers |
|-----|--------|
| 01–02 | Functional + technical reqs |
| 08 | Test evidence |
| 09 / INPUTS | Config + secrets |
| 13 | Limitations affecting matrix rows |
