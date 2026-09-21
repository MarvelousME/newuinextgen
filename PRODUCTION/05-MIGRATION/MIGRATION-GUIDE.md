# Migration guide

From a running NextGen site or static HTML:

1. Backup.
2. Install PRODUCTION packages (Companion first).
3. Html Importer: point at `webpages-content` / uploaded HTML, dry-run, import, rollback if needed, deactivate.
4. Confirm pages in PAGE-MATRIX still resolve via theme routers.
5. Map any leftover `ngt_*` options to Companion — live schema is `ngc_*`.