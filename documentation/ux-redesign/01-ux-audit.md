# Deliverable 1–3, 18 — Page-by-Page UX Audit, Gap Analysis, Improvement Matrix, Technical Debt

**Audited surface:** 24 registered pages (theme `inc/pages-registry.php`), 30+ Companion admin screens, 12 core + 8 extended shortcodes, 78 ui-library components, 4 role dashboards.  
**Method:** static analysis of every template, default, stylesheet, and script in the repo. No business functionality invented; every finding cites the file it comes from.

---

## 1. Scoring rubric

Each page scored 1–5 on: Hierarchy (H), Conversion (C), Accessibility (A), Responsiveness (R), Motion quality (M), States/feedback (S). 5 = enterprise-grade (Stripe/Linear class).

---

## 2. Public marketing pages

| Page (default) | H | C | A | R | M | S | Key findings |
|---|---|---|---|---|---|---|---|
| **home** (kinetic) | 4 | 4 | 3 | 4 | 4 | 3 | Strongest page. Sticky book CTA, trust marquee, tabs, FAQ. Issues: 22 KB kinetic CSS + full motion pack on every load; `--ngi-*` tokens remap `--ngt-*` at runtime (cascade fragility); video modal lacks focus trap. |
| **find-a-tutor** | 4 | 4 | 3 | 4 | 3 | 4 | Good: hero → KPI → marketplace → intake form → steps. Marketplace has loading/empty/error states. Issues: intake form below marketplace duplicates CTA intent; no sticky CTA on mobile; filters not keyboard-discoverable. |
| **become-a-tutor** | 4 | 4 | 3 | 4 | 3 | 3 | Earnings calculator is a strong conversion asset (`[ngt_income_calculator]`). Issues: calculator clamps disagree with docs (fee max 50 in PHP vs 30 in range UI); application form lacks progress indication for the multi-field submit. |
| **pricing** | 3 | 3 | 3 | 4 | 2 | 3 | Three static cards. Missing: billing toggle, per-card "most popular" affordance is weak, no comparison, no FAQ anchored under cards, no animated number transitions. |
| **about** | 3 | 2 | 3 | 4 | 3 | 3 | Inline IntersectionObserver script in `page-templates/about.php` duplicates `motion.js` (double-animation risk). Stats band is static. |
| **guarantee / tutor-vetting / safety-guide / child-safety** | 4 | 3 | 4 | 4 | 3 | 3 | Trust cluster is well structured (5-step verification, badges). Gap: not cross-linked from pricing/booking moments where trust objections actually occur. |
| **contact / support** | 3 | 3 | 3 | 4 | 2 | 4 | Support form has validation + processing states. Gap: no expected-response-time promise, help cards not searchable. |
| **blog** | 3 | 2 | 3 | 4 | 2 | 2 | Featured + side posts. No category filter UI, no reading-time, no empty state for no posts. |
| **privacy / terms** | 3 | — | 4 | 4 | — | — | Numbered legal sections fine. Missing: sticky in-page TOC for long docs. |
| **login / register** | 3 | 3 | 3 | 4 | 2 | 3 | `wp_login_form` shell in card. Missing: inline error recovery guidance, password visibility toggle, "continue as parent/student/tutor" role disambiguation before the form. |
| **thank-you** | 3 | 3 | 4 | 4 | 2 | 4 | Confirmation card + CTAs. Missing: next-step timeline ("what happens now"). |

## 3. Logged-in dashboards

All four dashboards share one render path: PHP shell (`NGC_Shortcodes::dashboard()`) → `dashboard-rest.js` hydration → KPI cards + sessions + Chart.js. States: loading / empty / error all present with `aria-live` + `aria-busy` (good baseline).

| Dashboard | Findings |
|---|---|
| **Parent** | KPI cards generic (same component as all roles). Missing: child-learner switcher, next-lesson countdown, unpaid-invoice alert priority, quick actions (book again, message tutor). |
| **Student** | Same shell. Missing: streak/gamification surface despite GamiPress integration existing (`ngc-gamification` admin exists but no student-facing widget); homework/next-session emphasis. |
| **Tutor** | Missing: earnings summary hero (data exists in Finance REST), calendar-first layout (calendar is a separate shortcode `nextgen_tutor_calendar`), payout status timeline. |
| **Admin** | `admin-dashboard.php` exists as page template; ops live mostly in wp-admin (NextGen menu). Duplication of "admin dashboard" concept in two places (front-end shell + wp-admin ops screens) with no cross-linking. |

**Systemic:** roles get *tailored data* but not *tailored layout* — one `bi-dash-kpi` grid for everyone. No recent-activity feed, favourites, or saved filters anywhere. No command palette or global search in any logged-in surface.

