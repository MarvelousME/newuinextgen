# INPUTS-REQUIRED — Business decision register

**Release:** Companion 1.9.5 / BeyondInfinity 1.9.17  
**Generated:** 2026-08-02  
**Rule:** Do not invent values. Secrets never stored in this repository.

## Conflict reconciliation (canonical)

| Field | Specification | Code SSOT (`config/nextgentutors-business-profile.json`) | Production | Recommended canonical | Decision required |
|---|---|---|---|---|---|
| Brand | Next Gen Tutors | Next Gen Tutors / NextGenTutors | UNVERIFIED — production not accessed | Next Gen Tutors | No |
| Site URL | https://www.nextgentutors.co.za | https://www.nextgentutors.co.za | UNVERIFIED | https://www.nextgentutors.co.za | Confirm before overwrite |
| Phone | 081 334 0625 | 0813340625 | UNVERIFIED | 0813340625 | Confirm formatting |
| Support email | support@nextgentutors.co.za | support@nextgentutors.co.za | UNVERIFIED | support@nextgentutors.co.za | No |
| Notification email | — | marvin.saunders@gmail.com | UNVERIFIED | Keep code value until ops confirms | **YES** — confirm production notification mailbox |
| Timezone | Africa/Johannesburg | Africa/Johannesburg | UNVERIFIED | Africa/Johannesburg | No |
| Currency | ZAR | ZAR | UNVERIFIED | ZAR | No |
| ID prefixes | NGT-T/S/P000 | NGT-T / NGT-S / NGT-P | Matches intent | NGT-T / NGT-S / NGT-P | Confirm zero-padding width |

## Open inputs (must be supplied by business/ops)

| ID | System | Screen/API | Field | Purpose | Type | Required | Source | Proposed | Secret? | Status |
|---|---|---|---|---|---|---|---|---|---|---|
| IN-001 | WooCommerce / PayFast | Woo → Payments | Merchant ID | Live payments | string | Yes (live) | Operator | — | Yes | OPEN |
| IN-002 | WooCommerce / PayFast | Woo → Payments | Merchant Key | Live payments | string | Yes (live) | Operator | — | Yes | OPEN |
| IN-003 | WooCommerce / PayFast | Woo → Payments | Passphrase | ITN validation | string | Yes (live) | Operator | — | Yes | OPEN |
| IN-004 | WooCommerce / PayFast | Woo → Payments | Sandbox credentials | Test payments | string | Yes (staging) | Operator | — | Yes | OPEN |
| IN-005 | FluentSMTP | FluentSMTP | SMTP provider + credentials | Transactional email | secret | Yes | Operator | — | Yes | OPEN |
| IN-006 | AI Integration | NGTAI settings | BYOK provider keys | AI matching/ops | secret | Optional | Operator | — | Yes | OPEN |
| IN-007 | AI Integration | NGTAI settings | HMAC callback secret | Signed callbacks | secret | Yes if AI live | Operator | — | Yes | OPEN |
| IN-008 | Commerce | Products | Lesson / package prices (ZAR) | Checkout | money | Yes | Business | — | No | OPEN — do not invent |
| IN-009 | Commerce | Finance | Platform fee / commission % | Ledger | percent | Yes | Business | — | No | OPEN |
| IN-010 | Booking | Amelia / NGC | Lesson duration defaults | Booking | minutes | Yes | Business | — | No | OPEN |
| IN-011 | Booking | Rules | Cancellation window | Ops policy | hours | Yes | Business | — | No | OPEN |
| IN-012 | Booking | Rules | Reschedule window | Ops policy | hours | Yes | Business | — | No | OPEN |
| IN-013 | Legal | Privacy/Terms pages | Legal entity registration / VAT | Compliance copy | string | If claimed publicly | Business | — | Sensitive | OPEN — do not invent |
| IN-014 | Legal | Physical address | Registered address | Contact/legal | string | If claimed | Business | — | Sensitive | OPEN |
| IN-015 | Banking | Payouts | Tutor payout bank details policy | Finance | policy | Yes | Business | — | Sensitive | OPEN |
| IN-016 | Notifications | Profile | Production notification mailbox | Ops alerts | email | Yes | Ops | Confirm vs `marvin.saunders@gmail.com` | No | OPEN |
| IN-017 | Tax | Woo | Tax enabled + rates | Checkout | config | Decision | Business | Off until decided | No | OPEN |
| IN-018 | Safeguarding | Policy | Escalation contacts | Child safety | contacts | Yes | Compliance | — | Sensitive | OPEN |
| IN-019 | CRM | FluentCRM | Production send permission | Marketing | boolean | Yes | Ops | false until approved | No | OPEN |
| IN-020 | Hosting | Coolify/host | Production deploy authorization | Release | boolean | Yes | Operator | NO until explicit | No | OPEN |
| IN-021 | Hosting | Compose overlay | `WP_ENVIRONMENT_TYPE=production` via `docker-compose.production.yml` | Blocks demo seed even if old wp-config has `NGC_ALLOW_DEMO_SEED` true | env | Yes | Operator | production | No | OPEN |
| IN-022 | Agent Gateway | Host `.env` | `NGT_GATEWAY_SHARED_SECRET` (no staging default) | Cross-service HMAC | secret | Yes | Operator | — | Yes | OPEN |

## Safe known values (already in SSOT)

- Country: South Africa  
- All 9 provinces listed in business profile  
- Learning modes: Online, In Person, Hybrid  
- Grades 1–12 + Tertiary  
- Phone / WhatsApp / support + admin emails (as in JSON)  
- Role map and ID prefixes  

## Operator actions before production apply

1. Confirm notification email (IN-016).  
2. Enter PayFast sandbox, verify ITN, then live only with explicit approval.  
3. Configure FluentSMTP; never send production CRM campaigns during dry-run.  
4. Supply approved pricing (IN-008) before product seed.  
5. Confirm backup + restore proof.  
6. Explicit production deployment authorization (IN-020).  
7. Public host: `docker compose -f docker-compose.yml -f docker-compose.production.yml` and set `NGT_GATEWAY_SHARED_SECRET` (IN-021, IN-022). Never leave `NGC_ALLOW_DEMO_SEED` true.
