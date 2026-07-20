# ADR-002: Dual UI library coexistence

## Status

Accepted (2026-07-20)

## Context

NextGen Tutors ships two UI systems:

1. **Theme CMS** (`NGC_UI_Library`) — page-keyed partials in `inc/defaults/`, shortcode `[ng_ui_component slug="…"]`.
2. **Magic UI** (`NGT_UI` / `ui-library/`) — 78 catalog components, shortcodes `[ngt_ui]`, `[ngt_income_calculator]`.

Both are intentional. Product needs theme-owned marketing sections **and** Magic UI effects without React in production.

## Decision

1. **Keep both systems** with option-centric routing via `NGC_NGT_UI_Bridge` and `ngc_ui_library_mode` (`both` | `theme_only` | `magic_only`).
2. **Canonical renderer for Magic UI** remains `NGT_UI_Renderer` / `ngt_render_ui_component()` for all editors (Gutenberg, Elementor, WPBakery adapter, PHP).
3. **Do not merge** `NGC_UI_Library` into `ui-library/` without a migration plan — different data models (page keys vs component slugs).
4. **Deprecation path** (only if product retires theme CMS partials):
   - Phase 1: Debug notice when `slug` resolves to theme CMS but an equivalent Magic slug exists.
   - Phase 2: Default `ngc_ui_library_mode` to `magic_only` on new installs.
   - Phase 3: Move remaining theme partials to dedicated `NGT_UI` components or block patterns.
   - Phase 4: Remove `NGC_UI_Library` render path after 2 release cycles with migration guide.

## Consequences

- Editors must understand **which shortcode** to use (`ng_ui_component` vs `ngt_ui`).
- Documentation (`dual-ui-stack.md`) is the operator source of truth.
- ADR-001 already documents bridge modes alongside the legacy plugin deny policy.

## Verification

```powershell
php NextGenTutors-Companion/tests/run.php
php ui-library/tests/integration-smoke.php
# Docker:
docker compose run --rm --entrypoint wp wpcli --path=/var/www/html eval "echo class_exists('NGC_NGT_UI_Bridge')?'BRIDGE_OK':'FAIL';" --allow-root
```
