# 03 — SWOT

## Strengths

- Pluggable Nest gateway with REST, webhooks, multi-session, dashboard
- MIT, Docker-native, Postgres/Redis/S3 adapters
- Better control-plane story than ad-hoc `npx @open-wa/wa-automate`
- Aligns with NextGen need for governed WhatsApp capability
- Existing NGT durable queue / policy / FluentCRM can wrap it

## Weaknesses

- Unofficial `whatsapp-web.js` transport (ban/ToS/session risk)
- Early version `0.1.6`; low Jest coverage thresholds
- Different API than existing theme OpenWA → migration work
- Duplicate “OpenWA” naming will confuse operators
- No official template messaging (WABA templates) as first-class Meta product feature

## Opportunities

- Consolidate Studio WA webhook stubs + theme OpenWA into one provider contract
- Wire booking/payment/lesson events to optional WhatsApp without blocking transactions
- Parent-first safeguarding with channel policies
- Future swap to Meta Cloud API without rewriting workflows
- RAD subsystem manifest for `communication.whatsapp.openwa`

## Threats

- Dual unofficial stacks (wa-automate + OpenWA-Dev) if both left enabled
- Spamming / marketing without consent
- Direct messaging minors
- Secrets in theme Customizer plaintext options
- Coupling workflows to OpenWA-Dev DTOs
- Gateway downtime incorrectly failing bookings
- Account ban taking down the only WA number

## Conclusion

Technically attractive as a **provider**, strategically useful only if NextGen first builds a **channel abstraction** and accepts unofficial-transport risk for staging (or commits to a future official BSP).
