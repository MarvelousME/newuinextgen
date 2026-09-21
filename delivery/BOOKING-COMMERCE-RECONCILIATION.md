# Commercial reconciliation — run `booking-commerce-20260917-003256`

**Date:** 2026-09-17  
**Currency:** ZAR  
**Catalogue version:** `NGC_Product_Catalog::CATALOG_VERSION = 1`

## Catalogue

All 16 official SKUs exist as WooCommerce products with `_ngt_*` meta. No subject-specific SKUs were invented.

| SKU | Product ID | Price |
|---|---|---|
| NGT-ONLINE-1HR | 2104 | 320 |
| NGT-INPERSON-1HR | 2105 | 350 |
| NGT-TERTIARY-1HR | 2106 | 500 |
| NGT-ONLINE-4-1TO3 | 2107 | 1280 |
| NGT-ONLINE-8-1TO3 | 2108 | 2560 |
| NGT-ONLINE-4-3TO12 | 2109 | 1200 |
| NGT-ONLINE-8-3TO12 | 2110 | 2400 |
| NGT-INPERSON-4-1TO3 | 2111 | 1400 |
| NGT-INPERSON-8-1TO3 | 2112 | 2800 |
| NGT-INPERSON-4-3TO12 | 2113 | 1280 |
| NGT-INPERSON-8-3TO12 | 2114 | 2560 |
| NGT-TERTIARY-4 | 2115 | 2000 |
| NGT-TERTIARY-8 | 2116 | 4000 |
| NGT-HIGHFREQ-12 | 2117 | 3600 |
| NGT-HIGHFREQ-16 | 2118 | 4800 |
| NGT-HIGHFREQ-20 | 2119 | 6000 |

## Happy-path order 2159

| Field | Value | Match |
|---|---|---|
| SKU | NGT-ONLINE-1HR | catalogue |
| WC order total | 320.00 | catalogue price 320 |
| Invoice 15 amount | 320.00 | equal to order total |
| Invoice customer | user 24 (parent) | child 25 is learner, not WC customer |
| Invoice line package | NGT-ONLINE-1HR | product key |
| Session payment_status | paid | order paid |
| Duplicate invoice on second provision | none (lookup by order_id returned 15) | PASS |

**Variance:** 0.00 ZAR between catalogue price, order total, and invoice amount.

## Failed order 2161

- Session 27 `failed` / `payment_status=failed`
- No MasterStudy course/lesson
- No meeting

## Refunded order 2162

- Session 28 `refunded`, `meeting_status=revoked`
- Lesson 2163 provisioned before refund; meeting revoked

## Totals to explain

| Order | Status | Amount | Session | Invoice |
|---|---|---|---|---|
| 2159 | paid/completed path | 320.00 | 26 completed | 15 paid 320.00 |
| 2161 | failed | 320.00 unpaid | 27 failed | none |
| 2165 | paid via PayFast ITN | 320.00 | 29 ready | 17 paid 320.00 |

Prior run `booking-commerce-20260917-000342` (order 2147 / session 20 / invoice 11) remains on disk as historical evidence.
