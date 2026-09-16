# Integrations module

**Owner:** Agent J (Companion modularization)  
**Paths:** `includes/integrations/`, `includes/adapters/`  
**Registry id:** `integrations` → `bootstrap.php`

## Owns

Third-party and ecosystem bridges that connect Companion to booking, payments gateways (checkout/ITN), LMS, CRM/support, automation, and platform packs — plus the adapter layer used by workflows.

### `includes/integrations/`

| Area | Key classes |
|------|-------------|
| Amelia booking | `NGC_Amelia`, `NGC_Amelia_Bootstrap` |
| PayFast (checkout / ITN / WC gateway) | `NGC_PayFast`, `NGC_PayFast_ITN`, `NGC_PayFast_Gateway`, `NGC_Parent_Checkout` |
| LMS | `NGC_Lms` |
| CRM / support | `NGC_Fluentcrm`, `NGC_Fluent_Support` |
| Ecosystem / packs / hub | `NGC_Ecosystem_Platform_Bridge`, `NGC_Content_Pack_Bridge`, `NGC_Automation_Hub_Bridge`, `NGC_Plugin_Manager_Bridge` |
| Automator / workflows | `NGC_AutomatorWP_Integration`, `NGC_AutomatorWP_Importer`, `NGC_Workflow_Spec_Registry`, `NGC_Integrate_Runtime` |
| Commerce / ops | `NGC_WooCommerce_Catalog`, `NGC_Session_Reminders`, `NGC_Referrals`, `NGC_Popia_Consent` |
| Local stack config | `NGC_Integrations_Bootstrap` (class; not this stub) |

### `includes/adapters/`

Adapter contract and implementations (`NGC_Integration_Adapter`, `NGC_Adapter_Base`, Amelia, Amelia availability, MasterStudy, FluentCRM, Fluent Support, Jitsi, internal booking, email, audit, verification).

Adapters are the stable façade for orchestrators; integration classes hold plugin-specific hooks and config.

## Does not own

| Concern | Owner |
|---------|--------|
| Core matching / scoring | `includes/matching/` (Agent G) |
| AI model runtime / agentic agents | `includes/ai/`, `includes/agentic/` (Agent I) |
| Payments ledger, payout scheduler & export | `includes/payments/` (Agent H). Legacy `class-ngc-payout-scheduler.php` / `class-ngc-payout-export.php` under `integrations/` (if still present) belong to Agent H — leave untouched. |
| Hub / Docker / plan docs | Out of scope for this module |

## Bootstrap

`bootstrap.php` is a **no-op** marker for `NGC_Module_Registry`. It documents ownership only; it does not require adapter/integration class files or change autoload behavior. Runtime wiring remains in the main plugin bootstrap and classes such as `NGC_Integrations_Bootstrap` / `NGC_Integrate_Runtime`.
