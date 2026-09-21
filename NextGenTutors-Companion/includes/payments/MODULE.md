# Payments module

## Boundary

Owns commerce settlement and tutor payout operations for NextGen Companion.

### Owns

| Class | Role |
| --- | --- |
| `NGC_Payments` | WooCommerce payment-complete / refund hooks; idempotent `settle_order` |
| `NGC_Payout_Scheduler` | Monthly and bi-weekly payout cron registration and batch runs |
| `NGC_Payout_Export` | Pending-payout CSV rows and file/download helpers |

### Does not own

- Matching / scoring (`includes/matching/`)
- AI model runtime or agentic agents (`includes/ai/`, `includes/agentic/`)
- Payment gateway adapters (e.g. PayFast under `includes/integrations/`) — they may call into this module
- Amelia, LMS, CRM, and other ecosystem bridges

## Layout

```
includes/payments/
  bootstrap.php
  MODULE.md
  class-ngc-payments.php
  class-ngc-payout-scheduler.php
  class-ngc-payout-export.php
```

Classes keep `NGC_*` names. Autoload resolves them via `includes/payments/` in `ngc_autoload`.

## Boot

`NGC_Module_Registry` lazy-loads `bootstrap.php`. Runtime `init()` for these classes remains wired by the main plugin bootstrap / integrate runtime (unchanged class names).
