# DEAD CODE CANDIDATES

Date: 2026-03-26  
Scope: report only — no aggressive function-level deletion performed.

## High confidence (file-level already quarantined)

- Former `prototypes/*-body.php` loaders as primary page bodies
- Former `inc/defaults/*.php` one-line routers

## Medium confidence (still loaded for compatibility)

| Symbol | File | Notes |
|---|---|---|
| `bi_render_page_template` | `inc/page-wrapper.php` | Deprecated facade; archives/tests may still call |
| `bi_render_generic_page` | `inc/page-wrapper.php` | Deprecated; `page.php` is flat |
| `bi_use_prototype_blend` / `bi_render_blended_prototype` | `inc/prototype-blend.php` | Default OFF; opt-in only |
| `bi_include_prototype_body` | `inc/prototype-bodies.php` | Redirects to canonical body when present |
| `bi_sync_page_prototype_content` | `inc/prototype-blend.php` | Admin sync helper for old blend markers |

## Low confidence / do not delete without runtime proof

- `page-templates/*` Template Name assignables
- Elementor Theme Builder location handlers in `header.php` / `footer.php`
- Alternate header styles `transparent` / `minimal` (registry-driven, still used)
- UI library tutor-card / subject components (shared)

## Suggested follow-ups (separate change)

1. DB query for `_wp_page_template` assignments → quarantine unused `page-templates/*.php`
2. Remove blend option UI once product confirms
3. Collapse `inc/defaults-production/` into `template-parts/pages/` only (already mostly copied) then quarantine production duplicates
4. Unify repo-root vs nested theme packaging (RF-04)
