# Deliverables 11, 12, 17, 19, 20 — Implementation Plan, File Map, Consolidation, Before/After, Roadmap

---

## 1. Strategy

**Token-first, alias-bridge, zero-regression.** The four legacy CSS namespaces are not rewritten — they are *re-pointed* at one canonical token layer, so every existing selector keeps working while inheriting the unified brand. Component work then proceeds page-by-page with verification gates. No business logic (PHP domain classes, REST, workflows) is touched by presentation phases.

## 2. Phase 1 — Foundation (IMPLEMENTED)

| Change | Files |
|---|---|
| Unified token layer + alias bridge + dark-scheme hook | `NextGenTutors-BeyondInfinity/assets/css/tokens/unified.css` (new), enqueued in `inc/theme-switcher.php` |
| Focus-visible repair (WCAG 2.4.7) | `assets/ngt/css/components.css`, `pages.css`, `floating.css`, `content.css`, `admin.css` |
| Magic UI brand alignment (purple → brand fallbacks) | `ui-library/assets/css/kinds/kind-text.css`, `kind-card.css`, `kind-list.css`, `kind-progress.css`, `kind-interactive.css`, `assets/css/components/magic-card.css`, `components/class-ngt-ui-magic-card.php`, `integrations/shortcodes/class-ngt-ui-shortcodes.php` |
| blur-fade orphan CSS fix | `kind-card.css` (+ removed from `kind-misc.css`) |
| Premium rotating page loader + nav progress bar | `inc/loader.php` (new), `assets/css/bi-loader.css` (new), `assets/js/bi-loader.js` (new), require in `functions.php` |
| Version bump | theme 1.9.10 → 1.9.11 (`style.css`, `functions.php`) |

## 2c. Phase 3 — Component build-out (IMPLEMENTED)

| Change | Files |
|---|---|
| Enterprise CSS primitives (badges, stepper, skeleton, dialog, comparison) | `assets/css/bi-enterprise.css`, `inc/enterprise-components.php` |
| Accessible dialog (front) | `assets/js/bi-dialog.js` (+ focus trap) |
| Accessible dialog (admin AI Suite) | `Companion/assets/js/ngc-dialog.js`, wired in `class-ngc-ai-admin.php`, replaces `window.confirm` in `ngc-ai.js` |
| Stepper + sessionStorage match wizard | `assets/js/bi-stepper.js`, match wizard markup, `ngc-matching.js` |
| CMS components: timeline, comparison-card, status-badge, skeleton | `template-parts/ui-library/*.php` + registry filter |
| Skeleton loading in dashboards + marketplace | `dashboard-rest.js` (theme+Companion), `ngc-marketplace.js` |
| Status badges on workflow run logs | `class-ngc-workflow-admin.php` + admin CSS |
| Pricing comparison card | `defaults-production/pricing.php` |
| Version bump | theme 1.9.12 → 1.9.13 |

## 2d. Phase 7 — Auth paths, loader expansion, inline-style purge (IMPLEMENTED)

| Change | Files |
|---|---|
| Login role disambiguation (parent/student/tutor) + forgot-password expectation copy | `inc/defaults-production/login.php` |
| Role-home helpers; deep-link-safe `login_redirect`; failed-login bounce to themed `/login` | `inc/security.php` |
| Role-aware login form redirect (theme fallback + Companion) | `inc/shortcodes-fallback.php`, `NextGenTutors-Companion/.../class-ngc-shortcodes.php` |
| Password visibility toggle | `assets/js/bi-login.js`, enqueue in `functions.php` |
| Loader variants 6–8 (aurora, ring, spark) | `inc/loader.php`, `assets/css/bi-loader.css`, `assets/js/bi-loader.js` |
| Utility classes + purge static `style=""` on login/register/support/child-safety/tutor-dashboard | `style.css`, `assets/css/components.css`, defaults-production pages |
| Version bump | theme 1.9.16 → 1.9.17 |

---

### Phase 2 — Journeys & conversion (theme + Companion assets only)
| File | Change |
|---|---|
| `inc/defaults-production/pricing.php` | billing toggle, popular-plan emphasis, FAQ block, number-ticker stats |
| `inc/defaults-production/thank-you.php` | next-steps timeline |
| `templates/tutor/calendar.php` + `assets/js/` (new `bi-booking-drawer.js`) | slot → in-place booking drawer (links to existing checkout; no new business logic) |
| `page-templates/about.php` | remove inline IO script → `[data-bi-motion]` |
| `NextGenTutors-Companion/assets/js/ngc-validation.js` | error-summary `aria-describedby` links; tutor-application localStorage autosave |
| `NextGenTutors-Companion/includes/shortcodes/class-ngc-shortcodes.php` | register page: role-selector cards above forms (existing shortcodes, new arrangement) |
| kinetic home modals | focus trap util (`bi-focus-trap.js`, shared) |
| `ui-library/components/class-ngt-ui-income-calculator.php` | align fee clamp to documented 30 |

### Phase 3 — Component build-out
New theme CMS components (registry + partial + CSS): stepper, dialog/drawer, status-badge set, timeline, comparison-card, skeleton set. Replace `window.confirm` in `ngc-ai.js` + admin screens with dialog. Extend ui-library snapshot tests for each touched slug.

### Phase 4 — Role experience ✅
`dashboard-rest.js` layout variants per role (parent: children+balance first; student: next-lesson hero; tutor: earnings+payout first + application-status card) — data from `ngc/v1/dashboard/{type}` including new `application` field; command palette (`bi-command.js` + routes registry); dark mode via `data-bi-scheme="dark"` with header toggle and localStorage preference.

