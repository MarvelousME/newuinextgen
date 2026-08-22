# 07 — WhatsApp Capability Map

| Capability ID | Phase | Provider method | Notes |
|---------------|-------|-----------------|-------|
| `whatsapp.health` | 2 | OpenWA `/health` | Required |
| `whatsapp.send_text` | 4 | send-text | Transactional first |
| `whatsapp.send_media` | 4+ | send media | Feature-flagged |
| `whatsapp.message_status` | 5 | status APIs | Only real statuses |
| `whatsapp.receive` | 6 | webhook → platform event | Signed |
| `whatsapp.session_status` | 3 | session status | Normalized states |
| `whatsapp.session_connect` | 3 | start + QR | Admin only |
| `whatsapp.session_disconnect` | 3 | stop | Admin only |
| `whatsapp.send_template` | deferred | — | No official WABA templates; use NGT text templates |

## Session state normalization

| Provider | NextGen |
|----------|---------|
| disconnected | `DISCONNECTED` |
| initializing / starting | `CONNECTING` |
| qr | `QR_REQUIRED` |
| ready / authenticated | `CONNECTED` |
| degraded / flapping | `DEGRADED` |
| error | `ERROR` |
| flag off | `DISABLED` |

## Consumers

`NGC_Transactional_Mail` peers, booking workflows, session reminders, FluentCRM automations (later), agent router (later, approval modes).
