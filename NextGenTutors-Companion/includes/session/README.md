# Session domain

WordPress-side orchestration for **booking → WooCommerce → NGT session → MasterStudy → live lesson**.

| File prefix | Role |
|-------------|------|
| `interface-ngc-*-provider.php` | Booking / commerce / learning / meeting / notify / CRM / audit contracts |
| `class-ngc-session-states.php` | Status constants |
| `class-ngc-session-state-machine.php` | Allowed transitions |
| `class-ngc-session-repository.php` | `wp_ngc_sessions` persistence |
| `class-ngc-ensure-session-provisioned.php` | Single provision command |
| `class-ngc-session-checkout.php` | Cart + order integrity |
| `class-ngc-session-presenter.php` | Dashboard/REST payloads without join URLs |
| `class-ngc-session-launch.php` | Authorized URL issuance |
| `class-ngc-product-catalog.php` | Official SKUs |

Do not return meeting URLs from list/get booking or dashboard JSON. Use launch.

See `docs/CODEMAPS/session-commerce.md`.
