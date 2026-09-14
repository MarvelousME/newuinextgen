# WEBSITE RECOVERY REPORT

Date: 2026-09-11  
Baseline commit: `5afb189`  
**RECOVERY STATUS: RECOVERED WITH LIMITATIONS**

## What the previous refactor broke

Uncommitted page-flatten replaced `bi_render_page_template` → `inc/defaults` → prototype `*-body.php` with flat `get_header` / `template-parts/pages` / `get_footer`, moved defaults and prototype bodies into `NOT-USED/`, and turned prototype blend **off**. Pages could still return HTTP 200 with chrome but lost body DOM, selectors, and motion contracts.

## Why content disappeared

Body HTML lived in `prototypes/*-body.php` (blend default ON) and production defaults. Those files were removed from live paths and no longer included by the new flat loaders (or incomplete/migrated copies lacked dynamic/`the_content`/shortcode composition).

## Why visual styling changed

Missing body wrappers/classes (`.bi-*`, kinetic/hero/data-* contracts) meant page CSS/JS/GSAP hooked nothing. Flatten also risked skipping wrapper-gated asset behaviour.

## Files incorrectly moved

67 paths under `NOT-USED/` per `NOT-USED/NOT-USED-MANIFEST.md` (defaults routers + prototype bodies, root + package). Classification “VERIFIED_UNUSED” was premature.

## Files restored

### From git (`git restore --source=HEAD`)

- All root + `NextGenTutors-BeyondInfinity/` `page-*.php`, `front-page.php`, `home.php`, `page.php`, `admin-dashboard.php`, `functions.php`
- `inc/defaults/*` (24) + package mirror (24)
- `prototypes/*-body.php` (19) + package mirror (19)
- `inc/page-wrapper.php`, `prototype-blend.php`, `prototype-bodies.php`, `production-content.php`, `pages-registry.php`, `nav-menu.php`, `template-tags.php`, `page-builders.php`, `kinetic-home.php`, `kinetic-surface.php`, `admin.php` (+ BI package equivalents)

### From BACKUPS (confirmation / quarantine)

- Header/footer already matched HEAD (hash equal to `BACKUPS/20260326-page-flatten/`)
- Flatten-only orphans moved into `BACKUPS/20260326-page-flatten/orphan-flatten-artifacts/`:
  - `inc/canonical-page.php`
  - `inc/elementor-pages.php`
  - BI package copies of the same

### From NOT-USED

- Not required for restore (git had originals). Copies left for forensics; manifest stamped with **RECOVERY NOTICE**.

## Templates / body templates restored

- Chain restored: `page-*.php` → `bi_render_page_template()` → `inc/defaults/{slug}.php` → `bi_render_page_default(..., '*-body.php')`
- `prototypes/*-body.php` present again; blend default **ON**

## Assets / header / footer / navigation

- Header/footer: unchanged vs known-good (not damaged by flatten on disk)
- Nav: restored HEAD `inc/nav-menu.php` (**NextGen Primary Grouped**)
- CSS/JS/kinetic/3D: prior enqueue modules restored with functions/inc files — no global “load all” hack

## Pages recovered (file-level)

All primary marketing, auth, legal, and dashboard entry templates restored to pre-flatten content. PHP lint OK on critical samples (`front-page.php`, `page-about.php`, wrapper, defaults, bodies, `functions.php`, header, footer).

## Remaining defects / limitations

1. **No live visual regression** — Docker `localhost:8890` timed out; staging credentials previously invalid/locked.
2. **`template-parts/pages/`** still on disk (unwired) — safe evidence; must not be bootstrapped without equivalence proof.
3. **`NOT-USED/`** still contains duplicate copies — forensic only; do not deploy from there.
4. Browser console / GSAP / mobile screenshot matrix not re-run in this session.
5. Menu display name remains HEAD’s **NextGen Primary Grouped** (pre-flatten). Renaming to “NextGen Primary” is deferred until visual verify (avoid nav churn during recovery).

## Visual / functional regression status

| Check | Status |
|---|---|
| File chain restored | PASS |
| Defaults + bodies present | PASS |
| Blend default ON | PASS |
| Header/footer intact | PASS |
| PHP lint samples | PASS |
| Desktop visual | **NOT RUN** |
| Mobile visual | **NOT RUN** |
| Staging | **BLOCKED** (auth) |

## Final readiness verdict

**RECOVERED WITH LIMITATIONS** — working-tree theme rendering matches last known-good commit; runtime visual proof still required before any new simplification.

---

## Final response checklist (summary)

1. **ROOT CAUSE:** Premature flatten + NOT-USED quarantine of live defaults/bodies; blend off.  
2. **DAMAGE:** Lost body content/DOM contracts; flat loaders; orphaned flatten helpers.  
3. **FROM NOT-USED:** Not copied back (git restore preferred); forensic copies remain.  
4. **FROM GIT/BACKUPS:** Full theme render path restored from `5afb189`; orphans quarantined to BACKUPS.  
5. **PAGE CONTENT:** Routers + `*-body.php` restored.  
6. **HEADER:** Intact (matches backup).  
7. **NAV:** Restored HEAD primary menu resolution.  
8. **FOOTER:** Intact.  
9–11. **CSS/JS/MOTION:** Prior modules restored via `functions.php`/inc; visual confirm pending.  
12–13. **WP/BUILDER:** Wrapper dual-mode restored (`the_content` / Elementor paths).  
14–15. **PAGE/MOBILE RESULTS:** File PASS; browser NOT RUN.  
16. **REMAINING:** Visual verify, staging deploy, defer simplification.  
17–18. **MODIFIED/RESTORED:** See `RECOVERY_CHANGE_ANALYSIS.md`.  
19. **TESTS:** PHP lint samples PASS; Docker unreachable.  
20. **VERDICT:** RECOVERED WITH LIMITATIONS.
