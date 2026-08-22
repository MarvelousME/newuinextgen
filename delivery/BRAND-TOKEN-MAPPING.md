# Brand Token Mapping Matrix

**Authority:** BeyondInfinity semantic tokens (`--ngt-color-*`, `--ngt-*`)  
**Skin source:** `html[data-bi-skin="beyond-infinity"]` + Customizer scheme

| Semantic token | BeyondInfinity CSS | Gutenberg (`theme.json`) | Elementor Global | WPBakery |
|----------------|------------------|--------------------------|------------------|----------|
| Brand primary | `--ngt-color-brand-primary` → `--ngt-primary` | `brand-primary` preset | `primary` system color | inherits `.ngt-*` |
| Brand secondary | `--ngt-color-brand-secondary` | `brand-secondary` | `secondary` | `.vc_btn3-color-primary` adapter |
| Brand accent | `--ngt-color-brand-accent` | `brand-accent` | `accent` | inherits |
| Text primary | `--ngt-color-text-primary` | `text-primary` | `text` | heading/body adapters |
| Text secondary | `--ngt-color-text-secondary` | `text-secondary` | — | paragraph colour |
| Page background | `--ngt-color-bg-page` | `bg-page` | — | row background |
| Elevated surface | `--ngt-color-bg-elevated` | `bg-elevated` | — | `.ngt-card` |
| Border | `--ngt-color-border` | `border` | — | column gutters |
| Success | `--ngt-color-success` | `success` | — | — |
| Warning | `--ngt-color-warning` | `warning` | — | — |
| Danger | `--ngt-color-danger` | `danger` | — | — |
| Display font | `--ngt-font-family-display` | `display` family | Primary typography | `.vc_tta` titles |
| Body font | `--ngt-font-family-body` | `body` family | Secondary typography | `.wpb_text_column` |
| Container default | `--ngt-container-default` | `wideSize: 1280px` | boxed section max-width | `.vc_row` |
| Container narrow | `--ngt-container-narrow` | `contentSize: 720px` | — | — |
| Button radius | `--ngt-button-radius` | button element radius | Theme Style | `.vc_btn3` |
| Card radius | `--ngt-card-radius` | group card style | — | `.ngt-card` |

## PHP export

`bi_brand_semantic_tokens()` in `inc/brand-style-kit.php` returns the active map for Elementor sync and documentation.

## Elementor sync

**Admin → Appearance → Brand Style Kit → Sync Elementor Global Colors & Fonts**  
Writes `_elementor_page_settings` on the active kit post.
