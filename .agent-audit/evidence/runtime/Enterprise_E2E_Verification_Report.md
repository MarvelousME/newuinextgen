# Enterprise E2E Verification Report — newuinextgen `:8900`

**Date:** 2026-08-02  
**Stack:** Docker `newuinextgen` → `http://localhost:8900`  
**Packages:** BeyondInfinity theme **1.9.17** · Companion NGC **1.9.5** · NGTAI migrator **1.2.0**  
**Verdict:** **COMPLETE WITH LIMITATIONS**  
**Production deploy:** **NO** (not authorized) · staging/demo Docker only

---

## Scorecard

| Gate | Result | Evidence |
|------|--------|----------|
| Provisioning 32/32 | **PASS** (`COMPLETED`, `ok=true`, demo verify true, `seed_version=14.0.0`) | provisioning run 2026-08-02 |
| NGTAI tables incl. `wp_ngtai_audit` | **PASS** (6 tables; audit rows=70 at close) | `sql/2026-08-02/Verification_ngtai_and_finance.tsv` |
| Headed Playwright journeys | **61 passed / 9 failed / 2 did not run** (72 planned, ~1.1h) | `e2e-headed-2026-08-02/` |
| PayFast sandbox ITN | **PASS** (retry after timeout harden) | `payfast/payfast-e2e-latest.txt` |
| Wallet ledger reconciliation | **PARTIAL** — credit `order:57629` R320 present; later ITN-paid orders processing without additional ledger rows | `sql/2026-08-02/After_PayFast_E2E.tsv` |
| FluentSMTP / FluentCRM | **PASS** (plugins active; CRM adapter bootstrapped) | `runtime/crm-smtp-2026-08-02.json` |
| Live SMTP credentials / live PayFast merchant | **OPEN** | `release/INPUTS-REQUIRED.md` |

**Headed journey rate:** **61/72 ≈ 84.7%** (honest; not green).

---

## Pass A — Disk + WordPress health

- Host C: remained critically low (~0.2–0.7 GB) during the run; video disabled (`E2E_VIDEO=0`).
- WordPress `:8900` reachable; provision engine **COMPLETED** 32/32 with nested demo verify true.

## Pass B — NGTAI migrator

- Migrator **1.2.0** ensures `wp_ngtai_audit` (+ related tables).
- Verified present: `wp_ngtai_agent_results`, `approvals`, `audit`, `callback_nonces`, `deliveries`, `idempotency`.

## Pass C — Headed E2E

**Command:** Playwright headed, workers=1, `BASE_URL=http://localhost:8900`, video off.

**Passed highlights:** student/tutor registration forms, find-a-tutor intake, ops REST auth gates, demo role dashboards (parent/tutor/student/admin), Mission Control tabs, most system-verification public pages, unified admin shell, Phase 14 seed + parent/tutor login sections.

**Failed (9):**

1. WF-01 parent register child form  
2. WF-19 contact support form  
3. WF-19 login page sign-in form visibility  
4. WF-10 homepage booking modal / assessment CTA  
5. Companion Business Profile admin company fields  
6. Phase 14 sections 4–6 (booking graph / ops / journeys evidence)  
7. System verification booking/match CTA  
8. System verification demo control verify UI  
9. Unified admin search → Mission Control  

**Skipped / did not run:** 2 (logout cleanup dependency + one follow-on).

Disk pressure and long serial timeouts contributed; several failures look selector/UI-gate related rather than provision/data absence.

## Pass D — PayFast sandbox

First run: **FAILED** 1 check — `itn_http_second` cURL timeout 20s after successful pay + replay.  
Hardened `ngc_pf_post_itn` (45s timeout + retries).  
Retry: **OK — PayFast sandbox E2E passed** (order `57632` processing; sandbox merchant `10000100`).

SQL: multiple `wc-processing` HPOS orders (`57629`, `57631`, `57632`, `57634`); ledger credit confirmed for `order:57629`.

## Pass E — SMTP / CRM + report

```json
fluent_smtp_active: true
fluent_crm_active: true
fluent_support_active: true
crm_available: true
crm_bootstrapped: true
payfast.enabled/sandbox: true / merchant_id 10000100
```

SMTP **transport credentials** and production PayFast secrets remain operator OPEN items — not invented.

---

## Remaining high defects (honest)

| ID | Severity | Note |
|----|----------|------|
| E2E-UI forms/CTAs | High | Parent register, contact, login form, booking modal flakes/fails under headed suite |
| Admin Business Profile fields | Medium | Selector/data mismatch in headed admin |
| Admin search Mission Control | Medium | `#ngt-admin-search-results` empty |
| Demo verify UI + Phase14 §4–6 | Medium | CLI/provision verify PASS; headed UI path incomplete |
| Wallet multi-credit | Low/Med | Repeat ITN pays order without new ledger row (investigate product credit path) |
| Host disk | Operational | Blocks video/traces; risk of ENOSPC |

---

## Production readiness

| Question | Answer |
|----------|--------|
| Evaluator demo on `:8900`? | **Yes with limitations** (provision+demo seed+PayFast sandbox+most journeys) |
| Production go-live? | **NO** |
| Staging only? | **YES** |

---

## Artefacts

- `.agent-audit/evidence/runtime/e2e-headed-2026-08-02/`
- `.agent-audit/evidence/payfast/payfast-e2e-latest.txt`
- `.agent-audit/evidence/runtime/crm-smtp-2026-08-02.json`
- `.agent-audit/evidence/sql/2026-08-02/Verification_ngtai_and_finance.tsv`
- `.agent-audit/evidence/sql/2026-08-02/After_PayFast_E2E.tsv`
