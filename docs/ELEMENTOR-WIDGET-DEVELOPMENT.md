# NextGen Elementor widget development

How to add or change widgets in the NextGen Tutors Elementor category without forking the motion stack or Companion.

## Boundaries

| Layer | Owns | Do not |
|---|---|---|
| Elementor widget | Controls, semantic markup, `data-ngt-*` | Query `tutors` CPT, invent prices, load GSAP |
| BeyondInfinity | Tokens, theme helpers (`bi_render_*`) | Duplicate marketplace filters |
| Companion | Providers, shortcodes, bookings | Presentation CSS |
| 3D Scroll Manager | GSAP/ScrollTrigger/Three runtime | A second `gsap.registerPlugin` |

## File map

- `inc/elementor-design-system/bootstrap.php` — boot
- `class-bi-el-inventory.php` — widget catalog
- `class-bi-el-widget-base.php` — Content / Style / NextGen Motion
- `class-bi-el-widgets.php` — named `\Elementor\Widget_Base` classes
- `class-bi-el-renders.php` — HTML
- `class-bi-el-motion.php` — compile controls → NGT3D rules
- `class-bi-el-data.php` — Companion accessors
- `class-bi-el-templates.php` — library JSON
- `assets/css/elementor-design-system*.css`
- `assets/js/elementor-design-system-*.js`

Existing NG UI widgets (`ng_ui_hero`, `ng_ui_tutor_card`, …) stay registered.

## Add a widget

1. Add an inventory row in `BI_EL_Inventory::all()`.
2. Add a class in `class-bi-el-widgets.php` extending `BI_EL_Widget_Base` with `definition_id()`.
3. Add the class name to `bi_el_ds_widget_classes()`.
4. Add `BI_EL_Renders::render_{id}()` that wraps theme/Companion APIs.
5. Extend the test inventory list in `tests/elementor-design-system/run.php`.

Widget names are `bi_el_` + id with hyphens converted to underscores.

## Motion compile contract

`BI_EL_Motion::compile_rule()` must return a repository-shaped rule:

- `target_selector` → `.elementor-element-{id} .bi-el`
- `animation_names` → NGT3D registry ids (`depth-hero`, `tilt-3d`, …)
- `animation_options` → keys allowed by `NGT3D_Validator`
- `desktop_mode` / `tablet_mode` / `mobile_mode` → `full|reduced|disabled`

Inject via `ngt3d_page_rules`. Never call `gsap.to` from widget JS except through `window.NGT3D`.

## Data contract

Use `BI_EL_Data::*`. If Companion is inactive, return `[]` and `BI_EL_Data::empty_state()` (editor-only hint). No placeholder people, ratings, or prices on the frontend.

## Markup

Root: `.bi-el.bi-el--{id}.bi-el-preset--{preset}` plus `data-ngt-el`, `data-bi-el-preset`, `data-bi-el-mode` (`editor|preview|frontend`).

## Tests

```powershell
php tests/elementor-design-system/run.php
```

Covers registration count, frontend render, missing Companion, missing Three.js fallback, reduced-motion attributes, Elementor document compile, editor/preview/frontend markup.
