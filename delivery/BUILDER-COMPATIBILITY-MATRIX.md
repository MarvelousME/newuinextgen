# Builder Compatibility Matrix (Updated 2026-08-14)

| Component | Elementor | Gutenberg | WPBakery | PHP | Shared Renderer |
| --------- | --------- | --------- | -------- | --- | --------------- |
| Header | Theme Builder location optional | N/A | N/A | **PASS** (`templates/header/*`) | Nav SSOT |
| Footer | Theme Builder location optional | N/A | N/A | **PASS** | Footer SSOT |
| Home hero | Soft (widget-bearing docs only) | Soft | Soft | **PASS** kinetic | `defaults-production/home.php` |
| Tutor card | Widget present | Block via UI lib | `ng_ui_component` | Partial | Multiple paths — consolidate |
| CTA / buttons | Adapter CSS | Adapter CSS | Adapter CSS | `.ngt-btn` | Tokens |
| Forms | Soft (Companion force theme) | Soft | Soft | Companion shortcodes | NGC forms |
| Dashboard card | Soft | Soft | Soft | Companion shortcodes | REST |

| Page | Opens Editor | Containers Editable | Typography Native | Colors Native | Spacing Native | Custom CSS Dependency |
| ---- | ------------ | ------------------- | ----------------- | ------------- | -------------- | --------------------- |
| Home (kinetic) | Expected if Elementor used | UNVERIFIED this pass | Token bridge | Token bridge | Token bridge | MEDIUM (kinetic CSS) |
| Marketing pages | Soft | UNVERIFIED | Bridge | Bridge | Bridge | MEDIUM |
| Form pages | Soft / may reset | N/A | — | — | — | LOW (Companion) |

**Cross-builder headed parity:** still **UNVERIFIED** end-to-end.
