# NGT Workflow Provider Health

**Date:** 2026-08-16  
**Note:** Static / capability presence — not live Docker probe in this run.

| Provider | Role after migration | Health signal |
|---|---|---|
| NGC_Workflow_Authority | Orchestration authority | Code present |
| FluentCRM | CRM projection | Adapter + port |
| GamiPress | Gamification projection | Adapter + port |
| MasterStudy | Learning projection | Adapter + port |
| Amelia | Calendar adapter | Unchanged |
| WooCommerce / PayFast | Commerce / capture | Unchanged settle |
| AutomatorWP | Non-authoritative | Core side effects filtered |
| Hub | Notifications; no role grant | add_user_role skipped |
| Bank/EFT | Payout execution | **UNVERIFIED / ABSENT** |
| Jitsi observer | Session observer | **UNVERIFIED** |
