# UI Library Component Catalogue (Stage 5)

**Status:** VERIFIED — **78** components registered (77 Magic UI + income-calculator)  
**Licence origin:** Magic UI MIT (reimplemented; no React embed)

## Canonical APIs

```php
echo ngt_render_ui_component( 'aurora-text', [
  'text' => 'Aurora Learning',
] );
```

```text
[ngt_ui component="aurora-text" text="Aurora Learning"]
[ngt_aurora_text text="Aurora Learning"]
[ngt_magic_card title="Find Your Tutor" content="..."]
[ngt_border_beam]Content[/ngt_border_beam]
[ngt_marquee items="Math|Science|English"]
[ngt_income_calculator hours_per_week="12" hourly_rate="350"]
```

## Dual stack (theme CMS + Magic UI)

See `documentation/dual-ui-stack.md`. Theme shortcode `[ng_ui_component]` can route to Magic UI via `component` attribute when `ngc_ui_library_mode` is `both` or `magic_only`.

## Architecture

| Layer | Path |
|-------|------|
| Dedicated components | `ui-library/components/class-ngt-ui-{magic-card,border-beam,marquee,income-calculator}.php` |
| Catalog (remaining Magic UI) | `ui-library/catalog/components.php` + `class-ngt-ui-catalog-component.php` |
| Shared catalog CSS/JS | `assets/css/components/catalog.css`, `assets/js/components/catalog.js` |
| Kind CSS (per catalog kind) | `assets/css/kinds/kind-*.css` |
| Design tokens | `tokens/tokens.css`, `tokens/class-ngt-ui-tokens.php` |
| Renderer | `NGT_UI_Renderer` / `ngt_render_ui_component()` |

## Components

All Magic UI registry slugs are available via `[ngt_ui component="…"]` and auto aliases `[ngt_{slug_with_underscores}]`.

First-wave dedicated (custom PHP):

| Slug | Shortcode | Status |
|------|-----------|--------|
| `magic-card` | `ngt_magic_card` | VERIFIED |
| `border-beam` | `ngt_border_beam` | VERIFIED |
| `marquee` | `ngt_marquee` | VERIFIED |
| `income-calculator` | `ngt_income_calculator` | VERIFIED |

Catalog wave (74 entries including `animated-subscribe-button`): text, buttons, patterns, devices, progress, lists, media, interactive — VERIFIED via smoke render markers.

Gutenberg / Elementor / WPBakery adapters: **PARTIAL** — single dynamic block/widget/element calling `NGT_UI_Renderer` (see `documentation/gutenberg-guide.md`). Runtime verify **BLOCKED** while Docker daemon is down.

## Asset loading

Conditional via `NGT_UI_Assets::enqueue_for( $slug )` — catalog components share `ngt-ui-catalog` CSS/JS handles; dedicated components keep their own files.

## Demo page

`/ngt-ui-demo/` — gallery of representative components.
