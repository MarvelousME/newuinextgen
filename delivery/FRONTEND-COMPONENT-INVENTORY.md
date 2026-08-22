# Frontend Component Inventory (excerpt)

| Component | Canonical class | Variants | Data source |
|-----------|-----------------|----------|-------------|
| Button | `.ngt-btn` | `--primary`, `--secondary`, `--outline`, `--white`, `--sm`, `--lg` | Theme |
| Card | `.ngt-card` | pricing, tutor, stat | Theme + CMS |
| Tutor card | `.ngt-tutor-card`, `.tutor-card` | compact, featured | `bi_get_live_tutors()` / CPT |
| Form field | `.ngt-form-group`, `.ngc-field` | valid/invalid | Companion forms |
| Section | `.ngt-section`, `.ngi-section` | alt, narrow | Templates |
| Container | `.ngt-container`, `.ng-container` | narrow, wide | Layout tokens |
| Badge | `.ngt-badge`, `.tutor-badge` | vetted, home | Tutor meta |
| Nav | `.ngt-nav__*` | transparent, minimal | `bi_nav_menu_structure()` |
| Hero | `.bi-hero`, `.ngi-hero` | marketing, cinematic | Page defaults / CMS |
| KPI stat | `.bi-stat-card`, `.bi-dash-kpi` | — | REST dashboards |

**Elementor:** widgets in `inc/ui-library/` (`NG_UI_Elementor_Widget`, Tutor Card)  
**Gutenberg:** `ngt-ui/component` block (Companion UI library)  
**WPBakery:** `[ng_ui_component]` shortcode map

Full migration of all variants to `.ngt-card--*` naming is **PARTIAL**.
