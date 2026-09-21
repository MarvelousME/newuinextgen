# Integrations guide

Companion adapters (`includes/adapters/`, `includes/integrations/`):

| System | Class | Behaviour if absent |
|---|---|---|
| WooCommerce / PayFast | `NGC_PayFast`, `NGC_Parent_Checkout`, Woo catalog | `{ ok: false }` / skip; no fatal |
| Amelia | `NGC_Amelia_Adapter` | `is_available()` false |
| MasterStudy LMS | `NGC_Masterstudy_Adapter` | notice + early return |
| FluentCRM | `NGC_Fluentcrm_Adapter` | try/catch |
| GamiPress / AutomatorWP / Fluent Support | bootstrap | inactive reasons |

Theme Woo hooks in `inc/workflows.php` no-op when `NGC_Plugin` exists.

WhatsApp OpenWA REST (`bi/v1`) remains theme-owned in this release (`inc/openwa.php`).