# NextGen UI Library — Verification Checklist

Run after each UI library change.

## Automated

- [ ] `node scripts/analyze-ui-artifacts.mjs` — inventories refreshed
- [ ] `php NextGenTutors-Companion/scripts/verify-ui-library.php` — **PASS** (no hardcoded tutor/price in UI partials)
- [ ] `php NextGenTutors-Companion/scripts/validate.php` — companion lint + version sync
- [ ] `GET /wp-json/ngc/v1/ui-library/verify` — providers report (admin)

## Source scans

- [x] 4/4 HTML artifacts scanned
- [x] Old theme folder scanned (181 files)
- [x] Duplicate headings detected (6)

## Library artifacts

- [x] Design tokens `assets/css/ng-ui-tokens.css`
- [x] Component CSS `ng-ui-components.css`, `ng-ui-hero.css`, `ng-ui-cards.css`, `ng-ui-dashboard.css`
- [x] 11 data providers registered
- [x] Component registry (11 definitions)
- [x] 11 template partials
- [x] Shortcodes `[ng_ui_component]` / `[ngc_ui_component]`
- [x] Elementor widget `ng_ui_component`
- [x] WPBakery `vc_map` element
- [x] Admin Import & Merge screen

## Data rule compliance

- [x] New UI partials contain no hardcoded tutor names
- [x] New UI partials contain no hardcoded prices/ratings
- [ ] All pages use providers (legacy `ngt_get_tutors()` fallback still in helpers — demo/CPT gated)
- [x] Demo seed only when `NGC_Platform_Demo::is_enabled()` or CPT empty

## Accessibility

- [x] Semantic sections (`<section>`, `<article>`, `<blockquote>`)
- [x] `aria-labelledby` on hero
- [x] Accordion `aria-expanded` + `hidden`
- [x] `:focus-visible` on buttons
- [x] `prefers-reduced-motion` on tokens + skeleton

## Performance

- [x] Per-page component CSS enqueue (not global bundle of all variants)
- [x] Lazy images on tutor avatars
- [ ] Calendar/booking JS deferred to phase 2

## Manual QA (recommended)

- [ ] Home renders CMS hero + tutor carousel from CPT
- [ ] Pricing cards show WooCommerce prices
- [ ] Dashboard KPIs show real user data when logged in
- [ ] Import & Merge admin loads scan table
- [ ] Mobile layout 320px–768px

## Test checklist

- [x] Playwright WF-24 homepage still passes (16/16 suite)
- [ ] No PHP notices with companion deactivated (graceful empty states)
- [ ] Elementor widget renders in editor preview
