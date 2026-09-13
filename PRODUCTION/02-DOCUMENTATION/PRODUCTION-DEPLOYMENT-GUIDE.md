# Production deployment guide

Target site: `https://www.nextgentutors.co.za` (timezone Africa/Johannesburg, currency ZAR).

1. Take a full backup of any existing site before overlaying packages.
2. Deploy to staging first using the ZIP upload path in INSTALLATION-GUIDE.md.
3. Inject secrets **only in wp-admin / wp-config** after install: PayFast merchant id/key/passphrase, SMTP, AI BYOK keys, gateway HMAC. None of these ship in ZIPs.
4. Disable `WP_DEBUG_DISPLAY` on production. Keep `WP_DEBUG_LOG` until the first week is clean.
5. Replace WP-Cron with system cron.
6. TLS, daily DB backups, and object cache are host concerns — see BACKUP-RESTORE.md and SECURITY-GUIDE.md.
7. External plugins (WooCommerce, Elementor, Amelia, MasterStudy, FluentCRM, PayFast gateway) are licensed separately. Companion adapters fail closed if they are absent.

Do not use `docker/.env` values (`NextGenAdmin!2026`, `staging-local-secret`) in production.