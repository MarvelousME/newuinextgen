# Configuration

Configuration is constants-first: a PHP constant (typically defined in `wp-config.php`) always beats the corresponding option. Options are editable on the plugin settings page.

| Constant | Option | Default | Purpose |
| --- | --- | --- | --- |
| `NGTAI_AGENTS_API_URL` | `ngtai_agents_api_url` | — | Base URL of agents-api. Must be http(s), no credentials; normalized and trailing-slash trimmed. |
| `NGTAI_AGENTS_API_KEY_ID` | `ngtai_agents_api_key_id` | — | Signing key identifier sent as `X-NGT-Key-Id`. |
| `NGTAI_AGENTS_API_SECRET` | `ngtai_agents_api_secret_encrypted` | — | HMAC secret. The option form is encrypted at rest via `NGTAI_Crypto`; the constant form is read as-is. |
| `NGTAI_ENABLED` | `ngtai_enabled` | `1` | Master switch for event delivery. |
| `NGTAI_DEMO_MODE` | `ngtai_demo_mode` | `0` | Demo/sandbox mode (also permits plain-HTTP localhost endpoints). |
| `NGTAI_TIMEOUT_SECONDS` | `ngtai_timeout_seconds` | `10` | HTTP timeout, clamped to 2–60s. |
| `NGTAI_MAX_ATTEMPTS` | `ngtai_max_attempts` | `5` | Maximum delivery attempts before dead-letter. |
| `NGTAI_RETRY_BASE_SECONDS` | `ngtai_retry_base_seconds` | `30` | Base retry delay. |
| `NGTAI_CALLBACK_SKEW_SECONDS` | `ngtai_callback_skew_seconds` | `300` | Allowed signature timestamp skew, clamped to 30–900s. |
| `NGTAI_NONCE_RETENTION_DAYS` | `ngtai_nonce_retention_days` | `30` | Durable nonce retention before purge. |
| `NGTAI_GLOBAL_PAUSE` | `ngtai_global_pause` | `0` | Kill switch: denies all agent delivery and actions. |
| `NGTAI_ALLOWED_HOSTS` | `ngtai_allowed_hosts` | API host | Outbound host allowlist (comma/space separated or array). Defaults to the API URL's host. |
| `NGTAI_TENANT` | — | `nextgentutors` | Tenant identifier stamped on envelopes. |

`NGTAI_Config::configured()` is true only when URL, key ID, and secret are all set; the API client refuses to send otherwise.

## Config files

- `config/event-schemas.php` — the event registry: per-type schema version, allowed payload fields, data classification, `external_delivery_allowed`, `policy_required`, and redaction profile.
- `config/payload-allowlists.php` — redaction profiles (`default`, `minor`, `finance`, `safeguarding`): blocked key patterns, minimized PII keys, learner identifier keys, and minor `never_send` fields; plus the `match.requested` candidate field allowlist.
- `config/capabilities.php` — capability map for admin/REST access.

## Example (`wp-config.php`)

```php
define( 'NGTAI_AGENTS_API_URL', 'https://agents.example.com' );
define( 'NGTAI_AGENTS_API_KEY_ID', 'ngt-prod-2026-07' );
define( 'NGTAI_AGENTS_API_SECRET', '…' ); // or store encrypted via the settings page
define( 'NGTAI_CALLBACK_SKEW_SECONDS', 300 );
```
