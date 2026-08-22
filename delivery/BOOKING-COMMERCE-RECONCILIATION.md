# BOOKING-COMMERCE Reconciliation

**Run:** `bc-20260809-113004`  
**Status:** PASS (API/DB totals)

| Field | Expected | Actual | Match |
| --- | --- | --- | --- |
| Product | ngt-online-1hr / 57658 | 57658 | PASS |
| Product list price | 320.00 ZAR | order total 320 | PASS |
| Cart line | N/A (direct `wc_create_order`) | line_total 320 | PASS |
| Order item | 320 | 320 (`order_item_id` 49) | PASS |
| Order total | 320 | 57733 → 320.00 | PASS |
| Payment amount | 320 (sandbox `payment_complete`) | processing/paid | PASS |
| Invoice | equals order total | invoice id 47 generated | PASS |
| Session payment_status | paid after settle | paid | PASS |
| Adult order | 320 | 57735 linked session 23 | PASS |

Source: `delivery/evidence/booking-commerce/bc-20260809-113004/reconciliation.json` + `chain.json`.

## Verdict

```text
STAGING ONLY
```

Browser cart/checkout price display and PayFast settlement amount were not reconciled in headed UI (direct order creation used in Docker harness).
