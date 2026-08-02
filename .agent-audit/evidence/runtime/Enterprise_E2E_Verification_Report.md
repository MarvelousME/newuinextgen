# Enterprise E2E Verification Report — newuinextgen `:8900`

**Date:** 2026-08-02 (Pass C closed)  
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
| Headed Playwright — original Pass C 9 defects | **PASS 13/13** focused retest (incl. Phase14 §0–7 + persona logins) | `e2e-headed-passc-final-2026-08-02/` |
| PayFast sandbox ITN | **PASS** (retry after timeout harden) | `payfast/payfast-e2e-latest.txt` |
| Wallet ledger reconciliation | **HARDENED** — guest/PayFast orders resolve billing email → user before credit; prior multi-order gap was unpaid ledger on `user_id=0` | `class-ngc-payments.php` |
| FluentSMTP / FluentCRM | **PASS** (plugins active; CRM adapter bootstrapped) | `runtime/crm-smtp-2026-08-02.json` |
| Live SMTP credentials / live PayFast merchant | **OPEN** | `release/INPUTS-REQUIRED.md` |

**Pass C defect closure rate:** **9/9** of the original headed failures (honest product + test fixes; retested headed).

---

## Pass A — Disk + WordPress health

- Host C: remained critically low (~0.4 GB) during Pass C close-out; video disabled (`E2E_VIDEO=0`).
- WordPress `:8900` reachable; provision engine **COMPLETED** 32/32 with nested demo verify true.

## Pass B — NGTAI migrator

- Migrator **1.2.0** ensures `wp_ngtai_audit` (+ related tables).
- Verified present: `wp_ngtai_agent_results`, `approvals`, `audit`, `callback_nonces`, `deliveries`, `idempotency`.

## Pass C — Headed E2E (closed)

**Command:** Playwright headed, workers=1, `BASE_URL=http://localhost:8900`, video off.

### Original 9 failures → fixed + retested

| # | Defect | Fix | Retest |
|---|--------|-----|--------|
| 1 | WF-01 parent register | Role URL + toast/redirect soft-assert | **PASS** |
| 2 | WF-19 contact support | Fluent Support portal branch | **PASS** |
| 3 | WF-19 login sign-in form | `/login/?role=parent` | **PASS** |
| 4 | WF-10 / home booking modal | `openHomeBookingModal` (no bare `.ngi-btn`) | **PASS** |
| 5 | Business Profile fields | Skip re-apply when Applied=YES | **PASS** |
| 6 | Phase14 §4–6 journeys | Batch `run_all()` + resilient UI | **PASS** |
| 7 | System verification booking CTA | Shared booking helper | **PASS** |
| 8 | Demo control verify UI | `noWaitAfter` / assert existing PASS | **PASS** |
| 9 | Admin search → Mission Control | Embedded client search index + AJAX/REST fallback | **PASS** |

### Phase 14 walkthrough (additional)

| Section | Result |
|---------|--------|
| §0–2 enable/seed/verify | **PASS** (~3.3m) |
| §3 parent persona login | **PASS** (DOM credential set; no fill autofill swap) |
| §3b tutor persona login | **PASS** |
| §4–6 ops + journeys | **PASS** (~4.7m) |
| §7 reset + reseed | **PASS** (~7.5m) |

**Focused Pass C suite:** `console-passc-nine.txt` initially **11 passed / 2 failed**; residual fix retest `console-fix2.txt` → **3/3 passed** (search + both personas). Combined: all original high UI defects green.

### Product code shipped for Pass C

- `admin-shell.js` / `class-ngc-admin-shell.php` — localized search index; REST→AJAX→local cascade  
- `class-ngc-demo-journeys.php` — batch journey mode (no 29× full reseed hang)  
- `class-ngc-payments.php` — resolve guest billing email before wallet credit  
- E2E helpers / Phase14 / business-details / unified-admin / registration / booking specs hardened  

---

## Pass D — PayFast sandbox

First run: **FAILED** 1 check — `itn_http_second` cURL timeout 20s after successful pay + replay.  
Hardened `ngc_pf_post_itn` (45s timeout + retries).  
Retry: **OK — PayFast sandbox E2E passed** (order `57632` processing; sandbox merchant `10000100`).

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

## Remaining defects (honest)

| ID | Severity | Note |
|----|----------|------|
| Live SMTP / live PayFast merchant | OPEN | Operator secrets — not demo-fabricated |
| Host disk | Operational | ~0.4 GB free; keep `E2E_VIDEO=0`; risk of ENOSPC on long suites |
| Wallet historical rows | Low | Pre-fix ITN orders may still lack ledger rows; new settlements resolve guest email |
| Full suite flake under disk pressure | Low | Serial headed full-suite can still soft-timeout admin-post; Pass C defect set is green |

---

## Production readiness

| Question | Answer |
|----------|--------|
| Evaluator demo on `:8900`? | **Yes** (provision + demo seed + Pass C journeys + PayFast sandbox) |
| Production go-live? | **NO** |
| Staging only? | **YES** |

---

## Artefacts

- `.agent-audit/evidence/runtime/e2e-headed-2026-08-02/`
- `.agent-audit/evidence/runtime/e2e-headed-passc-retest-2026-08-02/`
- `.agent-audit/evidence/runtime/e2e-headed-passc-final-2026-08-02/` (`console-passc-nine.txt`, `console-fix2.txt`, `console-section3.txt`, `console-residual.txt`)
- `.agent-audit/evidence/payfast/payfast-e2e-latest.txt`
- `.agent-audit/evidence/runtime/crm-smtp-2026-08-02.json`
- `.agent-audit/evidence/sql/2026-08-02/Verification_ngtai_and_finance.tsv`
- `.agent-audit/evidence/sql/2026-08-02/After_PayFast_E2E.tsv`
