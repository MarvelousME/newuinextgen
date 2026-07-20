# Dual UI Stack — NGC_UI_Library + NGT_UI

**Status:** VERIFIED (2026-07-20)  
**Decision:** Keep both systems; option-centric routing (no double render)

## Why two stacks?

| Stack | Owner | Slugs | Use case |
|-------|-------|-------|----------|
| **NGC_UI_Library** | Companion + theme | `hero`, `tutor-card`, `pricing-card`, … | CMS-driven page sections, provider data |
| **NGT_UI** | `ui-library/` | `magic-card`, `aurora-text`, `income-calculator`, … | Magic UI effects, calculators, marketing FX |

Both are intentional. Deprecation of `NGC_UI_Library` is **not** planned for theme template-parts in this phase.

## Bridge class

**File:** `NextGenTutors-Companion/includes/ui-library/class-ngc-ngt-ui-bridge.php`  
**Option:** `ngc_ui_library_mode` (default: `both`)

```
[ng_ui_component] / ngc_ui_render_component filter
        │
        ▼ priority 8 — NGC_NGT_UI_Bridge::maybe_render_magic
        │   (if slug in NGT_UI_Registry and mode allows)
        ▼ priority 9 — block_theme_when_magic_only
        ▼ priority 10 — NGC_UI_Library::render_component (theme CMS)
```

## Modes

| Mode | `[ng_ui_component]` behavior | `[ngt_ui]` / NGT aliases |
|------|------------------------------|--------------------------|
| `both` (default) | Theme slug → CMS; Magic slug → NGT; `component` attr forces Magic | Always NGT |
| `theme_only` | CMS only | Always NGT (unchanged) |
| `magic_only` | NGT registry only; unknown → HTML comment | Always NGT |

Configure: **NextGen → UI Import & Merge → Dual UI library mode**

## Shortcode routing examples

```text
# Theme CMS hero (both / theme_only)
[ng_ui_component slug="hero" page_key="home"]

# Magic UI via component attribute (both / magic_only)
[ng_ui_component component="magic-card" title="Hello"]

# Magic UI direct (always)
[ngt_ui component="aurora-text" text="NextGen Tutors"]
[ngt_income_calculator hourly_rate="350"]

# Legacy alias (always Magic)
[ngc_ui_component slug="tutor-card" page_key="home"]
```

## Slug collision rule (`both` mode)

If a slug exists in **both** registries, theme CMS wins unless `component` attribute is set.

## REST verification

`GET /wp-json/ngc/v1/ui-library/verify` — theme CMS providers and components (requires `manage_options`).

Magic UI registry: **NextGen → UI Library** admin screen or `wp ngt ui list`.

## Future deprecation path (not active)

If `NGC_UI_Library` is retired later:

1. Add thin alias `ng_ui_component` → `ngt_ui` for Magic slugs only
2. Migrate theme partials to call `ngt_render_ui_component()` or dedicated PHP partials
3. Keep `ngc_ui_render_component` filter for one release with `_deprecated` notices

## Related

- [ui-library-guide.md](./ui-library-guide.md)
- [ADR-001](./adr/ADR-001-legacy-plugin-deny.md) (dual UI section)
