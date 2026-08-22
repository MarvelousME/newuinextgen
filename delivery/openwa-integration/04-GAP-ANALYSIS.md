# 04 — Gap Analysis

| Capability | NextGen Current | OpenWA-Dev | Gap | Decision |
|------------|-----------------|------------|-----|----------|
| WhatsApp send text | Theme `bi_openwa_send_text` | REST send-text | Wrong layer (theme) | Move to Companion provider |
| WhatsApp media | Not verified in theme | Advertised | Gap | Enable after contract tests |
| Inbound webhook | `bi/v1/openwa/webhook` (token query) | HMAC webhooks | Harden + remap events | New `ngc/v1` receiver |
| Multi-session | Single session-id | Native multi-session | Gap | Adopt OpenWA-Dev sessions |
| Session QR admin | Terminal / docs | Dashboard + QR API | Gap | Bridge admin + deep-link optional |
| Delivery status | Minimal | Status module | Gap | Persist provider statuses only |
| Notification orchestrator | Fragmented | N/A | **Major gap** | Build `NGC_Notification_Service` |
| Templates | Email templates only | N/A | Gap | Central WA templates |
| Phone E.164 | Digit strip | chatId `@c.us` | Gap | Shared normalizer |
| Consent per channel | POPIA only | N/A | Gap | Extend prefs |
| Child safety on WA | Soft email parent-first | N/A | Gap | Hard policy |
| Queue/idempotency for WA | Not for OpenWA sends | Internal BullMQ | Gap | Use NGT durable queue for outbound |
| FluentCRM link | Partial | N/A | Gap | Phone → contact resolve |
| Official WABA | Absent | Absent | Shared gap | Future Meta provider |
| RAD capabilities | None for WA | N/A | Gap | Register `whatsapp.*` |

## Decision summary

| Option | Chosen? |
|--------|---------|
| REUSED (as-is theme OpenWA) | No — wrong package boundary |
| ADAPTED | Yes — theme OpenWA → legacy adapter |
| CONSOLIDATED | Yes — Studio stubs + theme WA → one façade |
| INTEGRATED (embed OpenWA into WP) | **No** |
| USED AS A PROVIDER | **Yes** — OpenWA-Dev |
| USED AS A FALLBACK PROVIDER | Yes — wa-automate during cutover |
| PARTIALLY ADOPTED | Yes — outbound transactional first; inbound/CRM/agent later |
| NOT IMPLEMENTED | No |
