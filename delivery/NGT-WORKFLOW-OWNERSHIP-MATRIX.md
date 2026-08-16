# NGT Workflow Ownership Matrix (System of Record)

**Date:** 2026-08-16  
**Rule:** One authority per concept. Plugins are adapters/projections.

| Concept | Authority (SoR) | Adapter / projection | Must not own |
|---|---|---|---|
| WordPress identity | WordPress users/roles | — | MasterStudy user creation as independent identity |
| Tutor application | Companion People / tutor applications | Fluent Forms (input only) | FluentCRM |
| Tutor verification decision | Companion verification / applications | CRM tags as projection | GamiPress badges |
| Tutor safety facts | `NGC_Safeguarding` + verification records | GamiPress badge display | AutomatorWP |
| Tutor listing visibility | Marketplace + listing eligibility policy | Theme cards | GamiPress |
| Booking commercial state | `NGC_Bookings` | Amelia calendar execution | Woo hooks writing Amelia DB directly |
| Calendar execution | Amelia (via adapter) | — | Earnings / CRM |
| Order | WooCommerce | — | — |
| Payment capture | WooCommerce + PayFast | PayFast ITN | NextGen card storage |
| Session business state | Session orchestrator / sessions | Jitsi meeting URL | Amelia as sole session SoR |
| Course progress | MasterStudy (via adapter) | — | Student identity |
| CRM segment / nurture | FluentCRM | Sequences | Booking/payment/lesson state |
| Rewards points/badges | NGT gamification rules → GamiPress projection | `NGC_Gamipress_Adapter` | Rating / verification / listing |
| Ratings | `NGC_Reviews` | GamiPress if awarded | GamiPress as rating SoR |
| Earnings | `ngc_earnings` ledger | Tutor dashboard projection | Amelia completion alone |
| Payout | `ngc_payouts` + finance rules | Bank/EFT adapter (missing) | AutomatorWP |
| Safety case | `ngc_safeguarding_cases` | Fluent Support tickets | AutomatorWP / AI adjudication |
| Consent (POPIA) | Consent store (`NGC_Popia_Consent`) | Checkout UI | Inferred from booking |
| Workflow orchestration | `NGC_Workflow_Authority` (= ecosystem-workflow) | Studio/Hub/AutomatorWP producers | Competing side-effect engines |
| Notifications transactional | Notification application service (to introduce) | Email/SMS/FluentCRM marketing | Duplicate Amelia+CRM+Notifier |

Dual authorities are **architecture defects** to eliminate during migration (shadow → compare → disable legacy).
