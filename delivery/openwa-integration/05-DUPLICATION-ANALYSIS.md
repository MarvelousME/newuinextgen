# 05 — Duplication Analysis

## Parallel stacks today

```text
Theme OpenWA (wa-automate)
Studio Notifications (WA webhook stub)
Intelligence WhatsApp webhook URL
wa.me FAB
Email transactional paths
Hub notifications
```

## Anti-patterns to forbid

Do **not** create additional:

- `OpenWAService` / `WhatsAppHelper` / `WhatsAppSender` outside the communication layer
- Second inbound webhook in theme *and* Companion
- Direct `wp_remote_post` to OpenWA-Dev from booking/payment classes
- Competing admin UIs (Customizer + Mission Control + new top-level menu) without shell integration

## Consolidation target

```text
NGC_Notification_Service  (orchestrator)
        |
        +-- EmailProvider      → NGC_Email_Adapter
        +-- WhatsAppProvider   → interface
        |         |
        |         +-- OpenWaDevProvider   (primary candidate)
        |         +-- WaAutomateProvider  (legacy fallback)
        |         +-- MetaCloudProvider   (future)
        +-- SmsProvider        → existing webhook formatter (optional)
        +-- InAppProvider      → admin/intel (optional)
```

Studio channel catalog and Intelligence ops alerts may **delegate** to this service rather than owning WA HTTP.

## OpenWA dashboard

| Choice | Recommendation |
|--------|----------------|
| Primary admin | NextGen Communications → WhatsApp |
| OpenWA React UI | **DIAGNOSTICS ONLY / ADMIN deep-link**, not day-to-day authority |
