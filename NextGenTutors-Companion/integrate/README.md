# Integrate Pack — Production Runtime (v1.4.0+)

The `integrate/` folder ships workflow specifications (WF-01..WF-05), a WooCommerce product catalog CSV, and a **deprecated** legacy orchestrator stub.

**Do not** `require` `nextgentutors-workflows.php` on production. All behaviour is implemented in the main plugin via `NGC_Integrate_Runtime`.

## What is wired

| Spec | Module | Behaviour |
|------|--------|-----------|
| WF-01 Tutor onboarding | Existing `NGC_Registration`, `NGC_Tutor_Lifecycle` | Spec registry + event catalog |
| WF-02 Booking & payment | `NGC_Bookings`, `NGC_Payments`, `NGC_Amelia` | Payment → reminder queue |
| WF-03 Reminders | `NGC_Session_Reminders` | 24h / 1h / 15m cron + email templates |
| WF-04 Reviews | `NGC_Reviews` + `ngc_review_submitted` hook | Workflow dispatch on submit |
| WF-05 Payouts | `NGC_Payout_Scheduler` | Monthly batch via `ngc_monthly_payout_batch` |

Additional: `NGC_Referrals` (`?ref=` cookie), `NGC_WooCommerce_Catalog` (CSV import CLI).

## WP-CLI

```bash
wp ngc integrate_status
wp ngc import_woocommerce_products --dry-run
wp ngc run_payout_batch
wp ngc process_reminders
```

## Verification

```bash
php scripts/integrate-test.php
wp ngc verify --integrations
```

See `INTEGRATION_REPORT.md` for SWOT and gap analysis.
