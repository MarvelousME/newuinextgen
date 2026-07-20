# Sanitisation Report — Stage 1 (Discovery Only)

## Policy

- **No files deleted.**
- **No files quarantined yet** (`audit-reports/quarantine/manifest.json` items = `[]`).
- Restoration tool present: `php scripts/restore-quarantined-files.php`.

## Candidate cohorts (from inventory)

| Cohort | Count | Status | Action |
|--------|------:|--------|--------|
| `to-discard/` tree | 584 | REDUNDANT / DUPLICATE | Quarantine after Stage 2 baseline + dependency proof |
| Exact duplicate hash groups | 331 | DUPLICATE | CONSOLIDATE — prefer canonical project path |
| Magic UI extracted tree | 957 | NOT VERIFIED (conversion) | Keep under `reference-style-extraction` only |
| Sourcemaps / backups | see CSV | UNUSED / REMOVE | Review individually |

## Unsafe automatic removal

Do **not** auto-delete:

- Any of 39 PHP files flagged `dynamic_load_risk` in `php-analysis.json`
- Assets referenced only from Elementor/WPBakery post meta (scan not yet run — **BLOCKED**)
- Translation, payment, auth, role, security, migration code

## Next sanitisation gate

Sanitisation (Stage 8) is **BLOCKED** until:

1. Elementor/WPBakery metadata asset reference scan exists
2. Stage 2 baseline smoke + restore drill succeeds
3. Duplicate groups reviewed with canonical destination chosen
