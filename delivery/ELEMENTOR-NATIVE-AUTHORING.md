# Elementor-Native Authoring

**Date:** 2026-08-14  
**Goal:** Every public page editable in Elementor Pro; domain logic stays in Companion shortcodes / shared PHP.

## What changed

| Change | Where |
| ------ | ----- |
| Stop deleting `_elementor_*` / stop blanket `force_theme_default` on form pages | `NextGenTutors-Companion/.../class-ngc-page-forms-registry.php` |
| Elementor documents win over `force_theme_default` (except Woo/thank-you) | `inc/page-wrapper.php` |
| Kinetic home yields when Elementor-built; `home_layout=elementor` option | `inc/config/home-sections.php`, `lists.php`, `inc/elementor-native.php` |
| NextGen Shortcode + Theme Page Body Elementor widgets (style controls) | `inc/elementor-native-widgets.php` |
| Seed / enable tool + REST | Tools → **Elementor Native Pages**; `POST /wp-json/bi/v1/elementor-native/enable` |
| NGC visual builder does not replace Elementor pages | `ngc_builder_replace_content` filter |

## How to edit a page

1. WP Admin → Pages → open page → **Edit with Elementor**
2. Use panel category **NextGen Tutors**:
   - **NextGen Shortcode** — forms, dashboards, marketplace (spacing/colors in Style tab)
   - **Theme Page Body** — wraps existing PHP defaults while you rebuild natively
   - Existing **NG UI** hero / tutor card / pricing card widgets
3. Use Elementor containers, global colors/typography, responsive controls as usual
4. Theme header/footer remain unless Theme Builder header/footer templates are assigned

## Locked (intentionally PHP)

`parent-checkout`, `thank-you`, Woo `cart` / `checkout` / order-received

## Runtime check (2026-08-14)

- REST enable reported all registry pages (except commerce locks) as **already_elementor**
- Homepage HTML shows Elementor containers (`e-con` / `elementor-element`); prototype-only blank home is gone
- Seed tool available for re-seed with **Force** if documents must be rebuilt from shortcodes

## Honest limitation

Pages seeded with **Theme Page Body** / shortcode widgets are Elementor-editable for layout, spacing, visibility, and wrapping chrome. Pixel-level rebuild of every kinetic HTML node into pure Elementor widgets is incremental: replace Theme Page Body chunks with native Elementor widgets + NextGen Shortcode widgets over time. Business logic must not be reimplemented inside Elementor.
