# Economic Control Center — Screen Matrix

**Source of truth:** `ecosystem-platform/packages/platform-core/economic-catalog.mjs`  
**Shell:** same Ecosystem Control Center (`ecosystem-platform/apps/control-center/`)  
**Routes:** `#/economic` and `#/economic/:screenId`

Permissions are checked with `IamService.can(userId, permission, tenantId)` — never `role === 'admin'`.

| # | Screen | Route | Capability | Permission | Adapter | API | Data honesty |
|---|--------|-------|------------|------------|---------|-----|--------------|
| 1 | Economic Home | `#/economic` `#/economic/overview` | economic-overview | economic.read | platform | `GET /api/v1/economic/overview` | Live tenant/customer/invoice counts. Money KPIs unavailable until ledger exists. |
| 2 | Treasury | `#/economic/treasury` | treasury | economic.read | none | — | Unavailable — capability not registered |
| 3 | General Ledger | `#/economic/ledger` | ledger | economic.read | none | — | Unavailable — capability not registered |
| 4 | Accounts | `#/economic/accounts` | ledger | economic.read | none | — | Unavailable — capability not registered |
| 5 | Wallets | `#/economic/wallets` | wallets | economic.read | none | — | Unavailable — capability not registered |
| 6 | Payments | `#/economic/payments` | payments | payments.read | companion-http | — | Capability registered; HTTP adapter not registered |
| 7 | Billing | `#/economic/billing` | invoicing | economic.read | odoo | invoices list | Invoice documents only. Plans/subscriptions not registered |
| 8 | Invoices | `#/economic/invoices` | invoicing | invoices.read | odoo | `GET/POST /api/v1/economic/invoices` | Draft create. No edit-after-post |
| 9 | Revenue | `#/economic/revenue` | invoicing | economic.read | odoo | invoices list | Document totals labeled as not recognized revenue |
| 10 | Expenses | `#/economic/expenses` | expenses | economic.read | none | — | Unavailable |
| 11 | Settlements | `#/economic/settlements` | settlements | economic.read | none | — | Unavailable |
| 12 | Reconciliation | `#/economic/reconciliation` | reconciliation | economic.read | none | — | Unavailable |
| 13 | Tax | `#/economic/tax` | tax | economic.read | none | — | Unavailable |
| 14 | Contracts | `#/economic/contracts` | contracts | economic.read | none | — | Unavailable |
| 15 | Marketplace | `#/economic/marketplace` | marketplace | economic.read | none | — | Unavailable |
| 16 | Earnings | `#/economic/earnings` | payouts | economic.read | none | — | Unavailable |
| 17 | Rewards | `#/economic/rewards` | rewards | economic.read | none | — | Unavailable |
| 18 | UBI / Distributions | `#/economic/ubi` | distributions | economic.read | none | — | Unavailable — distributions not registered |
| 19 | Assets | `#/economic/assets` | ledger | economic.read | none | — | Unavailable |
| 20 | Liabilities | `#/economic/liabilities` | ledger | economic.read | none | — | Unavailable |
| 21 | Cash Flow | `#/economic/cash-flow` | treasury | economic.read | none | — | Unavailable |
| 22 | Budgets | `#/economic/budgets` | budgets | economic.read | none | — | Unavailable |
| 23 | Forecasting | `#/economic/forecasting` | forecasting | economic.read | none | — | Unavailable |
| 24 | Pricing | `#/economic/pricing` | invoicing | economic.read | odoo | — | Versioning API not registered |
| 25 | Commissions | `#/economic/commissions` | commissions | economic.read | none | — | Unavailable |
| 26 | Fees | `#/economic/fees` | payments | payments.read | companion-http | — | HTTP adapter not registered |
| 27 | Gateways | `#/economic/gateways` | payments | payments.read | companion-http | — | HTTP adapter not registered. Secrets never displayed |
| 28 | Fraud | `#/economic/fraud` | fraud | fraud.review | none | — | Unavailable |
| 29 | Risk | `#/economic/risk` | risk | risk.read | none | — | Unavailable |
| 30 | Compliance | `#/economic/compliance` | audit | audit.read | platform | `GET /api/v1/economic/audit` | Live audit tail |
| 31 | Disputes | `#/economic/disputes` | payments | payments.read | companion-http | — | HTTP adapter not registered |
| 32 | Refunds | `#/economic/refunds` | payments | payments.read | companion-http | — | HTTP adapter not registered |
| 33 | Payouts | `#/economic/payouts` | payouts | economic.read | none | — | Unavailable |
| 34 | Beneficiaries | `#/economic/beneficiaries` | payouts | economic.read | none | — | Unavailable |
| 35 | Customers | `#/economic/customers` | crm | customers.read / .create | odoo | `GET/POST /api/v1/economic/customers` | Live Odoo `res.partner` |
| 36 | Vendors | `#/economic/vendors` | crm | customers.read | odoo | — | Vendor classification not registered |
| 37 | Currencies | `#/economic/currencies` | economic-overview | economic.read | platform | `GET /api/v1/economic/currencies` | Configured base currency only |
| 38 | Exchange Rates | `#/economic/exchange-rates` | fx | economic.read | none | — | Unavailable |
| 39 | Economic Automation | `#/economic/automation` | workflow-automation | economic.read | platform | — | Engine registered; designer not registered |
| 40 | Reports | `#/economic/reports` | audit | reports.generate | platform | audit export | Audit JSON export only |
| 41 | Audit | `#/economic/audit` | audit | audit.read | platform | `GET /api/v1/economic/audit` | Read-only. No destructive CRUD |
| 42 | Settings | `#/economic/settings` | economic-overview | economic.read | platform | overview.configured | Read-only configured env values |

## Executable mutations

| Action | Permission | Capability | Result if missing |
|--------|------------|------------|-------------------|
| `customers.create` | customers.create | crm | 403 / 501 |
| `invoices.create` | invoices.create | invoicing | 403 / 501 |
| Any other monetary action (`treasury.transfer`, `ledger.post`, `payments.refund`, …) | — | — | **501** — not an executable economic mutation |

## IAM mapping (canonical L0–L7)

| Level | Role | Economic grants |
|-------|------|-----------------|
| L0 | Platform Super Admin | `*` |
| L1 | Platform Operator | read, audit, reports, customers.read, invoices.read, payments.read, risk.read |
| L2 | Tenant Owner | L1 + customers.create, invoices.create, billing.manage |
| L3 | Tenant Admin | read, customers.read, invoices.read, payments.read, audit.read |
| L4 | Tenant Manager | read, customers.read, invoices.read |
| L5–L6 | User / Portal | economic.read |
| L7 | Service identity | economic.read, audit.read |
