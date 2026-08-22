# 01 — Existing Capability Inventory

**Evidence date:** 2026-08-11  
**Mode:** BROWNFIELD discovery (no code changes)

## Verdict

NextGenTutors **already has WhatsApp automation** in the theme via **`@open-wa/wa-automate` Easy API** (`inc/openwa.php`), **not** Calanca/OpenWA-Dev. There is **no** Meta WhatsApp Business Cloud API, Twilio, 360dialog, or Baileys integration. There is **no** single `NotificationService` channel abstraction.

## Messaging inventory

| Existing Component | Capability | Provider | Used By | Status | Keep/Replace/Consolidate |
| ------------------ | ---------- | -------- | ------- | ------ | ------------------------ |
| Theme OpenWA | Outbound text, inbound webhook, form notify, auto-reply | `@open-wa/wa-automate` Easy API (`:8080`) | Theme forms, admin Sync Launch, WF-23 specs | Implemented; **NOT PRODUCTION-VERIFIED** | **CONSOLIDATE** into Companion provider; keep as interim adapter or retire after OpenWA-Dev cutover |
| Theme OpenWA REST | `bi/v1/openwa/{webhook,status,send}` | wa-automate | Provider + admins | Live | Move to `ngc/v1` communications |
| `bi_openwa_inbox` | Last ~50 inbound | WP option | Admin | Ephemeral | Replace with durable message log |
| `wa.me` FAB / dock | Click-to-chat UI | Meta deep link | Public site | Production UX | **KEEP** (UI ≠ API) |
| `NGC_Email_Adapter` + `NGC_Transactional_Mail` | Transactional email; **parent-first** student notify | wp_mail / FluentSMTP | Bookings, approvals | Active | **KEEP** as email channel |
| `NGC_Studio_Notifications` | 8-channel catalog; email real; SMS/WA = webhook stubs | Configurable HTTPS | Studio | Partial | **CONSOLIDATE** as channel façade candidate |
| `NGC_Intelligence_Dispatch` | Ops alerts (email + webhooks) | Generic HTTPS | Mission Control | Active | **KEEP** for ops; separate from user messaging |
| FluentCRM adapter | Contacts/tags/POPIA fields | FluentCRM | Workflows | Soft-fail | **KEEP** for CRM routing |
| FluentSMTP | Mail transport | FluentSMTP | All email | Infra | **KEEP** |
| AutomatorWP recipes | Email recipes | AutomatorWP | Optional | Prefer Companion workflows | Deprecate parallel paths |
| Durable queue + DLQ | Async jobs | MySQL platform | Workflows, fraud, safeguarding | Active | **KEEP** as notification outbox |
| POPIA consent | Consent grant/withdraw | User meta + log | Checkout, CRM | Active | **KEEP**; extend channel prefs |
| Safeguarding | Cases (not messaging) | — | Ops | Active | **KEEP**; enforce recipient rules |
| Hub notifications | Hub email/DB | Automation Hub | Hub events | Parallel | Consolidate vs Companion |
| Session reminders | Email 24h/1h/15m | Email only | WF-24 | SMS/WA claimed in docs only | Wire WhatsApp via orchestrator later |

## Channel coverage

| Channel | Present? |
|---------|----------|
| Email | Yes (primary) |
| SMS | Webhook stub only |
| WhatsApp API | Yes (theme OpenWA / wa-automate) |
| WhatsApp click-to-chat | Yes (`wa.me`) |
| Push | No |
| In-app (admin) | Yes (admin notifications / intelligence) |
| CRM notifications | FluentCRM + email |
| AI-generated outbound messaging | No governed WhatsApp path |

## Phone / consent gaps

- Phone: digit-strip to `@c.us` only — **no shared E.164 service**
- Consent: POPIA + cookies exist; **no per-channel WhatsApp preference matrix**
- Child safety: soft parent-first in transactional mail — **must harden for WhatsApp**

## Critical naming collision

| Name | What it actually is |
|------|---------------------|
| “OpenWA” in NextGen theme | `@open-wa/wa-automate` Easy API |
| Calanca/OpenWA-Dev | NestJS + `whatsapp-web.js` gateway (`:2785` / `:2886`) |

These are **different products**. Integration must not assume API compatibility.
