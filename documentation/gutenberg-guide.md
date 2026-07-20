# Gutenberg / Elementor / WPBakery — UI Library

**Status:** PARTIAL — adapters implemented; Docker re-verify BLOCKED until daemon is up

## Canonical rule

All editors call `NGT_UI_Renderer::render()` / `ngt_render_ui_component()`. No duplicate render logic.

## Gutenberg

| Item | Value |
|------|-------|
| Block | `ngt-ui/component` |
| Path | `ui-library/integrations/gutenberg/` |
| Mode | Dynamic SSR (`render_callback`) |

## Elementor

| Item | Value |
|------|-------|
| Widget | `ngt_ui_component` |
| Category | `ngt-ui` |
| Path | `ui-library/integrations/elementor/` |
| Loads when | `elementor/widgets/register` fires |

## WPBakery

| Item | Value |
|------|-------|
| Base | `ngt_ui_vc` |
| Path | `ui-library/integrations/wpbakery/` |
| Loads when | `vc_before_init` / `vc_map` available |

## Shortcodes / PHP (already VERIFIED)

```text
[ngt_ui component="aurora-text" text="…"]
[ngt_magic_card title="…"]
```

```php
echo ngt_render_ui_component( 'aurora-text', [ 'text' => '…' ] );
```

## Admin

NextGen → **UI Library** / **UI Preview** / **UI Diagnostics**