## 4. Companion wp-admin screens

| Cluster | Findings |
|---|---|
| NextGen ops (`ngc-operations`, applications, matches, payouts, health, errors) | Classic WP tables. Functional but no bulk actions, no saved filters, no column sort on several tables. `nextgen-beyond-infinity-admin.css` adds hero wash + card polish (good) but only ~3.5 KB of the surface is touched. |
| AI Suite (`ngc-ai-suite`) | Tabs (Models/Agents/Chat) with proper `aria` toggling in `ngc-ai.js`. Status messages via `.ngc-ai-msg` (`role="status"`, `aria-live`). Gaps: no confirmation of key test latency history; delete uses `window.confirm`. |
| Automation Studio | React SPA (`studio.bundle.js`, 401 KB). Has fallback (`studio-fallback.js`). Gap: bundle not code-split; loads whole editor for any studio screen. |
| Platform (analytics, profiling, privacy, observability, demo control) | Chart.js + jQuery `$.ajax` in `ngc-platform-analytics.js` while newer surfaces use `fetch` — two REST client styles. Demo Control Centre is strong (Phase 14). |
| Workflows | Nine submenus; status checklist pages are text-heavy tables without visual state (no green/amber badges pattern reuse). |

## 5. Forms audit

| Form | Findings |
|---|---|
| find-tutor / become-tutor / contact / register (`.ngc-form`) | Client validation (`ngc-validation.js`: field states, shake, progress) + global submit-lock ripple (`ngc-button-processing.js`). Good. Gaps: validation CSS hardcodes slate/blue (no tokens); error summary not linked to fields for screen readers; no autosave for the long tutor application. |
| Match wizard (`[ngc_match_tutor]`) | Multi-step with smooth scroll; spinner on submit. Gap: no step indicator persistence on reload; back button loses state. |
| Login | No inline validation; WP default error redirect. |
| Booking | Not a form — calendar CTAs deep-link to `/find-a-tutor` with query args. Journey break: user picks a slot then lands on a generic page and must re-enter context. **Highest-friction journey found.** |

## 6. Accessibility audit (WCAG 2.2 AA)

**Passing patterns (keep):** skip link (`header.php`), `aria-expanded/haspopup` nav dropdowns, `aria-live` dashboards/toasts, `aria-modal` dialogs, decorative `aria-hidden` icons, reduced-motion honored in `motion.js`, `ng-ui-tokens.css` (zeroed durations), catalog runtime, and interactive canvas loops.

**Failures found (fixed in Phase 1 where marked ✅):**

| Issue | Location | WCAG | Status |
|---|---|---|---|
| `outline: none` on focus with only border-color change (insufficient for 2.4.11/2.4.7) | `assets/ngt/css/components.css:244`, `pages.css:173`, `floating.css:99,210`, `content.css:200`, `admin.css:189` | 2.4.7 Focus Visible | ✅ fixed — `:focus-visible` ring tokens |
| Magic purple gradient text on light surfaces ~2.9:1 contrast | `ui-library/assets/css/kinds/kind-text.css` | 1.4.3 Contrast | ✅ fixed — brand tokens (navy/emerald ≥4.5:1 on white) |
| Video/booking modals: no focus trap | kinetic home defaults | 2.4.3 Focus Order | Phase 2 (spec in 04-motion-spec §5.4) |
| `window.confirm` for destructive actions | `ngc-ai.js`, admin screens | — (UX, not failure) | Phase 3 — accessible dialog component |
| Error summary not programmatically associated | `ngc-validation.js` | 3.3.1 | Phase 2 |
| No visible focus style on marketplace filter chips | `ngc-marketplace.css` | 2.4.7 | Phase 2 |

**Dark mode:** none exists (only `html.ngt-ui-dark` toggle demo in ui-library). Unified token layer now defines the dark-scheme hook (`html[data-bi-scheme="dark"]`) so Phase 4 can ship it without re-architecture.

## 7. Gap analysis (capability level)

