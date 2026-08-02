# System setup gap matrix — Master autonomous prompt vs repo

**Updated:** 2026-08-02  
**Theme package (mandatory):** `NextGenTutors-BeyondInfinity` (never BeyondIdentity)

| Area | Expected (master prompt) | Actual | Status | Remediation |
|------|--------------------------|--------|--------|-------------|
| Theme | NextGenTutors-BeyondInfinity | Package present; Docker mounts theme | PASS | — |
| Orchestrator | `wp ngt system *` | Companion CLI + Mission Control mirror | PASS | Prefer Companion surface |
| **Provisioning** | Versioned 32-step wizard + dry-run + lock + rollback | `NGC_Provisioning_Engine` + Setup Wizard (`ngc-setup-wizard`) + `wp ngt provision *` — verified COMPLETE on Docker 2026-08-02 | PASS | `wp ngt provision run --force-safe --allow-demo` |
| Business SSOT | Centralized profile JSON | `config/nextgentutors-business-profile.json` + `NGC_Business_Profile` | PASS | `wp ngt system configure --force-safe` |
| Roles | Full ops matrix | Core + finance/safeguarding/ops roles | PASS | Mapped via profile `role_map` |
| Companion | Configured domain | Active; demo/privacy/metrics | PARTIAL | Live Amelia/CRM/LMS sync limited |
| Pages/menus | Full registry | Theme pages-registry + sync | PARTIAL | Run sequential setup + page repair |
| Woo/PayFast | Sandbox verified | Official PayFast sandbox E2E **PASS** on `:8900` 2026-08-02 (`payfast-e2e-latest.txt`); live merchant OPEN | PASS (sandbox) / OPEN (live) | Keep sandbox for demo; supply live creds only when authorized |
| FluentSMTP/CRM | Configured + seeded | Plugins active + CRM adapter bootstrapped 2026-08-02; SMTP transport credentials open | PARTIAL | Configure real SMTP in FluentSMTP admin |
| Amelia/LMS | Populated | Integration classes; live seed PARTIAL | PARTIAL | Approve tutors before Amelia employees |
| AutomatorWP/GamiPress | Triggers active | Present; UUID insert bug fixed 2026-08-02 | PARTIAL | Trigger after SMTP |
| Phase 14 demo | Relational | Seeder + provision step `demo-journeys` | PASS WITH LIMITATIONS | Evidence packs may need host export |
| Release packaging | ZIPs + checksums + manifest | `scripts/build-release.ps1` → `release/` + `dist/` | PASS | Verified 2026-08-02 |
| Docs deliverables | 14 manuals + 15 SVGs | `release/documentation/*`, `release/diagrams/*` | PASS (MD+SVG); PDF PENDING | Operator PDF export |
| AI bridge | NGTAI package | Migrator **1.2.0**; `wp_ngtai_audit` present (24 rows) on `:8900` | PASS | Re-run migrator on older DBs |
| Headed E2E | Journey suite green | **61/72 passed** headed on `:8900` 2026-08-02 | PASS WITH LIMITATIONS | See `Enterprise_E2E_Verification_Report.md` |
| Production deploy | Authorized two-stage | **Not authorized this session** | BLOCKED | Explicit operator authorization required |

## Commands

```bash
wp ngt provision catalogue
wp ngt provision run --dry-run
wp ngt provision run --force-safe --allow-demo
wp ngt system inspect
wp ngt system preflight
wp ngt system configure --force-safe
wp ngt system seed --force-safe
wp ngt system verify
wp ngt system run-all --force-safe
wp ngt system export-report
```

```powershell
powershell -File scripts/build-release.ps1
cd e2e
$env:BASE_URL='http://localhost:8900'
npm run test:system-headed
```
