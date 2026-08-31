# Economic Control Center Visual Fidelity Report

**Date:** 31 August 2026  
**Reference:** Ecosystem Control Center HTML (`ecosystem-platform/apps/control-center/`) + `docs/control-center/ecosystem-control-center-architecture.svg`  
**Preview path supplied by prompt:** `sandbox:/mnt/data/economic-control-center-ui-preview.html` — **not present on disk**. Visual source of truth is the existing Control Center.  
**Implementation:** product switch inside the same shell (`#/economic`). `--ecc-*` aliases `--cc-*`. No second theme.

## Could these screens be native modules of the same Control Center?

**YES** for shell, sidebar, glass panels, cyan/violet palette, typography family, action bar, operations rail, status dots, and kinetic tokens.  
**PARTIAL** for pixel-diff against the missing HTML preview file.

## Production readiness verdict

**COMPLETE WITH LIMITATIONS** — not PRODUCTION READY as a full ledger/treasury/payments operations suite.

Live, authorized surfaces: overview (counts + health), customers CRUD, invoices draft create, audit (read), currencies (configured base), reports (audit JSON export), settings (read-only config). All other monetary domains fail closed with explicit Unavailable copy. No fabricated balances.

## Visual Acceptance Matrix

| Screen | Reference | Desktop | Tablet | Mobile | Token Compliance | Accessibility | Status |
|--------|-----------|---------|--------|--------|------------------|---------------|--------|
| Economic Overview | CC 3-column shell + KPI glass cards | VERIFIED 1800/1440/1366 | VERIFIED 1024/768 | PARTIAL 390 — nav/action density | VERIFIED `--ecc-*` | PARTIAL | PARTIAL |
| Treasury | Unavailable orange panel | VERIFIED | VERIFIED | PARTIAL | VERIFIED | VERIFIED honest copy | VERIFIED (capability missing) |
| Ledger | Unavailable panel | VERIFIED | VERIFIED | PARTIAL | VERIFIED | VERIFIED honest copy | VERIFIED (capability missing) |
| Wallets | Unavailable panel | VERIFIED | VERIFIED | PARTIAL | VERIFIED | VERIFIED honest copy | VERIFIED (capability missing) |
| Payments | HTTP adapter missing | VERIFIED | VERIFIED | PARTIAL | VERIFIED | VERIFIED honest copy | VERIFIED (adapter missing) |
| Invoices | Grid + draft form | VERIFIED | VERIFIED | PARTIAL | VERIFIED | PARTIAL | PARTIAL |
| Settlement | Unavailable | — | — | — | VERIFIED | VERIFIED copy | VERIFIED (capability missing) |
| Reconciliation | Unavailable | — | — | — | VERIFIED | VERIFIED copy | VERIFIED (capability missing) |
| Tax | Unavailable | — | — | — | VERIFIED | VERIFIED copy | VERIFIED (capability missing) |
| Fraud | Unavailable | — | — | — | VERIFIED | VERIFIED copy | VERIFIED (capability missing) |
| Reports | Catalogue cards | VERIFIED | VERIFIED | PARTIAL | VERIFIED | PARTIAL | PARTIAL |
| Settings | Read-only config | VERIFIED | VERIFIED | PARTIAL | VERIFIED | PARTIAL | PARTIAL |

## Playwright evidence (31 Aug 2026, 13/13 PASS)

| Viewport / screen | File |
|-------------------|------|
| Overview 1800×1120 | `e2e/reports/evidence/economic-control-center/economic-overview-1800x1120.png` |
| Overview 1440×900 | `e2e/reports/evidence/economic-control-center/economic-overview-1440x900.png` |
| Overview 1366×768 | `e2e/reports/evidence/economic-control-center/economic-overview-1366x768.png` |
| Overview 1024×768 | `e2e/reports/evidence/economic-control-center/economic-overview-1024x768.png` |
| Overview 768×1024 | `e2e/reports/evidence/economic-control-center/economic-overview-768x1024.png` |
| Overview 390×844 | `e2e/reports/evidence/economic-control-center/economic-overview-390x844.png` |
| Treasury 1440×900 | `e2e/reports/evidence/economic-control-center/economic-treasury-1440x900.png` |
| Ledger 1440×900 | `e2e/reports/evidence/economic-control-center/economic-ledger-1440x900.png` |
| Wallets 1440×900 | `e2e/reports/evidence/economic-control-center/economic-wallets-1440x900.png` |
| Payments 1440×900 | `e2e/reports/evidence/economic-control-center/economic-payments-1440x900.png` |
| Invoices 1440×900 | `e2e/reports/evidence/economic-control-center/economic-invoices-1440x900.png` |
| Reports 1440×900 | `e2e/reports/evidence/economic-control-center/economic-reports-1440x900.png` |
| Settings 1440×900 | `e2e/reports/evidence/economic-control-center/economic-settings-1440x900.png` |

Asserted: title `ECONOMIC CONTROL CENTER`, `#ecc-page` visible, **42** left-nav buttons.

## Token compliance

`--ecc-*` in `packages/nextgen-ui/tokens.css` aliases `--cc-*` / `--brand-*`. No competing palette.

## Remaining gaps

1. User HTML preview was not in the workspace; cannot pixel-diff against it.
2. Ledger, treasury, wallets, tax, settlement, reconciliation, fraud, FX, UBI have no registered providers.
3. Companion `payments` capability is registered without a platform HTTP adapter.
4. Mobile 390×844: 42-item nav + action bar consume the first viewport; KPIs sit below the fold (PARTIAL).
5. WCAG 2.2 AA is not fully automated. Focus-visible, drawer Escape, labels, and status text exist; dense 36–40px controls miss a strict 44px target in places.
6. Tenant on the evidence run was `pending`; customer/invoice counts stay unavailable until a provisioned tenant is selected.

## Commands

```bash
cd ecosystem-platform && npm test
cd ecosystem-platform && npm run dev:api
# http://localhost:8790/#/economic

cd e2e
ECOSYSTEM_CC_URL=http://localhost:8790 ECOSYSTEM_API_TOKEN=<token> npx playwright test workflows/economic-control-center-visual.spec.ts
```
