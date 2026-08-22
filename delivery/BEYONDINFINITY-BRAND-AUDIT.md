# BeyondInfinity Brand Audit

**Date:** 2026-08-10  
**Scope:** NextGenTutors-BeyondInfinity theme + repo overlays (`assets/`, `inc/`)

## Executive summary

BeyondInfinity already had a **partial** design-token architecture (`tokens/base.css`, `tokens/unified.css`, skins). Multiple parallel systems competed for authority. This pass establishes **brand-semantic tokens**, **theme.json**, **builder adapter layers**, and a **Brand Style Kit admin preview** — but full cross-builder parity and page-family migration remain **PARTIAL**.

## Current system (pre-remediation)

| Layer | Path | Role |
|-------|------|------|
| Structural tokens | `assets/css/tokens/base.css` | Spacing, radius, motion, layout |
| Skin semantics | `assets/css/skins/beyond-infinity.css` | Brand colours on `html[data-bi-skin]` |
| Unified aliases | `assets/css/tokens/unified.css` | Bridges `--ng-*`, `--navy`, `--ngt-color-*` |
| Legacy duplicate | `style.css :root` | **Removed** — duplicated entire token set |
| UI library | `ng-ui-tokens.css` | Hardcoded hex (still needs alias-only refactor) |
| Kinetic | `kinetic-tokens.css` | `--ngi-*` homepage palette |
| Premium overlay | `nextgen-beyond-infinity-ui.css` | `--ngbi-*` + `!important` on buttons |
| Admin schemes | `inc/config/color-schemes.php` | **Aligned** to Beyond Infinity skin default |

## Duplicate / conflicting systems

1. **Six token namespaces** — `--ngt-*`, `--ng-*`, `--ngi-*`, `--ngbi-*`, `--navy/--lime`, Elementor globals (unsynced)
2. **Three button conventions** — `.ngt-btn`, `.ng-btn`, builder-native buttons
3. **style.css owned tokens** — caused cascade fights with skin layers (fixed)
4. **Customizer default scheme ≠ skin** — fixed to `#07172f / #28c7f7 / #ffb703`
5. **No theme.json** — fixed (added `theme.json` v3)

## Builder coupling

| Builder | Integration | Status |
|---------|-------------|--------|
| Elementor Pro | `integrations/elementor.css` + kit sync API | **NEW** — manual sync button in admin |
| Gutenberg | `theme.json` + `integrations/gutenberg.css` + editor styles | **NEW** |
| WPBakery | `integrations/wpbakery.css` | **NEW** — progressive |
| Native PHP | `style.css`, `components.css`, templates | Existing — consumes `--ngt-*` |

## Accessibility problems (known)

- Focus rings repaired in `unified.css`; extended in `accessibility.css`
- Reduced motion global guard added
- Validation colour-only risk reduced for `.ngc-field.is-invalid`
- **UNVERIFIED:** full WCAG 2.2 AA audit across all page families

## Responsive problems (known)

- Builder container widths normalized via integration CSS
- Kinetic homepage uses separate `--ngi-*` tokens — scoped to `.ngi-home`
- **UNVERIFIED:** overflow audit on dashboards / WooCommerce checkout

## Performance issues

- Unified tokens inlined at priority 60 (good)
- Multiple legacy token files still enqueued on some routes
- `nextgen-beyond-infinity-ui.css` still uses broad selectors — demotion recommended

## Recommended remediation (remaining)

1. Refactor `ng-ui-tokens.css` to alias-only (no hex)
2. Scope or demote `nextgen-beyond-infinity-ui.css` `!important` rules
3. Add MasterStudy + Amelia adapter CSS in `integrations/`
4. Run visual regression + headed E2E cross-builder parity suite
5. Migrate page families incrementally (inventory → classify → tokenize)
