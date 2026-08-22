# 10 — File Change Register

**Implementation window:** 2026-08-09  
**Branch:** working tree (uncommitted at audit time)

## Modified files (reference migration)

| File | Change |
|------|--------|
| `NextGenTutors-BeyondInfinity/inc/nav-menu.php` | Full rewrite: `bi_nav_menu_structure()`, schema `2026-08-09-reference-nav-v1`, Sign In CTA, menu sync |
| `NextGenTutors-BeyondInfinity/templates/header/default.php` | Wire `bi_render_header_sign_in_cta()`; remove Get Started |
| `NextGenTutors-BeyondInfinity/templates/header/transparent.php` | Same as default header |
| `NextGenTutors-BeyondInfinity/templates/footer/default.php` | Reference column footer (Explore, Company, Get In Touch, legal) |
| `NextGenTutors-BeyondInfinity/inc/brand-content.php` | Brand narrative merges for about/home/become |
| `NextGenTutors-BeyondInfinity/inc/defaults-production/home.php` | Home content merge |
| `NextGenTutors-BeyondInfinity/inc/defaults-production/about.php` | About content merge |
| `NextGenTutors-BeyondInfinity/inc/defaults-production/become-a-tutor.php` | Become-a-tutor merge |
| `NextGenTutors-BeyondInfinity/assets/ngt/js/chrome.js` | Get Started removed; Sign In only |
| `NextGenTutors-BeyondInfinity/style.css` | Footer/nav styling adjustments |
| `assets/ngt/js/chrome.js` | Shared root copy synced (Get Started removed) |

## Related changes (same sprint, not chrome-specific)

| File | Change |
|------|--------|
| `NextGenTutors-BeyondInfinity/assets/js/dashboard-rest.js` | Dashboard REST client updates |
| `NextGenTutors-Companion/*` | Booking commerce, sessions, REST (domain layer) |

## New audit artifacts

| File | Purpose |
|------|---------|
| `.agent-audit/reference-theme-migration/evidence/source-file-list.txt` | 334-path source inventory |
| `.agent-audit/reference-theme-migration/00–12-*.md` | This audit pack |

## Unchanged but authoritative

| File | Role |
|------|------|
| `NextGenTutors-BeyondInfinity/content/page-map.json` | 24-page launch map |
| `NextGenTutors-BeyondInfinity/inc/pages-registry.php` | Page metadata + shortcodes |
| `NextGenTutors-BeyondInfinity/assets/js/nav-menu.js` | Dropdown interaction |

## Files explicitly not changed

| File | Reason |
|------|--------|
| Reference `nextgen-tutors-theme/` on Desktop | Source archive; read-only |
| `NextGenTutors-BeyondInfinity/page-*.php` handlers | Already mapped; content via defaults |
| Companion booking/session classes | Separate domain migration track |

## Rollback notes

- Restore prior `inc/nav-menu.php` and clear option `bi_nav_public_schema`
- Re-run `bi_sync_launch_nav(true)` or reassign menus in WP admin
- Revert header/footer templates to pre-migration copies from git
