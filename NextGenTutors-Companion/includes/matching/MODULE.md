# Matching module

## Owns

- Tutor–student **scoring** and **match lifecycle** (create, propose, accept, reject, assign)
- CPT tutor discovery used for ranking (`NGC_Tutor_Cpt_Source`)
- Smart matching UI/API helpers (`NGC_Smart_Matching`)
- Match REST routes (`NGC_Rest_Matching` → `/matches`, accept/reject/assign)

## Key classes

| Class | Location |
|-------|----------|
| `NGC_Matching` | `includes/matching/class-ngc-matching.php` — `create_from_find_tutor` requires `matching.propose` via Policy Bridge |
| `NGC_Smart_Matching` | `includes/matching/class-ngc-smart-matching.php` |
| `NGC_Tutor_Cpt_Source` | `includes/matching/class-ngc-tutor-cpt-source.php` |
| `NGC_Rest_Matching` | `includes/matching/class-ngc-rest-matching.php` |

Autoload: `ngc_autoload` already searches `includes/matching/`.

## Does not own

- **Payments / payouts** (`NGC_Payments`, payout scheduler/export) — payments module
- AI model runtime / agentic orchestration — AI module
- Amelia / PayFast / LMS / CRM bridges — integrations / adapters
- Generic REST shell (`NGC_Rest`) — registers routes; matching owns only `NGC_Rest_Matching`

## Bootstrap

`bootstrap.php` is a lazy-load marker for `NGC_Module_Registry`. No domain init change yet beyond documentation.
