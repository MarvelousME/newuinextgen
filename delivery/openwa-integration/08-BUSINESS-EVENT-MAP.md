# 08 — Business Event Map

All WhatsApp sends are **optional policies**, not hard-coded in transactions.

| Business event | Default WA | Notes |
|----------------|------------|-------|
| student.registered | OFF | Prefer parent if minor |
| parent.registered | OFF | Opt-in |
| tutor.registered | OFF | |
| tutor.approved / rejected | OFF→configurable | |
| booking.created / confirmed | OFF→configurable | High value transactional |
| booking.reminder / lesson.starts_soon | OFF→configurable | Extend reminders |
| lesson.completed | OFF | |
| payment.completed / failed | OFF→configurable | |
| invoice.created | OFF | |
| refund / payout | OFF | Sensitive — human review |
| support.case.created | OFF | |
| lead.created / assigned | OFF | Marketing consent required |
| openwa.session.disconnected | ON (admin) | Ops alert via email/intel |

## Channel policy example

```text
Booking Confirmation
  Email     ON
  WhatsApp  flag whatsapp.transactional.enabled + template on
  SMS       OFF
  In-App    ON (if applicable)
```

## AI outbound

Modes: `HUMAN_ONLY` (default) → `DRAFT_FOR_APPROVAL` → `AUTO` (restricted templates only).
