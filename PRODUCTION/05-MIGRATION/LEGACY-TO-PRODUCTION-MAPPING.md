# Legacy to production mapping

| Legacy | Production |
|---|---|
| `ngt_*` REST | Companion `ngc/v1` (alias `ngt/v1`) |
| `ngtbi_*` | Does not exist — do not invent shims |
| Theme Tutor CPT | Companion `tutors` CPT |
| Theme DB tables | None in theme; Companion `wp_ngc_*` |
| Theme business APIs | Companion services |
| Theme UI | BeyondInfinity |
| `*-body.php` | Keep as authoritative body when blend on |
| Automation Hub CPTs | Do not migrate onto Hub if Companion is active |
| Monorepo-as-theme ZIP | Invalid — use materialized BeyondInfinity ZIP |