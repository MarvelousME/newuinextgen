# 06 — Target Architecture

```text
                 NEXTGENTUTORS (Companion + RAD)
                          |
               NGC_Notification_Service
                          |
               WhatsAppProviderInterface
                          |
              +-----------+-----------+
              |                       |
        OpenWaDevProvider      WaAutomateProvider (legacy)
              |
       OpenWA-Dev API (:2785 private)
              |
         whatsapp-web.js session
              |
            WhatsApp
```

## Pillars

| Pillar | Plan |
|--------|------|
| Manifest | `communication.whatsapp.openwa` |
| Capabilities | `whatsapp.send_text`, `send_media`, `receive`, `message_status`, `session_*` |
| Policy | `NGC_Policy_Bridge` + caps for send/broadcast/session |
| Fabric | Business event → durable queue → worker → provider |
| Conformance | Health, webhook signature, idempotency tests |
| Dependency graph | Workflows → `notification.whatsapp.send` |
| Control plane | NGT Admin → Communications → WhatsApp |

## Non-negotiables

1. Business logic never calls OpenWA URLs directly.
2. OpenWA failure never blocks booking/payment/lesson.
3. Theme keeps `wa.me` FAB only; automation leaves theme over time.
4. Unofficial transport risk documented in admin UI banner.
5. Secrets in vault / encrypted options — not Customizer plaintext long-term.

## Preferred send path

```text
booking.confirmed
  → Notification Orchestrator (channel policy)
  → enqueue durable job (idempotency key)
  → worker authorize (policy)
  → WhatsAppProvider.sendText
  → persist status
  → audit (metadata, scrubbed body)
```
