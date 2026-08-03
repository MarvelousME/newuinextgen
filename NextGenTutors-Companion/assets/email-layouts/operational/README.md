# Operational email & consent layouts

Live HTML layouts from the IMPORTANT design pack. Loaded by `NGC_Operational_Layouts` and used by `NGC_Workflow_Email_Templates` / `NGC_Transactional_Mail`.

| File | Template key | Trigger |
|------|--------------|---------|
| `booking-confirmation.html` | `booking_confirmed` | `ngc_booking_confirmed` |
| `tutor-approval-welcome.html` | `tutor_approved` | Tutor approval workflow |
| `session-rating-request.html` | `session_rating_request` | `ngc_lesson_completed` |
| `popia-compliant-email.html` | `popia_transactional` | Generic POPIA shell |
| `popia-consent-form.html` | shortcode `[ngc_popia_consent]` | Registration / checkout |

FluentCRM-style tokens (`{{contact.first_name}}`, etc.) are normalized to Companion merge fields at load time.
