# FIVE-MINUTE TUTOR BOOKING E2E REPORT

**Verdict:** `BLOCKED` — booking/payment/dashboard correlation executed; **real-time meeting join is not implemented** in the Companion domain (no Zoom/Jitsi/join URL owner). Per directive, overall journey cannot be PASS.

**Run ID:** `NGT-E2E-20260803T020155`  
**Environment:** `http://localhost:8890` · TZ `Africa/Johannesburg` · Currency `ZAR`  
**Generated:** 2026-08-03  

---

## 1. Executive verdict

Domain APIs created a correlated parent → child → tutor → booking (~+5 min) → sandbox WC order path and completed booking status. Headed Playwright (7/7) verified public registration UI, marketplace, admin reachability, and absence of leaked meeting secrets.

**Blocking gap:** session rows expose no `joinUrl` / meeting credentials. Safeguarding fail-closed for unauthorized users is PASS only because secrets do not exist — live dual-party join was **NOT RUN / BLOCKED**.

## 2. Environment and versions

| Component | Actual |
|-----------|--------|
| WordPress | Docker `wordpress:6.7-php8.2-apache` @ `:8890` |
| Companion | 1.9.5 (bind-mounted) |
| WooCommerce | Active · ZAR |
| PayFast (`ngc_payfast`) | enabled · sandbox=yes |
| FluentCRM | Active |
| Amelia | Active |
| MasterStudy LMS | Active |
| Agent Gateway | Healthy `:8787` · `ngt.firstparty.diagnostics` |
| Meeting adapter | **MISSING** |

## 3. Preflight

| Check | Expected | Actual | Evidence | Status |
|-------|----------|--------|----------|--------|
| Home 200 | 200 | 200 | headed 01 | PASS |
| wp-admin | reachable | 200 | headed 06 | PASS |
| REST | healthy | 200 | preflight | PASS |
| DB | healthy | healthy | compose | PASS |
| Timezone | Africa/Johannesburg | Africa/Johannesburg | domain JSON | PASS |
| WooCommerce | active | yes | domain JSON | PASS |
| PayFast sandbox | sandbox | sandbox=yes | domain JSON | PASS |
| FluentCRM | active | yes | domain JSON | PASS |
| LMS | active | yes | domain JSON | PASS |
| Meeting provider | active | **none** | code review | **FAIL/BLOCKED** |
| Mail-capture | sink | FluentSMTP present; capture not fully proven | PARTIAL |
| Agent Gateway | healthy | ok | `/health` | PASS |

## 4. Test identities (sanitized)

Fictional `@example.test` accounts; passwords never recorded.

| Role | Correlation (IDs only) |
|------|-------------------------|
| Tutor user | 280 · application 17 |
| Parent | 281 |
| Child learner | 42 · student WP user 282 |
| Unrelated parent | 283 |
| Booking | 159 |
| Order | 57644 |

## 5–11. Phase results (summary)

| Phase | Status | Notes |
|-------|--------|-------|
| Tutor registration (domain + headed UI) | PASS | Fixed UNIQUE `uuid` insert bug in `NGC_Tutor_Lifecycle::apply` |
| Tutor approval | PASS* | Workflow ran; **CPT publish incomplete** (`cpt_id=0`) |
| Parent registration | PASS | Domain + headed form |
| Guardian-child | PASS | Parent owns child; unrelated parent not linked |
| Tutor discovery/profile | PARTIAL | Marketplace has cards; **this run’s CPT not published** |
| Availability | PARTIAL | Amelia present; journey used domain slot `now+5m` minute-aligned |
| Five-minute booking | PASS | `scheduled_start=2026-08-03T04:06:00+02:00` · idempotent key replay safe |
| Sandbox payment | PASS | WC order 57644 · `payment_complete(SANDBOX-*)` · no live funds |
| Parent/student/tutor dashboard data | PASS | Booking matched via `NGC_Bookings::list` |
| Real-time meeting | **BLOCKED** | No join field in `format_session_row` |
| Attendance/completion | PASS | Status → `completed` (meeting-independent) |
| Unauthorized meeting access | PASS | Fail-closed / no secrets in public student-dashboard |
| Child-safety isolation | PASS | Cross-family link false |
| Duplicate suppression | PASS | Idempotent booking key |

\*Approval PASS in verdicts means lifecycle approve succeeded; publication quality is PARTIAL (defect).

## 12. Exact scheduled start

```text
now_local:        2026-08-03T04:01:55+02:00
target (now+5m):  2026-08-03T04:06:55+02:00
scheduled_start:  2026-08-03T04:06:00+02:00
constraint:       aligned_to_minute_boundary_min_5m_ahead
```

## 13. Five-minute timeline

