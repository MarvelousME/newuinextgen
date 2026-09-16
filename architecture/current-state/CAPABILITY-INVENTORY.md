# CAPABILITY-INVENTORY

**Last Updated:** 2026-09-16  
Bootstrap from sacred package contracts + `.agent-audit/11-functional-capability-matrix.md`.

Machine registry: `architecture/capabilities/*.json` (loaded by `NGC_Capability_Registry`).

| Capability ID | Provider | Protocol | Policy Bridge | Status |
|---------------|----------|----------|---------------|--------|
| theme.render.page | beyondinfinity | internal | — | Registered |
| theme.shortcode.consume | beyondinfinity | shortcode | — | Registered |
| companion.rest.query | companion | rest | partial | Registered |
| companion.rest.command | companion | rest | partial | Registered |
| companion.shortcode.render | companion | shortcode | — | Registered |
| matching.propose | companion | command | **yes** (`NGC_Matching::create_from_find_tutor`) | Registered |
| booking.create | companion | command | **yes** (`NGC_Bookings::create`) | Registered |
| payment.authorize | companion | command | **yes** (`NGC_Payments::settle_order`) | Registered |
| agent.execute | companion | command | via agent control plane | Registered |
| notification.send | companion | event | no | Registered |
| platform.capability.invoke | companion | internal | bridge API | Registered |
| memory.* / talent.* | companion | internal | **yes** (service layer) | Registered |
| ai.transport.dispatch | ai-integration | rest | — | Registered |
| ai.policy.gate | ai-integration | internal | — | Registered |
| content.html.import | html-importer | command | — | Registered |
| ops.plugin.install | plugin-manager | command | — | Registered |
| ops.plugin.health | plugin-manager | query | — | Registered |

## Enforcement notes (TD-RAD-001)

- Privileged **service entrypoints** call `NGC_Policy_Bridge::authorize_domain()`.
- Payment settlement: WC hooks / cron / explicit `trusted_system` (PayFast ITN) may bypass interactive ACL when capability exists; anonymous uid=0 alone is **not** trusted.
- Remaining gap: not every REST helper or payout path is bridge-wrapped yet.