### Phase 5 — Performance & consolidation ✅
Orphans deleted (`theme.css`/`theme.js`); motion CSS 9→1 request (`motion-pack.css`); dashboard enqueue prefers Companion copy + Chart.js; GSAP only when effective NGT bundles need it; unified tokens inlined as critical CSS. Deferred: shared `ngc-http`/`ngc-esc`, Studio code-split, live Lighthouse.

### Phase 6 — CRO polish ✅
Trust-injection chips (02-IA §5), Find · Pricing · Login sticky mobile actions, live province/subject taxonomy coverage bands on find-a-tutor, URL-prefilled marketplace filters, and reduced-motion-safe animated social-proof stats. Implemented in theme 1.9.16 / Companion 1.9.5.

## 4. CSS/JS consolidation strategy

| Action | Detail | Status |
|---|---|---|
| Delete orphans | `theme.css` (33 KB), `theme.js` (7 KB) — never enqueued | ✅ Done — removed from theme package |
| Single dashboard-rest.js | theme enqueue prefers Companion copy when plugin active; Chart.js added on theme path | ✅ Done |
| Shared `ngc-http.js` + `ngc-esc.js` | replaces 5 per-file `esc()` and 2 REST client styles | ⏸ Deferred (low ROI vs risk this phase) |
| Conditional motion pack | 9 CSS files → 1 combined `motion-pack.css` (source 01–09 kept for edits) | ✅ Done (stronger than original plan: −8 requests site-wide) |
| NGT chain gating | `bi_ngt_skin_enabled()` already gates; GSAP now keyed to *effective* bundles after mock/kinetic skips | ✅ Tightened |
| Drop unused vendor fetch | No Three.js in theme; GSAP no longer fetched when home/static bundles are skipped | ✅ Done |
| Studio code-split | bundle 401 KB → route-level chunks (needs build-src restoration) | ⏸ Deferred |
| Critical CSS | unified token layer inlined via `wp_add_inline_style` | ✅ Done |

Performance targets (post-deploy): Lighthouse ≥95 / CLS <0.1 / LCP <2.5s on home, find-a-tutor, pricing, become-a-tutor (mobile emulation).

## 5. Quality gates (every phase exit)

1. `php scripts/validate.php` (Companion) + theme PHP lint — green
2. `php ui-library` snapshot tests for touched slugs
3. Manual keyboard walk of changed pages (tab order, focus visible, Esc closes overlays)
4. reduced-motion smoke (OS setting on → no loops)
5. `scripts/build-release.ps1` — packages build clean
6. Playwright `e2e/workflows/` suite passes (extend per phase)

## 6. Before vs after (Phase 1)

| Aspect | Before | After |
|---|---|---|
| Brand accents in CSS | 3 competing accents (`#FF9F0A`, `#c4a35a`, kinetic gold) + Magic demo purple `#9E7AFF/#FE8BBB` in 8 files | One canonical palette; all namespaces alias to it; Magic fallbacks are brand emerald/navy/amber |
| Focus visibility | 6 `outline:none` suppressions (WCAG 2.4.7 fail) | `:focus-visible` rings via `--ngt-focus-ring` everywhere |
| Page load experience | Blank paint → content pop-in | Branded rotating loader (5 variants), fade-out into gated hero entrance, reduced-motion safe, session-aware (no repeat flashing) |
| blur-fade component | Silently unstyled (CSS in wrong kind file) | Styled via kind-card.css |
| Dark mode readiness | None | Token hook `data-bi-scheme="dark"` defined; Phase 4 ships header toggle + localStorage preference |
| Income calculator | PHP clamp (50) disagreed with UI/docs (30) | Phase 2: clamp = 30 |
| Role dashboards | Identical student-like layout for parent/student; tutor missing app status | Role-specific heroes, KPIs, quick actions; tutor application card |
| Global navigation | Primary menu only | Ctrl/Cmd+K command palette for logged-in users |
| Motion CSS requests | 9 sequential stylesheets every page | 1 combined `motion-pack.css` |
| Unified tokens | Extra render-blocking CSS request | Inlined as critical CSS |
| GSAP on kinetic home | Fetched even when `home.js` skipped | Only when effective bundles include `home`/`static` |

## 7. Phased roadmap with verification checkpoints

| Phase | Scope | Exit checkpoint |
|---|---|---|
| **1 Foundation** ✅ | tokens, focus, brand alignment, loader | lint + validate.php + build green (verified) |
| **2 Journeys** ✅ | booking drawer, pricing trust, register roles, form a11y/autosave, focus traps, thank-you timeline | lint + validate.php + snapshots green (verified 2026-07-21); Playwright booking+register specs still pending live site |
| **3 Components** ✅ | stepper/dialog/badges/skeleton/timeline/comparison; AI confirm-dialog migration | lint + validate.php + snapshots green (verified 2026-07-21) |
| **4 Role UX** ✅ | dashboard variants, command palette, dark mode, app-status | lint + validate.php + snapshots green (verified 2026-07-21) |
| **5 Performance** ✅ | orphans, motion-pack, dashboard single-source, GSAP gate, critical tokens | lint + validate.php + snapshots green (verified 2026-07-21); live Lighthouse pending post-deploy |
| **6 CRO** ✅ | trust injection, sticky CTAs, coverage bands, shared counter contract | lint + validate.php + snapshots green (verified 2026-07-21); live conversion click-count/mobile/reduced-motion audit pending deployment |
| **7 Auth & polish** ✅ | login role paths, password toggle + failed-login recovery, loader +3 variants (8 total), inline-style purge on auth/support/dashboard shells | PHP lint green (2026-07-25); Playwright login-role live check pending Docker |
