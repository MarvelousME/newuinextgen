# UI Library — Developer Guide

**Status:** VERIFIED (2026-07-20)  
**Mount:** `ui-library/` → `wp-content/ngt-ui-library`  
**Bootstrap:** `NGT_UI_Bootstrap` via `NGC_UI_Library_Bridge`

## Overview

The shared UI library reimplements Magic UI components as **PHP + CSS + lightweight JS** (no React runtime). Companion boots the library; all editors and shortcodes call a single renderer.

```
Companion (NGC_UI_Library_Bridge)
    └── NGT_UI_Bootstrap
            ├── NGT_UI_Registry
            ├── NGT_UI_Renderer  ← canonical render path
            ├── NGT_UI_Assets    ← conditional enqueue
            ├── NGT_UI_Shortcodes
            └── Editor adapters (Gutenberg / Elementor / WPBakery)
```

## Canonical APIs

### PHP

```php
echo ngt_render_ui_component( 'aurora-text', [
    'text' => 'Aurora Learning',
] );

echo ngt_render_ui_component( 'income-calculator', [
    'hourly_rate'    => 350,
    'hours_per_week' => 12,
] );
```

### Shortcodes

| Shortcode | Purpose |
|-----------|---------|
| `[ngt_ui component="slug" …]` | Generic Magic UI component |
| `[ngt_{slug_with_underscores}]` | Auto-alias per registered slug |
| `[ngt_magic_card]` / `[ngt_border_beam]` / `[ngt_marquee]` | Dedicated aliases |
| `[ngt_income_calculator]` | Tutor earnings estimator (product requirement) |

### Theme CMS shortcodes (parallel stack)

| Shortcode | Purpose |
|-----------|---------|
| `[ng_ui_component slug="hero" page_key="home"]` | Theme CMS component via `NGC_UI_Library` |
| `[ng_ui_component component="magic-card"]` | Routes to Magic UI when bridge mode allows |

See [dual-ui-stack.md](./dual-ui-stack.md) for coexistence rules.

## Component types

| Type | Location | Examples |
|------|----------|----------|
| Dedicated | `ui-library/components/class-ngt-ui-*.php` | `magic-card`, `border-beam`, `marquee`, `income-calculator` |
| Catalog-driven | `ui-library/catalog/components.php` + `NGT_UI_Catalog_Component` | 74+ Magic UI slugs by `kind` |

### Catalog kinds

`button`, `text`, `pattern`, `card`, `device`, `progress`, `list`, `media`, `map`, `interactive`, `misc`

Each kind may load an additional CSS handle: `ngt-ui-kind-{kind}`.

## Design tokens

| File | Role |
|------|------|
| `ui-library/tokens/tokens.css` | Aggregator |
| `ui-library/tokens/colors.css` | `--ngt-color-*`, `--ngt-color-magic-accent*` |
| `ui-library/tokens/class-ngt-ui-tokens.php` | PHP defaults for schema + inline styles |

Catalog components default accent colors to brand tokens (`#c4a35a` / `#3b2f6e`), not demo purple.

## Asset loading

- **Frontend:** `NGT_UI_Assets::enqueue_for( $slug )` on render
- **Admin:** scoped to `ngt-ui-*`, `ngc-operations`, block editor, Elementor screens only
- **Handles:** `ngt-ui-tokens` → `ngt-ui-base` → component/kind/catalog CSS

## Income calculator

**Shortcode:** `[ngt_income_calculator]`  
**Class:** `NGT_UI_Income_Calculator`  
**Assets:** `income-calculator.css`, `income-calculator.js`

| Attribute | Default | Description |
|-----------|---------|-------------|
| `hours_per_week` | `10` | 1–40 (range input) |
| `hourly_rate` | `350` | ZAR per hour |
| `platform_fee` | `15` | Platform % (0–30) |
| `weeks_per_month` | `4.33` | Monthly multiplier |
| `currency_symbol` | `R` | Display prefix |

Used on **Become a Tutor** (legacy setup: `[ngt_income_calculator]` in page content).

## Editor adapters

| Editor | Integration | Status |
|--------|-------------|--------|
| Gutenberg | `ngt-ui/component` block | VERIFIED |
| Elementor | `ngt_ui_component` widget | VERIFIED (when Elementor active) |
| WPBakery | `ngt_ui_vc` element | PARTIAL (adapter present; WPBakery not in Docker) |

All adapters call `NGT_UI_Renderer::render()` — no per-editor render forks.

## Admin screens

**NextGen → UI Library** (registry, preview, diagnostics)  
**NextGen → UI Import & Merge** (theme CMS import + dual UI mode setting)

## CLI

```bash
wp ngt ui list
wp ngt ui validate
```

## Demo & smoke

| Resource | Path |
|----------|------|
| Demo page | http://localhost:8900/ngt-ui-demo/ |
| Smoke script | `docker/ngt-ui-smoke.php` (mount manually or eval in WP-CLI) |

```powershell
cd docker
docker compose run --rm --entrypoint wp wpcli --path=/var/www/html eval "echo do_shortcode('[ngt_income_calculator]') ? 'IC_OK' : 'FAIL';" --allow-root
```

## Adding a new dedicated component

1. Create `ui-library/components/class-ngt-ui-{slug}.php` extending `NGT_UI_Component_Base`
2. Add CSS/JS under `ui-library/assets/css|js/components/`
3. Register in `class-ngt-ui-bootstrap.php` before `NGT_UI_Catalog_Loader::register_all()`
4. Register assets in `class-ngt-ui-assets.php`
5. Add shortcode alias in `class-ngt-ui-shortcodes.php` if not covered by auto-alias
6. Run `wp ngt ui validate` and extend smoke script

## Related docs

- [component-catalogue-stage5.md](./component-catalogue-stage5.md)
- [dual-ui-stack.md](./dual-ui-stack.md)
- [gutenberg-guide.md](./gutenberg-guide.md) · [elementor-guide.md](./elementor-guide.md) · [wpbakery-guide.md](./wpbakery-guide.md)
- [ADR-001](./adr/ADR-001-legacy-plugin-deny.md)
