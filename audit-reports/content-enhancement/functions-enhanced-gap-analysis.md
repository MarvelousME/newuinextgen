# Gap analysis — `functions-enhanced.php` vs Companion

**Source:** `content-enhancement/_extracted/nextgen-tutors-core/nextgen-tutors-core/resources/functions-enhanced.php`  
**Decision:** **QUARANTINE / reference only** — do not include in BeyondInfinity  
**Date:** 2026-07-20

## Summary

Theme-era procedural helpers (~1700 lines) for SmartHead-era NextGen. Companion already owns equivalent domains under `ngc_*` / class APIs. **No merge** without a proven missing behaviour.

| Source capability | Companion equivalent | Gap? |
|-------------------|----------------------|------|
| Custom roles | `NGC_Roles` | No |
| FluentCRM sync | FluentCRM integration modules | No |
| Earnings / monthly payouts | Payments / PayFast / wallet | No (different schema) |
| Session ratings AJAX | `NGC_Reviews` | No |
| Achievements | GamiPress + `NGC_Gamification` | No |
| Referral codes | `NGC_Referrals` + POPIA-gated cookies | No |
| Cron monthly payout | Companion schedulers / workflows | No |
| Audit log helper | `NGC_Audit` / system log | No |
| Amelia session hooks | Amelia adapters | No |
| `ngt_*` table create on theme switch | `NGC_Database::create_tables` | **Conflict if loaded** |

## AJAX actions in source (must not register)

- `ngt_submit_rating`
- `ngt_get_tutor_earnings`
- `ngt_get_user_achievements`
- `ngt_get_referral_stats`

## Shortcodes referenced by setup scripts (not registered in Core plugin)

- `[ngt_tutor_grid]` → use `[ngc_tutor_marketplace]` / theme defaults  
- `[ngt_income_calculator]` → **NOT VERIFIED** as Companion shortcode (optional future EXTEND only if product requires)  
- `[ngt_student_dashboard]` / `[ngt_tutor_dashboard]` → `[ngc_student_dashboard]` / `[ngc_tutor_dashboard]`

## Action

Keep file under `_extracted` only. Activation guard (`NGC_Legacy_Plugin_Guard`) prevents Core plugin load that would encourage including this file.
