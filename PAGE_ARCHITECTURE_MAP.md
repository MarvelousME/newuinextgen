# PAGE ARCHITECTURE MAP

Date: 2026-03-26  
Theme: NextGenTutors-BeyondInfinity (repo root + nested package)

## Global chrome

| Concern | Canonical source |
|---|---|
| Header | `header.php` → Elementor location **or** `templates/header/{transparent\|default\|minimal}.php` |
| Navigation | `bi_render_primary_nav_menu()` → theme_location `primary` → menu **NextGen Primary** (legacy name `NextGen Primary Grouped` renamed/fallback) |
| Footer | `footer.php` → Elementor location **or** `templates/footer/{default\|minimal}.php` |

Exceptions (documented): dashboard/admin pages use `minimal` header/footer via registry `config_defaults`. Elementor canvas templates skip theme chrome.

---

## Primary pages (before → after)

### HOME
**BEFORE:** `front-page.php` → `bi_render_page_template` → `inc/defaults/home.php` → `bi_render_page_default` → production **or** prototype blend  
**AFTER:** `front-page.php` → `get_header()` → `template-parts/pages/home.php` (kinetic production body) → `get_footer()`  
**Ownership:** CODE (kinetic + Companion CMS markers)  
**Nav:** NextGen Primary  
**Body migrated:** from `inc/defaults-production/home.php` (not index-body — kinetic preserved)

### ABOUT
**BEFORE:** `page-about.php` → wrapper → defaults router → prototype blend (`about-body.php`) or production  
**AFTER:** `page-about.php` → `get_header()` → `template-parts/pages/about.php` → `get_footer()`  
**Ownership:** CODE (migrated from `prototypes/about-body.php`)  
**Nav:** NextGen Primary

### FIND A TUTOR
**BEFORE:** nested render + blend/production  
**AFTER:** `page-find-tutor.php` → `template-parts/pages/find-a-tutor.php` (production live marketplace + forms)  
**Ownership:** CODE + Companion shortcodes  
**Note:** Prototype marketing shell superseded by production for live KPIs/marketplace

### BECOME A TUTOR / BLOG / CONTACT / SUPPORT / GUARANTEE / PRICING / TUTOR VETTING / SAFETY GUIDE / PRIVACY / TERMS / ONBOARDING / WORDPRESS SETUP
**AFTER:** matching `page-*.php` → `template-parts/pages/{slug}.php`  
**Body source:** migrated from matching `*-body.php` (authoritative under former default-on blend)  
**Shortcodes appended** where registry lists them (contact, support, become-a-tutor, onboarding)

### LOGIN / REGISTER / PARENT CHECKOUT / THANK YOU / CHILD SAFETY
**AFTER:** matching `page-*.php` → production-sourced `template-parts/pages/{slug}.php`  
**Ownership:** CODE + Companion forms

### PARENT / STUDENT / TUTOR / ADMIN DASHBOARDS
**AFTER:** matching templates → production shortcode bodies  
**Chrome:** minimal header/footer (exception)  
**Nav:** minimal chrome (documented exception — not marketing NextGen Primary emphasis)

### GENERIC FALLBACK
**AFTER:** `page.php` → `get_header()` → canonical body if `template-parts/pages/{slug}.php` exists, else `the_content()` → `get_footer()`

---

## Validation matrix

| PAGE | SLUG | CANONICAL FILE | HEADER | NAV | FOOTER | BODY MIGRATED | ASSETS | STATUS |
|---|---|---|---|---|---|---|---|---|
| Home | home | front-page.php | transparent | NextGen Primary | default | yes (production kinetic) | kinetic/3D retained | READY |
| About | about | page-about.php | default* | NextGen Primary | default | yes (prototype) | theme | READY |
| Find a Tutor | find-a-tutor | page-find-tutor.php | default* | NextGen Primary | default | yes (production) | marketplace | READY |
| Become a Tutor | become-a-tutor | page-become-a-tutor.php | default* | NextGen Primary | default | yes (prototype+SC) | theme | READY |
| Pricing | pricing | page-pricing.php | default* | NextGen Primary | default | yes (prototype) | theme | READY |
| Contact | contact | page-contact.php | default* | NextGen Primary | default | yes (prototype+SC) | forms | READY |
| Support | support | page-support.php | default* | NextGen Primary | default | yes (prototype+SC) | forms | READY |
| Blog | blog | page-blog.php | default* | NextGen Primary | default | yes (prototype) | theme | READY |
| Guarantee | guarantee | page-guarantee.php | default* | NextGen Primary | default | yes (prototype) | theme | READY |
| Tutor Vetting | tutor-vetting | page-tutor-vetting.php | default* | NextGen Primary | default | yes (prototype) | theme | READY |
| Safety Guide | safety-guide | page-safety-guide.php | default* | NextGen Primary | default | yes (prototype) | theme | READY |
| Privacy | privacy-policy | page-privacy-policy.php | default* | NextGen Primary | default | yes (prototype) | theme | READY |
| Terms | terms | page-terms.php | default* | NextGen Primary | default | yes (prototype) | theme | READY |
| Child Safety | child-safety | page-child-safety.php | default* | NextGen Primary | default | yes (production) | theme | READY |
| Login | login | page-login.php | default* | NextGen Primary | default | yes (production) | auth | READY |
| Register | register | page-register.php | default* | NextGen Primary | default | yes (production) | auth | READY |
| Dashboards | *-dashboard | page-*-dashboard.php | minimal | exception | minimal | yes (production SC) | app | READY |

\* Header style still resolved by `bi_get_header_style()` (registry/meta/options); markup always from canonical `header.php`.
