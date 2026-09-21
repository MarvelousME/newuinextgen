# RECOVERY CHANGE ANALYSIS

Date: 2026-09-11  
Known-good baseline: git commit `5afb189` (`Add Economic Control Center as a native Control Center module.`)  
Damaging change: uncommitted **page-flatten** refactor (documented in `CODEBASE_REFACTOR_REPORT.md`, dated 2026-03-26 in that file; executed in working tree ~2026-09-10)

## Root cause (evidence-based)

The flatten replaced the working chain:

```text
page-*.php / front-page.php
  → bi_render_page_template()
    → get_header() / get_footer()
    → bi_render_theme_default()
      → inc/defaults/{slug}.php
        → bi_render_page_default()
          → prototypes/*-body.php (blend default ON)
          → OR inc/defaults-production/{slug}.php
```

with a flat contract:

```text
page-*.php
  → get_header()
  → bi_canonical_render_body() → template-parts/pages/{slug}.php
  → get_footer()
```

and moved `inc/defaults/*` + `prototypes/*-body.php` into `NOT-USED/`, flipping prototype blend default **OFF**. That removed the live body source and selectors CSS/JS/GSAP expected.

## Primary source of truth used for recovery

1. **git HEAD `5afb189`** — selective `git restore` of all flatten-touched theme paths  
2. **`BACKUPS/20260326-page-flatten/`** — pre-flatten snapshot (header/footer hashes match live; used for confirmation)  
3. **`NOT-USED/`** — forensic copies only after restore (live paths restored from git, not copied from NOT-USED)

## File inventory (flatten impact → recovery)

### Page entry templates

| Original path | Current path | Change | Previous responsibility | Current (after recovery) | Page output? | CSS/JS? | WP dynamic? | Recovery action |
|---|---|---|---|---|---|---|---|---|
| `front-page.php` | `front-page.php` | modified → restored | `bi_render_page_template(bi_home_default_path())` | same as HEAD | yes | via wrap/assets | Elementor short-circuit | `git restore` |
| `home.php` | `home.php` | modified → restored | home blog/fallback via wrapper | same as HEAD | yes | yes | yes | `git restore` |
| `page.php` | `page.php` | modified → restored | generic + wrapper | same as HEAD | yes | yes | `the_content` path | `git restore` |
| `admin-dashboard.php` | `admin-dashboard.php` | modified → restored | dashboard via wrapper | same as HEAD | yes | app | shortcodes | `git restore` |
| `page-about.php` … `page-wordpress-setup.php` (24) | same | modified → restored | `bi_render_page_template(…/inc/defaults/{slug}.php)` | same as HEAD | yes | page assets | Template Name | `git restore` |
| `NextGenTutors-BeyondInfinity/page-*.php` (+ front/home/page/admin) | same | modified → restored | packaged theme mirror | same as HEAD | yes (Docker mount) | yes | yes | `git restore` |

### Defaults routers

| Original path | Change | Responsibility | Recovery |
|---|---|---|---|
| `inc/defaults/*.php` (24) | deleted → restored | `bi_render_page_default(slug, *-body.php)` | `git restore` |
| `NextGenTutors-BeyondInfinity/inc/defaults/*.php` (24) | deleted → restored | package mirror | `git restore` |
| `NOT-USED/inc/defaults/*` | copies remain | forensic only | leave; not live |

### Prototype bodies

| Original path | Change | Responsibility | Recovery |
|---|---|---|---|
| `prototypes/*-body.php` (19) | deleted → restored | marketing/kinetic DOM + selectors | `git restore` |
| `NextGenTutors-BeyondInfinity/prototypes/*-body.php` (19) | deleted → restored | package mirror | `git restore` |
| `NOT-USED/prototypes/*` | copies remain | forensic only | leave |

### Rendering / blend / registry

| Original path | Change | Responsibility | Recovery |
|---|---|---|---|
| `inc/page-wrapper.php` | modified → restored | `bi_render_page_template` full builder/theme dual mode | `git restore` |
| `inc/prototype-blend.php` | modified → restored | blend default **ON** (`bi_use_prototype_blend` → true) | `git restore` |
| `inc/prototype-bodies.php` | modified → restored | body resolution helpers | `git restore` |
| `inc/production-content.php` | modified → restored | production default renderer | `git restore` |
| `inc/pages-registry.php` | modified → restored | page config / chrome | `git restore` |
| `inc/nav-menu.php` | modified → restored | primary nav (**NextGen Primary Grouped** at HEAD) | `git restore` |
| `inc/template-tags.php`, `inc/page-builders.php`, `inc/kinetic-home.php`, `inc/kinetic-surface.php`, `inc/admin.php` | modified → restored | tags/builders/kinetic | `git restore` |
| `functions.php` (+ BI package) | modified → restored | bootstrap without flatten loaders | `git restore` |

### Header / footer

| Path | Change | Notes | Recovery |
|---|---|---|---|
| `header.php` | unchanged vs HEAD | hash matches `BACKUPS/20260326-page-flatten/header.php` | none needed |
| `footer.php` | unchanged vs HEAD | hash matches backup | none needed |
| `templates/header/*`, `templates/footer/*` | present in backup; live unchanged | style partials | keep |

### Flatten-only artifacts (not in HEAD)

| Path | Status | Action |
|---|---|---|
| `inc/canonical-page.php` | untracked flatten helper | moved → `BACKUPS/20260326-page-flatten/orphan-flatten-artifacts/` |
| `inc/elementor-pages.php` | untracked flatten helper | quarantined to BACKUPS orphans |
| `NextGenTutors-BeyondInfinity/inc/canonical-page.php` | untracked | quarantined |
| `NextGenTutors-BeyondInfinity/inc/elementor-pages.php` | untracked | quarantined |
| `template-parts/pages/*.php` (24) | flatten body copies | **kept** with `RECOVERY-NOTE.txt`; **not wired** into bootstrap |
| `NOT-USED/` tree | quarantine copies | retained for forensics; live paths restored |
| `BACKUPS/20260326-page-flatten/` | pre-change snapshot | retained |
| `CODEBASE_REFACTOR_REPORT.md`, `PAGE_ARCHITECTURE_MAP.md`, etc. | flatten docs | retained as historical; do not follow for live architecture |

## What was NOT reverted

- Companion test harness fixes (`integrate-test.php`, `tests/run.php`)
- Release packaging / BeyondMeasure zip wiring / e2e deploy scripts
- Unrelated architecture/gate report churn outside theme render chain

## Restored rendering chain (verified by file content)

```text
front-page.php → bi_render_page_template(bi_home_default_path())
page-about.php → bi_render_page_template(…/inc/defaults/about.php)
inc/defaults/about.php → bi_render_page_default('about', 'about-body.php')
inc/defaults/home.php → bi_render_page_default('home', 'index-body.php')
bi_use_prototype_blend() default → true
prototypes/about-body.php, prototypes/index-body.php → present
```

## Visual / runtime verification status

Local Docker `http://localhost:8890` timed out during recovery (engine/site not reachable). Staging deploy was previously blocked on credentials. **File-level recovery is complete; browser visual regression remains pending.**
