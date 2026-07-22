# NextGenTutors — Enterprise UX Redesign Program

**Status:** Phases 1–6 implemented  
**Date:** 2026-07-21  
**Source of truth:** existing solution only (theme v1.9.16, Companion 1.9.5, ui-library 78 components)

This folder contains the complete deliverable set for the platform-wide UX/UI modernization.

| # | Deliverable | Document |
|---|-------------|----------|
| 1 | Page-by-page UX audit | [01-ux-audit.md](./01-ux-audit.md) |
| 2 | Gap analysis | [01-ux-audit.md §7](./01-ux-audit.md) |
| 3 | Prioritised improvement matrix | [01-ux-audit.md §8](./01-ux-audit.md) |
| 4 | New information architecture | [02-information-architecture.md](./02-information-architecture.md) |
| 5 | User journey diagrams | [02-information-architecture.md §4](./02-information-architecture.md) |
| 6 | Updated navigation structure | [02-information-architecture.md §3](./02-information-architecture.md) |
| 7 | Component inventory | [03-design-system.md §2](./03-design-system.md) |
| 8 | Design token specification | [03-design-system.md §3](./03-design-system.md) |
| 9 | Motion specification | [04-motion-spec.md](./04-motion-spec.md) |
| 10 | Page transition specification | [04-motion-spec.md §5](./04-motion-spec.md) |
| 11 | Enterprise frontend implementation plan | [05-implementation-plan.md](./05-implementation-plan.md) |
| 12 | File-by-file implementation plan | [05-implementation-plan.md §3](./05-implementation-plan.md) |
| 13 | Component dependency matrix | [03-design-system.md §4](./03-design-system.md) |
| 14 | Reusable layout architecture | [03-design-system.md §5](./03-design-system.md) |
| 15 | Styling architecture | [03-design-system.md §6](./03-design-system.md) |
| 16 | Animation architecture | [04-motion-spec.md §6](./04-motion-spec.md) |
| 17 | CSS/JS consolidation strategy | [05-implementation-plan.md §4](./05-implementation-plan.md) |
| 18 | Technical debt report | [01-ux-audit.md §9](./01-ux-audit.md) |
| 19 | Before vs after comparison | [05-implementation-plan.md §6](./05-implementation-plan.md) |
| 20 | Phased roadmap + verification checkpoints | [05-implementation-plan.md §7](./05-implementation-plan.md) |

## Phase 1 — implemented

1. Unified design token layer  
2. WCAG 2.2 focus repair  
3. Brand alignment of Magic UI  
4. blur-fade orphan CSS fix  
5. Premium page loader  

## Phase 2 — implemented

1. Booking drawer (context-preserving)  
2. Pricing trust injection + animated rates  
3. Thank-you role-specific timeline  
4. Register role selector  
5. Form error summary + tutor autosave  
6. Focus traps on kinetic modals  
7. About counters moved to shared `main.js`  
8. Income calculator fee clamp = 30%  

## Phase 3 — implemented

1. **Enterprise primitives** — badges, stepper, skeleton, dialog, comparison (`bi-enterprise.css`)  
2. **Accessible dialogs** — front `BIDialog` + admin `NGCDialog`; AI Suite deletes no longer use `window.confirm`  
3. **Match wizard stepper** + sessionStorage form/step persistence  
4. **CMS components** registered: timeline, comparison-card, status-badge, skeleton  
5. **Skeleton loading** on role dashboards and marketplace grids  
6. **Status badges** on workflow run logs; Online vs In-Person comparison on pricing  

## Phase 4 — implemented

1. **Role-specific dashboards** — student next-session hero; parent learners + balance first; tutor earnings/payout first; admin quick actions  
2. **Application status card** — tutor dashboard surfaces pending/approved/rejected from `tutor_applications`  
3. **Quick actions** — role-aware CTA row from localized page URLs  
4. **Dark mode** — `data-bi-scheme="dark"` toggle in all headers; preference in `localStorage`; FOUC-safe head script  
5. **Command palette** — Ctrl/Cmd+K for logged-in users; role-aware routes; toggle dark mode + logout actions  

## Phase 5 — implemented

1. **Orphans deleted** — `theme.css` (~33 KB) + `theme.js` (~7 KB) never enqueued  
2. **Motion pack consolidated** — 9 CSS requests → 1 (`motion/motion-pack.css`)  
3. **Dashboard single source** — theme enqueue prefers Companion `dashboard-rest.js`; Chart.js now loaded on the theme path  
4. **GSAP gated to effective bundles** — no GSAP fetch when kinetic home / mock-data skip removes the consumers  
5. **Critical CSS** — unified token layer inlined (no separate render-blocking request)  

## Phase 6 — implemented

1. **Contextual trust chips** — vetting on pricing/find, child safety on parent registration, payout reliability near tutor application, and NextGen100 before checkout  
2. **Mobile sticky actions** — accessible Find · Pricing · Login bottom navigation with safe-area spacing  
3. **Live coverage bands** — subject/province taxonomy counts deep-link to and drive the REST marketplace filters  
4. **Animated social proof** — shared decimal/prefix/suffix-safe counters with reduced-motion support; impact stats now use live platform metrics  
5. **Marketplace polish** — URL filter prefill and corrected “Verified” badge copy  

Deferred (documented in §4): shared `ngc-http`/`ngc-esc`, Studio route-level code-split (needs build-src), live Lighthouse scoring on production.

Theme version: **1.9.16**. Companion: **1.9.5**.

## Verification (executed 2026-07-21)

- `php NextGenTutors-Companion/scripts/validate.php` — green (BI 1.9.15 / NGC 1.9.4 synchronized, 64 unit assertions)
- `php ui-library/tests/catalog-snapshot.php` — green (28 checks)
- `php ui-library/tests/integration-smoke.php` — green
- PHP lint on Phase 5 PHP files — clean
- Phase 6: PHP lint (10 touched PHP files) and Node syntax checks (2 touched JS files) — clean
- Phase 6: Companion validation green (211 PHP files, 64 assertions); catalog snapshots 28/28; integration smoke green
- **Not run:** `scripts/build-release.ps1` — only 0.07 GB free on C: at verification time; free disk space before rebuilding release ZIPs
- **Not run:** Lighthouse on live/staging — run after deploy to confirm LCP/CLS targets
