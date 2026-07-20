# UI Library

Canonical WordPress-native component system for NextGenTutors.

- Contracts: `contracts/`
- Registry / assets: `registry/`
- Renderer: `rendering/class-ngt-ui-renderer.php`
- Components (Stage 5 wave 1): `magic-card`, `border-beam`, `marquee`
- Shortcodes: `[ngt_ui]`, `[ngt_magic_card]`, `[ngt_border_beam]`, `[ngt_marquee]`
- PHP: `ngt_render_ui_component( $slug, $settings )`
- Loaded by Companion via `NGC_UI_Library_Bridge` from `wp-content/ngt-ui-library`
- Reference sources: `../reference-style-extraction/` (never enqueue directly)

See `../documentation/ui-library-guide.md` and `../documentation/component-catalogue-stage5.md`.
