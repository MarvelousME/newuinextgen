# IMPORTANT ops pack — absorption map

Source: `NextGen-files/IMPORTANT/` (operator deliverables). Runtime code lives in Companion; this folder keeps the field catalogue and automation reference only.

| Source | Status | Where it landed |
|--------|--------|-----------------|
| `Create-FluentCRM-Custom-Fields.xlsx` | Absorbed | `NGC_Fluentcrm_Adapter::custom_field_defs()` + this copy |
| `Consent Withdrawal Handler.php` | Absorbed | `NGC_Popia_Consent` (`?ngc_withdraw_popia=1`) |
| `WooCommerce-Checkout-Hook.php` / `Exact-Production-Deliverables.php` | Absorbed | `NGC_Popia_Consent` checkout gate + order/user audit |
| `WP-CLI Bulk Contact Import Script.php` | Absorbed | `wp ngc import_contacts <csv>` |
| `find-tutor-form.json` | Absorbed | Theme + Companion Find a Tutor fields, POPIA checkbox, CRM tags |
| `FluentCRM-Automation-JSON-Export.json` | Reference + fields | Custom fields + rating/session sync hooks; recreate flows in FluentCRM UI |
| `zoom-recording-sync.n8n.json` | Reference only | n8n Zoom→WP pattern; stack uses Jitsi — do not enable as primary |
| `chat-export-full-next-gen.json` | Skipped | Chat history dump — not product config |
| `Create-Backup-Script.sh` | Parameterized | `scripts/ops/backup-db-encrypted.sh` |
| `Verification-of-woo-products.sh` | Parameterized | `scripts/ops/verify-woo-products.sh` |
| `Verification&Compliance*.sh` | Docs + checks | `scripts/ops/verify-backup-compliance.sh` |
| `setup-ngt-platform.php` | Not runtime | Obsolete SmartHead/`ngt_*` setup; use Companion provisioning + demo seed |
| `Secure Wrapper Script.sh` | Skipped | Remote fallback for legacy theme setup — unsafe pattern |

## FluentCRM custom fields

- `popia_consent_given`, `popia_consent_date`, `popia_consent_ip`, `popia_consent_version`, `popia_processing_purpose`
- `sessions_count`, `last_session_date`, `latest_rating`, `verification_status`

Bootstrap: `wp ngc fluentcrm_bootstrap` or any CRM sync via `bootstrap_assets()`.
