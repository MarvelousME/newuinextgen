# System setup gap matrix — Master autonomous prompt vs repo

**Updated:** 2026-07-20  
**Theme package (mandatory):** `NextGenTutors-BeyondInfinity` (never BeyondIdentity)

| Area | Expected (master prompt) | Actual | Status | Remediation |
|------|--------------------------|--------|--------|-------------|
| Theme | NextGenTutors-BeyondInfinity | Package present; Docker mounts theme | PASS | — |
| Orchestrator | `nextgentutors-system-orchestrator` + `wp ngt system *` | Implemented as Companion CLI `wp ngt system …` | PASS | Prefer Companion surface over new plugin |
| Business SSOT | Centralized profile JSON | `config/nextgentutors-business-profile.json` + `NGC_Business_Profile` | PASS | `wp ngt system configure --force-safe` |
| Roles | Full ops matrix | Core + ngc_compliance/safeguarding/operations/content/auditor/ai_ops | PASS | Mapped via profile `role_map` |
| Companion | Configured domain | Active; demo/privacy/metrics | PARTIAL | Live Amelia/CRM/LMS sync limited |
| Pages/menus | Full registry | Theme pages-registry + sync | PARTIAL | Run sequential setup + page repair |
| Woo/PayFast | Sandbox verified | Code + scripts; secrets env-only | PARTIAL | Supply sandbox creds to finish payment e2e |
| FluentSMTP/CRM | Configured + seeded | Plugins may be active; full list/tag seed PARTIAL | PARTIAL | Configure SMTP then CRM via admin |
| Amelia/LMS | Populated | Integration classes; live seed PARTIAL | PARTIAL | Approve tutors before Amelia employees |
| AutomatorWP/GamiPress | Triggers active | Present; not fully verified headed | PARTIAL | Trigger after SMTP |
| Phase 14 demo | Relational | Seeder + headed e2e green | PASS | `wp ngt system seed` / `npm run test:phase14` |
| E2E all WF | Headed every workflow | 12 specs incl. gaps smoke + phase14 | PARTIAL | Deep PayFast/refund still sandbox-gated |
| Docs deliverables | `/docs/` full list | Partial + BESPOKE + this matrix | PARTIAL | Export via `wp ngt system export-report` |

## Commands

```bash
wp ngt system inspect
wp ngt system preflight
wp ngt system configure --force-safe
wp ngt system seed --force-safe
wp ngt system verify
wp ngt system run-all --force-safe
wp ngt system export-report
```

```powershell
cd e2e
$env:BASE_URL='http://localhost:8900'
npm run test:all-headed
```
