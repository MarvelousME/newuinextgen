# Page Builder Ownership Matrix

**Audit date:** 2026-08-14 · Runtime `localhost:8890`

Decision tree (theme): `bi_render_page_template()` in `inc/page-wrapper.php`

1. Builder edit mode → Elementor/WPBakery shell  
2. Elementor canvas / header-footer template → `the_content()` only  
3. Else if `bi_should_show_theme_fallback()` → PHP theme default (`bi-theme-main`)  
4. Else if Elementor Theme Builder location → Theme Builder body  
5. Else → `bi_render_builder_content()` (`bi-builder-content`)

| Page | URL | Rendering Authority | Elementor Editable | Gutenberg Editable | WPBakery Editable | Compatibility Risk |
| ---- | --- | ------------------- | ------------------ | ------------------ | ----------------- | ------------------ |
| Home | `/` | **PHP kinetic** (`bi-render:theme-fallback` verified 2026-08-14) | Soft — only if document has real widgets; kinetic `template_include` priority 9999 reclaim | Soft | Soft | HIGH if Elementor stubs reclaim body |
| Find a Tutor | `/find-a-tutor/` | Hybrid PHP + `[ngc_tutor_marketplace]` / forms | Soft (Companion may force theme) | Soft | Soft | Dual marketplace stacks |
| Pricing | `/pricing/` | PHP blend/production | Yes if built | Yes | Yes | Medium |
| About | `/about/` | PHP blend/production | Yes if built | Yes | Yes | Low |
| Contact | `/contact/` | Hybrid + `[ngc_contact_support_form]` | Soft (forms registry) | Soft | Soft | Medium |
| Login | `/login/` | Shortcode/hybrid | Soft | Soft | Soft | Medium |
| Parent dashboard | `/parent-dashboard/` | `[ngc_parent_dashboard]` | Soft | Soft | Soft | Companion required |
| Subjects | `/subjects/` | PHP intended | Yes if built | Yes | Yes | **Missing defaults-production files** |
| Checkout | Woo / parent-checkout | Theme-forced / Woo | No (forced) | Soft | Soft | Release-critical |

**HTTP smoke (2026-08-14):** `/`, `/find-a-tutor/`, `/pricing/`, `/about/`, `/contact/`, `/login/` all returned `main=1`, `sticky=0`, `proto=0`.
