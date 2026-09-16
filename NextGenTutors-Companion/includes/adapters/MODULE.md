# Adapters (Integrations module surface)

**Owner:** Agent J  
**Canonical module docs:** [`../integrations/MODULE.md`](../integrations/MODULE.md)

This directory holds `NGC_Integration_Adapter` implementations used by workflow orchestrators. It is part of the **integrations** module together with `includes/integrations/`.

- **Owns:** plugin façades (Amelia, MasterStudy, FluentCRM/Support, Jitsi, booking helpers, email/audit/verification adapters).
- **Does not own:** matching scoring, AI model runtime, or payout ledger/scheduler (see integrations MODULE.md).