| Capability | Exists today | Gap |
|---|---|---|
| Design tokens | 4 competing namespaces (`--ngt-*` style.css, `--ng-*` ng-ui-tokens, `--ngi-*` kinetic, legacy `--navy/--lime` NGT) with diverging values (accent `#FF9F0A` vs `#c4a35a` vs kinetic gold) | ✅ Phase 1: unified canonical layer; namespaces become aliases |
| Motion system | Strong CSS pack (01–09) + IO + reduced-motion | No page-transition manager, no branded loader → ✅ Phase 1 loader; transitions Phase 2 |
| Component library | 78 Magic UI + 11 theme CMS components + theme partials | No steppers, command palette, data-grid, timeline, comparison cards → Phase 3 |
| Role UX | Tailored data per role | No tailored layout/navigation/quick actions per role → Phase 4 |
| Conversion | Calculator, trust cluster, sticky home CTA | Pricing page static; booking journey breaks context; no social-proof injection at decision points → Phase 2/5 |
| Global search / command palette | None | Phase 4 |
| Dark mode | None | Token hooks ready; Phase 4 |
| Performance | Full motion pack + NGT chain + kinetic CSS on marketing pages (~150 KB CSS typical) | Consolidation strategy in 05 §4; conditional motion loading Phase 5 |

## 8. Prioritised improvement matrix

Impact × effort, P1 = do first. Phase column maps to roadmap in 05-implementation-plan §7.

| P | Item | Impact | Effort | Phase |
|---|------|--------|--------|-------|
| P1 | Unified token layer (kills 4-namespace drift) | Very high (every surface) | M | **1 ✅** |
| P1 | Focus-visible repair (WCAG blocker) | High | S | **1 ✅** |
| P1 | Brand-align Magic UI fallbacks | High (brand trust) | S | **1 ✅** |
| P1 | Premium page loader + transition manager | High (perceived quality) | M | **1 ✅** (loader) / 2 (transitions) |
| P2 | Booking journey: slot → contextual booking panel (no context loss) | Very high (conversion) | L | 2 |
| P2 | Pricing page: toggle, popular emphasis, FAQ, animated numbers | High | M | 2 |
| P2 | Form error-summary a11y + autosave on tutor application | High | M | 2 |
| P2 | Modal focus traps | High | S | 2 |
| P3 | Component additions: stepper, dialog, data-grid, timeline, command palette | High | L | 3 |
| P3 | Replace `window.confirm` with dialog component | Med | S | 3 |
| P3 | Admin tables: badges, bulk actions, saved filters | Med | L | 3 |
| P4 | Role-specific dashboard layouts + quick actions + activity feed | Very high | XL | 4 |
| P4 | Dark mode via `data-bi-scheme="dark"` | Med | M | 4 |
| P4 | Global search / command palette (⌘K) | High | L | 4 |
| P5 | CSS/JS consolidation + conditional motion loading | High (perf) | L | 5 |
| P5 | Studio bundle code-splitting | Med | L | 5 |
| P6 | CRO experiments: social proof injection, sticky CTAs, province/subject coverage | High | M | 6 |

## 9. Technical debt report

| Debt | Evidence | Cost of keeping | Remediation |
|---|---|---|---|
| **Four token systems** | `style.css` `--ngt-*`, `ng-ui-tokens.css` `--ng-*`, `kinetic-tokens.css` `--ngi-*`, `assets/ngt/css/tokens.css` `--navy/--lime` | Every new component picks a namespace at random; brand drift (3 different accents) | ✅ `tokens/unified.css` canonical + alias mapping; namespaces deprecated in place |
| **Orphaned assets** | `assets/css/theme.css` (33 KB, never enqueued), `assets/js/theme.js` (never enqueued) | Confusion, shipped dead weight in zip | Delete in Phase 5 after grep confirms zero references |
| **Duplicated dashboard-rest.js** | Theme + Companion both ship copies | Fix in one, forget the other | Phase 5: single source (Companion), theme dequeues |
| **Two REST client styles** | jQuery `$.ajax` (analytics, system-log) vs `fetch` (marketplace, dashboards, AI) | Inconsistent error handling | Phase 5: shared `ngc-http.js` util |
| **Per-file `esc()` duplication** | 5 JS files each define an HTML escaper | Drift risk (XSS if one diverges) | Phase 5: shared util |
| **Inline IO script in about template** | `page-templates/about.php` | Competes with `motion.js` | Phase 2: remove inline, use `[data-bi-motion]` |
| **blur-fade CSS in wrong kind file** | JS adds `.ngt-ui-blur-fade`; rules in `kind-misc.css`; slug is `kind: card` | Component silently unstyled | ✅ Phase 1: rules moved to `kind-card.css` |
| **Vendor loader oversell** | Three.js + GSAP fetched for globe/particles; globe renders 2D canvas; GSAP unused | ~600 KB wasted on pages using those components | Phase 5: drop vendor fetch until real usage |
| **Snapshot coverage 28/78** | `ui-library/tests/snapshots/` | Regressions invisible | Phase 3: extend with each touched component |
| **Docs vs code clamps** | Income calculator fee ≤50 (PHP) vs ≤30 (docs/range) | Support confusion | Phase 2: align to 30 |
