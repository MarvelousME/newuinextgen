# Security guide

- REST `ngc/v1` routes use `permission_callback` (login, `manage_options`, or role caps). Do not expose Universal API (`nuapi/v1`) on production.
- Forms/AJAX: Companion and theme fallbacks must send nonces (`bi_nonce`, `wp_rest`).
- Secrets never belong in ZIPs. Review `04-VALIDATION/inventory-meta.json` `secret_hits` before each ship. Exclude `docker/.env` and `config/ngt-onepass-eval.php`.
- Output escaped (`esc_html`, `esc_url`); input sanitized (`sanitize_text_field`, `sanitize_key`).
- POPIA: Companion consent log + `[ngc_popia_withdraw]`.
- phpMyAdmin in Docker binds to 127.0.0.1 — keep it that way.