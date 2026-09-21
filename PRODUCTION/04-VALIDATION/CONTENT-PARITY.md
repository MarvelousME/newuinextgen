# Content parity

Authority: `PAGE_CONTENT_OWNERSHIP.md` in the theme root.

- Keep `prototypes/*-body.php` as body source when blend is on.
- Production defaults live in `inc/defaults-production/`.
- `template-parts/pages/*` are flatten artifacts — unwired.
- Stale prototype SQL (`ngt_session_logs`) is documentation debt, not live schema.