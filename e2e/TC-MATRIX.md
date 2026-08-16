# TC-01…TC-26 headed E2E matrix

**Base URL:** `http://localhost:8890` (`BASE_URL` override)  
**Specs:** `tc-01-15-functional.spec.ts`, `tc-16-20-integration.spec.ts`, `tc-21-26-security.spec.ts`  
**Evidence:** `delivery/evidence/tc-matrix/<run-id>/`

## Run headed

```powershell
cd e2e
$env:BASE_URL = 'http://localhost:8890'
npm run test:tc-matrix-headed
```

Requires: Docker WP up, demo personas seeded (`demo.parent@…`, `demo.tutor.approved@…`, `demo.student.adult@…`), Playwright browsers installed.

Optional:

| Env | Effect |
|---|---|
| `E2E_DEEP_CRM=1` | Assert FluentCRM tags (needs live CRM) |
| `E2E_PAYFAST_SETTLE=1` | Continue past checkout into sandbox settle (needs public ITN) |
| `NGC_DEMO_PASSWORD` | Override demo password |

## Honesty map

| IDs | What headed proves by default | Not claimed without flags / staging |
|---|---|---|
| TC-01, 02 | Form submit success | FluentCRM tags / auto-responder content |
| TC-03, 04 | Admin tutor/users surface | Full Approve/Reject → CRM → badge chain |
| TC-05, 06, 20 | Find tutor / pricing / checkout UI | Amelia row + Woo completed + ITN settle |
| TC-07, 08 | Tutor dashboard | Earnings row / 50% no-show compensation |
| TC-09, 10 | Rating/support surfaces | Highly Rated badge / Fluent Support ticket |
| TC-11 | Admin payout surface | Cron fire + EFT + PDF invoices |
| TC-12 | Referral mention | R50 credit ledger |
| TC-13…15 | Dashboard shells / admin menu | Exact 4–6 KPI widgets |
| TC-16…19 | Plugin admin / LMS surfaces | AutomatorWP dual sync (discouraged) |
| TC-21…25 | Injection/XSS/auth/CSRF/role | — |
| TC-26 | Consent field presence / gate | Full POPIA counsel sign-off |

Do **not** treat a green matrix as enterprise production proof.