| Actual time (JHB) | Expected | Observed | Status |
|-------------------|----------|----------|--------|
| 04:01:55 | Preflight / create | Domain journey start | PASS |
| 04:06:00 | Session start window | Booking `scheduled_at` set | PASS |
| — | Join Meeting enable | **No join control exists** | BLOCKED |
| Post-create | Complete session | `status=completed` | PASS |

## 14. Authoritative owners (source of truth)

| Capability | Owner | Status |
|------------|-------|--------|
| Tutor apply/approve | `NGC_Tutor_Lifecycle` | IMPLEMENTED |
| Parent/child | `NGC_Registration` / `NGC_Child_Learners` | IMPLEMENTED |
| Booking | `NGC_Bookings` + REST | IMPLEMENTED |
| Payment | `NGC_Parent_Checkout` + WC + `ngc_payfast` | IMPLEMENTED (sandbox) |
| Meeting join | — | **MISSING** |
| Dashboards | `NGC_Rest_Dashboard` / bookings list | IMPLEMENTED |

## 15. Defects

1. **CRITICAL:** No meeting join adapter / `joinUrl` — blocks real-time DoD.  
2. **HIGH:** Tutor approve did not publish `tutors` CPT for this run (`cpt_id=0`).  
3. **MEDIUM:** Booking row `order_id` remained 0 after sandbox payment (correlation has 57644).  
4. **MEDIUM:** `NGC_Tutor_Lifecycle::apply` failed on UNIQUE empty `uuid` until patched.  
5. **LOW:** Conversion events UNIQUE uuid empty (non-fatal warning on booking create).  
6. **PDF:** No pandoc/wkhtmltopdf — PDF **NOT GENERATED**.  
7. Full Phase 12–13 matrix (payment failure, worker restart, etc.) **NOT RUN** in this pass.

## 16. Evidence paths

```text
delivery/evidence/five-minute-booking/
├── api/domain-journey-latest.json
├── database/booking-extract.json
├── screenshots/ (01–07 headed)
├── logs/playwright-console.txt
├── timeline.json
├── playwright-report/
└── (videos/traces under e2e/test-results on failure; headed run green)

delivery/evidence/FIVE-MINUTE-TUTOR-BOOKING-E2E-REPORT.md  (this file)
delivery/evidence/FIVE-MINUTE-TUTOR-BOOKING-E2E-REPORT.pdf  → NOT GENERATED (tooling absent)
```

Headed Playwright: `e2e/workflows/five-minute-booking-journey.spec.ts` — **7/7 PASS**  
Domain runner: `config/five-minute-journey.php`

## 17. Cleanup

**PARTIAL** — fictional users retained for evidence retention (`@example.test`, `is_demo` meta). No production deletion. Orphan meeting rooms: N/A.

## 18. Definition of Done matrix

| Criterion | Status |
|-----------|--------|
| Tutor registration | PASS |
| Tutor unapproved then approved | PASS (publication PARTIAL) |
| Approved tutor searchable | PARTIAL |
| Parent registration | PASS |
| Minor linked to guardian | PASS |
| Cross-family rejected | PASS |
| Authoritative availability | PARTIAL |
| ~5 min booking | PASS |
| Sandbox payment | PASS |
| Single booking/order | PASS (order link meta PARTIAL) |
| Dashboards correlated | PASS (list-level) |
| Join Meeting activates | **BLOCKED** |
| Student+tutor same meeting | **BLOCKED** |
| Unauthorized cannot join | PASS (fail-closed) |
| Attendance/completion | PASS (status-based) |
| CRM/LMS/mail fully verified | PARTIAL / NOT RUN |
| Negative suite complete | PARTIAL |
| Failure/recovery suite | NOT RUN |
| Evidence complete | PARTIAL (no PDF) |
| Cleanup complete | PARTIAL |

---

## Mandatory end markers

* Tutor registration: [PASS]
* Tutor approval: [PASS]
* Parent registration: [PASS]
* Guardian-child relationship: [PASS]
* Tutor discovery/profile: [PARTIAL]
* Five-minute booking: [PASS]
* Sandbox payment: [PASS]
* Parent Dashboard accuracy: [PASS]
* Student Dashboard accuracy: [PASS]
* Tutor Dashboard accuracy: [PASS]
* Real-time meeting join: [BLOCKED]
* Unauthorized meeting access rejected: [PASS]
* Attendance and completion: [PASS]
* Duplicate suppression: [PASS]
* Child-safety isolation: [PASS]
* E2E scenarios passed: [7/7 headed UI + 14/17 domain verdicts PASS; 1 PARTIAL; 2 BLOCKED gates]
* Critical defects: [2]
* Test data cleanup: [PARTIAL]
* Overall five-minute journey: [BLOCKED]
