# NextGen Tutors UI Library

Bespoke design system and component layer for **BeyondInfinity** theme + **NextGenTutors-Companion** v1.9.0.

## Architecture

```
Research HTML + Old Theme          Companion (data)              Theme (presentation)
─────────────────────────          ────────────────              ────────────────────
scripts/analyze-ui-artifacts.mjs → inventories JSON     →      NGC_UI_*_Data_Provider
docs/ui-library/                   NGC_UI_Provider_Registry      template-parts/ui-library/
                                   NGC_UI_Component_Registry     assets/css/ng-ui-*.css
                                   NGC_UI_Import_Scanner         inc/ui-library/bootstrap.php
                                   Admin: UI Import & Merge
```

**Strict data rule:** Tutor, pricing, review, stats, and dashboard values flow through providers — never pasted into templates.

See also: [SYSTEM-OVERVIEW.md](../SYSTEM-OVERVIEW.md) · [CODE-REVIEW-2026-07-06.md](../CODE-REVIEW-2026-07-06.md) · [NEXT-STEPS.md](../NEXT-STEPS.md)

```powershell
# Regenerate inventories from Desktop artifacts
node scripts/analyze-ui-artifacts.mjs

# Verify no hardcoded dynamic values in UI partials
php NextGenTutors-Companion/scripts/verify-ui-library.php

# REST audit (logged-in admin)
GET /wp-json/ngc/v1/ui-library/verify
```

## Shortcodes

- `[ng_ui_component slug="hero" page_key="home"]`
- `[ngc_ui_component slug="tutor-card" limit="6"]`

## Admin

**WP Admin → NGC Operations → UI Import & Merge**

- Scans artifact inventory
- Lists duplicate headings
- Provider availability matrix
- Dry-run import (CMS field mapping)

## Files

| Path | Purpose |
|------|---------|
| `docs/ui-library/ANALYSIS-REPORT.md` | HTML + old theme analysis |
| `docs/ui-library/PAGE-COMPONENT-MATRIX.md` | Page → component mapping |
| `docs/ui-library/VERIFICATION-CHECKLIST.md` | Acceptance checklist |
| `NextGenTutors-Companion/includes/ui-library/` | Providers, registry, import |
| `NextGenTutors-BeyondInfinity/template-parts/ui-library/` | PHP partials |
| `NextGenTutors-BeyondInfinity/assets/css/ng-ui-*.css` | Tokens + components |

## Elementor / WPBakery

Page builder bridges hook `ngc_ui_component_definitions` and `ngc_ui_render_component`. Native widgets are **Phase 2** — use shortcodes in builder blocks until widgets ship.

## Content migration

1. Run `analyze-ui-artifacts.mjs`
2. Review `docs/ui-library/inventories/duplicates.json`
3. Map static copy to `NGC_Section_CMS` fields via Import & Merge
4. Never import tutor names, prices, or ratings from HTML artifacts
