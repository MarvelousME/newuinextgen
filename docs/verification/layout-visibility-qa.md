# Layout Visibility QA

## Page Layout Mode Declarations

| Page | Declared Mode | Builder Support | Sidebar Mode | Status |
|---|---|---|---|---|
| Home | boxed/full (resolver + override) | Elementor/WPBakery/Gutenberg | no-sidebar default | VERIFIED |
| Find a Tutor | boxed/full (resolver + override) | Elementor/WPBakery/Gutenberg/shortcode | no-sidebar default | VERIFIED |
| Become a Tutor | boxed/full (resolver + override) | Elementor/WPBakery/Gutenberg/shortcode | no-sidebar default | VERIFIED |
| Tutor Marketplace | boxed/full/wide (resolver + override) | Elementor/WPBakery/Gutenberg | no-sidebar default | VERIFIED |
| Tutor Profile | configurable (`tutor_profile_layout_mode`) | theme template + shortcode | no-sidebar default | VERIFIED |
| Tutor Calendar | configurable (`calendar_layout_mode`) | shortcode/theme partial | no-sidebar default | VERIFIED |
| Parent Dashboard | forced full by default + override | shortcode + template | no-sidebar default | VERIFIED |
| Student Dashboard | forced full by default + override | shortcode + template | no-sidebar default | VERIFIED |
| Tutor Dashboard | forced full by default + override | shortcode + template | no-sidebar default | VERIFIED |
| Admin Dashboard frontend | forced full by default + override | shortcode + template | no-sidebar default | VERIFIED |
| Login/Register | boxed/full (resolver + form wrappers) | shortcode/theme templates | no-sidebar default | VERIFIED |
| Pricing/Subjects/About/Contact | boxed/full/wide | Elementor/WPBakery/Gutenberg | configurable | VERIFIED |
| Checkout/Payment/Invoice/Booking | full or boxed via resolver | Woo + shortcode contexts | configurable | PARTIAL |
| Reviews/Ratings/Support | boxed/full/wide | shortcode/theme templates | configurable | VERIFIED |
| Verification/Analytics/CRM/Workflow screens | dashboard full default | shortcode/admin frontend | no-sidebar default | VERIFIED |

## Visual QA Checklist

| Page | Layout Mode | Desktop | Tablet | Mobile | Controls Visible | Overflow | Status |
|---|---|---|---|---|---|---|---|
| Home | boxed/full | Pending runtime | Pending runtime | Pending runtime | Code-guarded | Code-guarded | PARTIAL |
| Find a Tutor | boxed/full | Pending runtime | Pending runtime | Pending runtime | Code-guarded | Code-guarded | PARTIAL |
| Become a Tutor | boxed/full | Pending runtime | Pending runtime | Pending runtime | Code-guarded | Code-guarded | PARTIAL |
| Tutor Profile + Calendar | boxed/full | Pending runtime | Pending runtime | Pending runtime | Code-guarded | Code-guarded | PARTIAL |
| Parent/Student/Tutor/Admin dashboards | full default | Pending runtime | Pending runtime | Pending runtime | Code-guarded | Code-guarded | PARTIAL |
| Login/Register/Support forms | boxed/full | Pending runtime | Pending runtime | Pending runtime | Code-guarded | Code-guarded | PARTIAL |
| Analytics/Verification/Workflow frontend screens | full default | Pending runtime | Pending runtime | Pending runtime | Code-guarded | Code-guarded | PARTIAL |

## Automated Debug Checks

Admin-only debug mode (`Appearance > Layout Settings > Enable visual QA debug outline`) runs:

- horizontal overflow detection (`data-ng-overflow="1"`)
- hidden/out-of-viewport focusable controls detection (`data-ng-hidden-control="1"`)
- live panel with layout mode, template, builder, viewport size

## Responsive Matrix

| Breakpoint | Horizontal Overflow | Controls Visibility | Menu Usability | Forms | Calendar | Dashboard | Status |
|---|---|---|---|---|---|---|---|
| 320px | Guarded by CSS/JS checks | Guarded | Guarded | Guarded | Guarded | Guarded | PARTIAL |
| 375px | Guarded by CSS/JS checks | Guarded | Guarded | Guarded | Guarded | Guarded | PARTIAL |
| 414px | Guarded by CSS/JS checks | Guarded | Guarded | Guarded | Guarded | Guarded | PARTIAL |
| 768px | Guarded by CSS/JS checks | Guarded | Guarded | Guarded | Guarded | Guarded | PARTIAL |
| 1024px | Guarded by CSS/JS checks | Guarded | Guarded | Guarded | Guarded | Guarded | PARTIAL |
| 1280px | Guarded by CSS/JS checks | Guarded | Guarded | Guarded | Guarded | Guarded | PARTIAL |
| 1440px | Guarded by CSS/JS checks | Guarded | Guarded | Guarded | Guarded | Guarded | PARTIAL |
| 1920px | Guarded by CSS/JS checks | Guarded | Guarded | Guarded | Guarded | Guarded | PARTIAL |

## Final Verification Table

| Requirement | Result | Evidence |
|---|---|---|
| Layout mode manager | VERIFIED | `beyondinfinity/inc/layout-manager.php` |
| Page layout resolver and body classes | VERIFIED | resolver + `body_class` filter |
| Container utility CSS | VERIFIED | `beyondinfinity/assets/css/layout-system.css` |
| Responsive fix CSS | VERIFIED | `layout-system.css` + `page-builders.css` updates |
| Page builder compatibility fixes | VERIFIED | `page-builders.css` updates + resolver builder classes |
| Dashboard layout wrappers | VERIFIED | `page-wrapper.php`, shortcodes dashboard wrapper |
| Calendar layout wrappers | VERIFIED | shortcode calendar wrapper + CSS |
| Form layout wrappers | VERIFIED | shortcode forms/login wrappers |
| Table wrapper utility | VERIFIED | `.ng-table-wrap` utility |
| Modal/dropdown z-index fixes | VERIFIED | CSS z-index stack in layout-system |
| Template wrappers | VERIFIED | `page-wrapper.php`, `single-tutors.php` |
| Shortcode wrappers | VERIFIED | `class-ngc-shortcodes.php` |
| Admin layout settings | VERIFIED | layout settings page + per-page meta override |
| Visual QA debug tooling | VERIFIED | `layout-debug.js` + admin-only toggle |

