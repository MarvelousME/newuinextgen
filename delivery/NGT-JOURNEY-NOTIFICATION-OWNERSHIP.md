# NGT Journey Notification Ownership

**Date:** 2026-08-16  
**Goal:** One transactional owner per message. Prevent Amelia + Notifier + FluentCRM duplicates.

| Event | Recipient | Channel | Template owner | Transport provider | Decision owner |
|---|---|---|---|---|---|
| BookingConfirmed / PaymentCaptured | Parent/student | Email | NGT Notification service | FluentSMTP / wp_mail | Ecosystem workflow |
| BookingConfirmed | Tutor | Email/SMS | NGT | Email/SMS adapter | Ecosystem |
| Welcome nurture | Parent | Email series | **FluentCRM sequence** | FluentCRM | CRM (marketing only) |
| SessionReminderDue24Hours | Parent + tutor | Email/SMS | NGT | Email/SMS | Ecosystem reminders |
| SessionReminderDue1Hour | Parent + tutor | Email/SMS | NGT | Email/SMS | Ecosystem |
| SessionReminder (Amelia native) | — | — | Amelia | Amelia | **DISABLE** (NGT owns same) |
| Meeting link / classroom join | Parent + tutor | In-app / email | NGT Session / Meeting port | **Jitsi** adapter | Ecosystem — **not Zoom** (Amelia Zoom copy = DISABLE) |
| Rating request | Parent/student | Email | NGT PostSessionWorkflow | Email | Ecosystem |
| TutorVerified welcome | Tutor | Email | NGT | Email | Ecosystem |
| TutorRejected | Tutor | Email | NGT | Email | Ecosystem |
| PayoutCreated / completed | Tutor | Email | NGT Finance | Email | Ecosystem |
| SafetyFlagRaised / case | Safety team | Email + SMS + Support | NGT Safeguarding | Fluent Support + SMS | Safeguarding |
| Marketing newsletter | Opt-in contacts | Email | FluentCRM | FluentCRM | CRM only |

**Rules**

1. Transactional booking/payment/session messages = NGT owned.  
2. FluentCRM may send nurture **after** projection, not a second confirmation.  
3. Amelia emails for the same event = retain only if explicitly listed; else disable.  
4. Meeting SoR = **Jitsi** (Companion meeting adapter). Do not treat Amelia Zoom copy as SoR.  
5. Audit each send with correlation_id + template_key + idempotency key.
