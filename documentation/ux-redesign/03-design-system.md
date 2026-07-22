# Deliverables 7, 8, 13, 14, 15 — Component Inventory, Design Tokens, Dependencies, Layout & Styling Architecture

---

## 1. Design language: "Beyond Clarity"

A unique language for NextGenTutors — not a copy of Stripe/Linear/Vercel, but sharing their discipline:

- **Deep-navy authority + emerald progress + amber energy.** Canonical brand: navy `#0F1A2F` (structure/trust), emerald `#059669` (growth/success/actions), amber `#FF9F0A` (highlights/energy). These already exist in `style.css`; the redesign makes them *the only* palette.
- **Light-first, glass-accented.** White/`#F8FAFC` surfaces; glassmorphism reserved for overlays (nav on scroll, loader, drawers) — never body content.
- **Motion means meaning.** Every animation communicates state change or draws attention to one thing. The existing motion pack (01–09) stays; the new rule is *one hero motion per viewport*.
- **Typography:** Sora (display) + Inter (body) — already loaded; the redesign enforces the scale via tokens instead of ad-hoc `font-size`.

## 2. Component inventory (existing, canonical home per component)

### Theme partials (`template-parts/`)
section-heading · tutor-card · hero · how-it-works · impact · mission · services · trust-strip · tutors · final-cta · wp-banner

### Theme CMS components (Companion registry `NGC_UI_Component_Registry` → theme `template-parts/ui-library/`)
hero · stats-band · tutor-card · subject-card · pricing-card · review-card · dashboard-kpi · booking-list · calendar-grid · achievement-badge · empty-state

### Magic UI (ui-library, 78 slugs, 11 kinds)
4 dedicated (magic-card, border-beam, marquee, income-calculator) + 74 catalog across button(8) / card(9) / device(3) / interactive(10) / list(5) / map(1) / media(4) / pattern(12) / progress(2) / text(20).

### Companion frontend
marketplace grid + filters · match wizard · match FAB panel · dashboard shell (KPI/sessions/charts) · calendar slots · forms (`.ngc-form` + validation + button-processing) · studio forms/dashboards

### Missing components (build in Phase 3, as theme CMS components or Companion assets)
| Component | Consumers | Notes |
|---|---|---|
| Stepper | tutor application, match wizard, onboarding | progress + keyboard nav |
| Dialog / Drawer | booking panel, delete confirmations (replaces `window.confirm`) | focus trap, `aria-modal` |
| Data grid | admin tables, invoices | sortable headers, status badges, bulk actions |
| Timeline | thank-you next steps, payout status, audit history | vertical, token-driven |
| Command palette | logged-in header (Phase 4) | ⌘K, fuzzy filter over registered routes |
| Toast variants | success/error/info already exist — add action-toast (undo) | extend `ngt-toast.js` |
| Status badge | workflows admin, invoices, applications | single `.ngt-badge--{state}` set |
| Comparison card | pricing | two-column plan compare |
| Skeleton set | dashboards, marketplace | shimmer via tokens, honors reduced-motion |

## 3. Design token specification (implemented: `assets/css/tokens/unified.css`)

**Single source of truth.** Loaded at enqueue priority 5 (with `tokens/base.css`), before skins, components, kinetic, NGT, and ui-library CSS. All four legacy namespaces resolve to canonical values by alias, so **no component rewrite is required for consistency** — and components migrate to canonical names opportunistically.

### Canonical scale
| Group | Tokens |
|---|---|
| Color (brand) | `--ngt-primary`, `--ngt-primary-dark/-light`, `--ngt-secondary(±)`, `--ngt-accent(±)` |
| Color (surface) | `--ngt-bg`, `--ngt-surface`, `--ngt-surface-2`, `--ngt-border`, `--ngt-border-light` |
| Color (text) | `--ngt-text`, `--ngt-text-2`, `--ngt-text-3`, `--ngt-text-invert` |
| Status | `--ngt-success(-bg)`, `--ngt-warning(-bg)`, `--ngt-error(-bg)`, `--ngt-info(-bg)` |
| Focus | `--ngt-focus-ring`, `--ngt-focus-ring-offset` (new — WCAG 2.4.7 repair) |
| Typography | `--ngt-font-display`, `--ngt-font-body`, `--ngt-text-xs…-3xl` (fluid `clamp()`) |
| Spacing | `--ngt-space-xs…-3xl` (4/8/16/24/32/48/64) |
| Radius | `--ngt-radius-sm/md/lg/xl/full` |
| Elevation | `--ngt-shadow-sm/md/lg/xl` + `--ngt-shadow-glass` |
| Blur | `--ngt-blur-glass` |
| Motion | `--ngt-duration-fast/base/slow`, `--ngt-ease`, `--ngt-ease-out`, `--ngt-ease-spring` |
| Z-index | `--ngt-z-nav/drawer/modal/toast/loader` |
| Layout | `--ngt-layout-max`, `--ngt-content-pad`, `--ngt-grid-gap` |

