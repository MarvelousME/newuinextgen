# CAPABILITY-INVENTORY

Bootstrap from sacred package contracts + `.agent-audit/11-functional-capability-matrix.md`.

Machine registry: `architecture/capabilities/sacred-packages.json`.

| Capability ID | Provider | Protocol | Status |
|---------------|----------|----------|--------|
| theme.render.page | beyondinfinity | internal | Registered |
| theme.shortcode.consume | beyondinfinity | shortcode | Registered |
| companion.rest.query | companion | rest | Registered |
| companion.rest.command | companion | rest | Registered |
| companion.shortcode.render | companion | shortcode | Registered |
| booking.create | companion | command | Registered |
| payment.authorize | companion | command | Registered |
| agent.execute | companion | command | Registered |
| notification.send | companion | event | Registered |
| platform.capability.invoke | companion | internal | Registered |
| ai.transport.dispatch | ai-integration | rest | Registered |
| ai.policy.gate | ai-integration | internal | Registered |
| content.html.import | html-importer | command | Registered |
| ops.plugin.install | plugin-manager | command | Registered |
| ops.plugin.health | plugin-manager | query | Registered |

Domain capabilities still only partially exposed as first-class IDs (matching, payouts, safeguarding) — see agent audit matrix.
