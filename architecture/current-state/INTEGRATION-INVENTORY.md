# INTEGRATION-INVENTORY

| Integration | Owner | Pattern | Notes |
|-------------|-------|---------|-------|
| WooCommerce / PayFast | companion | adapter + gateway | payment.authorize |
| Amelia | companion | `NGC_*_Adapter` | booking calendar optional |
| MasterStudy | companion | adapter | LMS |
| FluentCRM / FluentSupport | companion | adapter | CRM/support |
| Jitsi / meetings | companion | adapter | meetings |
| AI BYOK models | companion AI suite | internal | models stay in Companion |
| AI transport | ai-integration | bridge plugin | signed/redacted |
| Agent gateway | `services/ngt-agent-gateway` | HTTP service | external to WP |
| Automation Hub | parallel plugin | bridge | consolidation debt |

Port interface: `NextGenTutors-Companion/includes/adapters/interface-ngc-integration-adapter.php`.