### Alias mapping (compatibility layer, in unified.css)
| Legacy namespace | Mapping |
|---|---|
| `--ng-color-*`, `--ng-space-*`, `--ng-radius-*`, `--ng-shadow-*`, `--ng-duration-*` (ng-ui-tokens) | re-pointed to canonical `--ngt-*` values |
| `--ngi-*` (kinetic) | gold/cyan retained as *kinetic-scoped* accents, but base navy/text/line resolve to canonical |
| NGT legacy `--navy`, `--lime`, `--amber`, `--r-*`, `--sh-*` | `--navy`→primary, `--lime`→secondary, `--amber`→accent, radii/shadows→canonical |
| ui-library `--ngt-color-*` | accent/primary re-pointed to brand (see §6 note on scoping) |

### Dark-scheme hook (Phase 4-ready)
`html[data-bi-scheme="dark"]` block re-assigns surface/text/border/shadow tokens only. No component changes needed when dark mode ships because components consume tokens.

## 4. Component dependency matrix

| Component | Tokens | CSS file | JS | Depended on by |
|---|---|---|---|---|
| Buttons (`.ngt-btn`) | color/radius/motion/focus | style.css + components.css | ngc-button-processing.js | all forms, CTAs, cards |
| Cards (`.ngt-card`) | surface/shadow/radius | components.css | — | dashboards, marketplace, pricing |
| Forms (`.ngc-form`) | color/status/focus | ngc-forms.css + ngc-validation.css | ngc-validation.js | find-tutor, become-tutor, contact, register |
| Dashboard shell | surface/spacing | dashboard-mission.css | dashboard-rest.js | 4 role pages |
| Marketplace | color/shadow | ngc-marketplace.css | ngc-marketplace.js | find-a-tutor |
| Magic UI kinds | ui-library tokens (aliased) | catalog.css + kind-*.css | catalog-core/interactive.js | home, marketing sections |
| Toast | status/z/motion | ngt-toast.css | ngt-toast.js | global |
| Loader (new) | brand/motion/z | bi-loader.css | bi-loader.js | global |
| Nav | color/z/blur | nav-menu.css | nav-menu.js | global |

Rule: **new components may only consume tokens** — a stylelint-style review gate in Phase 3 CI (grep for hex literals in changed CSS).

## 5. Reusable layout architecture

Existing: `layout-system.css` defines `--ng-layout-*` / `--ng-z-*`; `page-wrapper.php` renders defaults. Target primitives (Phase 3, CSS-only, no markup invention):

| Primitive | Class | Purpose |
|---|---|---|
| Shell | `.ngt-shell` | max-width + fluid padding (`--ngt-layout-max`, `--ngt-content-pad`) |
| Grid-12 | `.ngt-grid` | 12-col CSS grid, `--ngt-grid-gap`, collapses 12→6→4→1 |
| Stack | `.ngt-stack-{s,m,l}` | vertical rhythm via `gap` |
| Cluster | `.ngt-cluster` | horizontal wrap groups (chips, meta rows) |
| Sidebar | `.ngt-with-sidebar` | content + context panel (dashboards) |
| Sticky region | `.ngt-sticky-actions` | sticky CTA/action bars (exists ad-hoc on home/mobile — formalize) |

## 6. Styling architecture

```text
Load order (front-end):
1. tokens/base.css + tokens/unified.css   ← canonical + aliases   (priority 5)
2. skins/{preset}.css                      ← may override semantics (kept)
3. style.css + components.css + sections.css
4. motion/01–09 (priority 15)
5. ng-ui / NGT / kinetic / nbi layers (12–38) — all now inherit canonical values via aliases
6. page-specific CSS (dashboard, forms, marketplace)
```

Rules:
1. **No new hex values** outside token files (review gate).
2. **Component CSS uses `var()` only**; instance overrides via scoped custom properties (pattern already used by Magic UI `--ngt-accent` instance vars — keep, but defaults now come from brand).
3. **ui-library scoping note:** catalog components receive instance `--ngt-accent/--ngt-accent-2` inline from `NGT_UI_Tokens` (PHP). Fallbacks in kind CSS now reference brand tokens instead of Magic demo purple, so unstyled instances degrade to brand, not to a foreign palette.
4. **Deprecations (Phase 5):** `assets/css/theme.css` + `assets/js/theme.js` (orphaned, never enqueued) removed after final reference audit; duplicate `dashboard-rest.js` consolidated to Companion copy.
