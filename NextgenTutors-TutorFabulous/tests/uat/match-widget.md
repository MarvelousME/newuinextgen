# Match Widget UAT Checklist

**Package:** NextGen Companion 1.2.0 + BeyondInfinity 1.4.8  
**Status:** NOT VERIFIED until executed on a live WordPress instance.

## Prerequisites

- BeyondInfinity theme active
- NextGen Companion plugin active
- At least one published `tutors` CPT post (demo seed acceptable for UI only)
- Front page is not `find-a-tutor` and not a dashboard route

## Browser checks

| # | Step | Expected | Result |
|---|------|----------|--------|
| 1 | Load public homepage | Match FAB or floating dock visible (unless filtered off) | |
| 2 | Click `#match-dock-btn` once | Widget panel opens **once**; does not immediately close | |
| 3 | Click `#match-dock-btn` again while open | Panel stays open (open-only contract via event) | |
| 4 | Click `#ngc-match-close` | Panel closes | |
| 5 | Submit hero search (if present) | Widget opens with subject/province prefilled | |
| 6 | Enable OS “Reduce motion” | Panel opens without scale animation; FAB pulse disabled | |
| 7 | Logged-out: run match wizard | AJAX succeeds with valid nonce | |
| 8 | Rapid-fire 21+ match AJAX calls within 10 min | HTTP 429 JSON error returned | |
| 9 | Mobile viewport (375px) | Panel usable; no horizontal overflow | |
| 10 | Browser console | No duplicate-init errors; `NGCMatchWidgetInitialized === true` | |

## Security checks

| # | Step | Expected |
|---|------|----------|
| 11 | `GET /wp-json/ngc/v1/match/smart?subject=Math` | 200 with sanitized matches or 429 when throttled |
| 12 | `GET /wp-json/nextgen/v1/tutors/{id}/calendar` logged out | No `user_id` / `amelia_employee_id` in JSON |
| 13 | Match AJAX without nonce | 403 |

## Notes

- Green health checks do **not** prove Amelia, FluentCRM, GamiPress, payments, or POPIA compliance.
- Demo tutors display **DEMO SEED** badge in admin; convert or clear before production launch.
