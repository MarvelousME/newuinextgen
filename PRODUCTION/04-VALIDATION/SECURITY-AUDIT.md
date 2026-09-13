# Security audit (packaging pass)

- Companion uninstall is fail-safe (no drop unless `NGC_DROP_TABLES`).
- Theme no longer defines `NGC_VERSION`; Companion detection is `class_exists('NGC_Plugin')`.
- Secret scan results: see `inventory-meta.json` → `secret_hits` (review; many hits are password *generators* or docs).
- Do not ship `docker/.env`, PayFast live keys, or `config/ngt-onepass-eval.php`.
- REST permission callbacks remain the runtime control; this document does not replace a pentest.