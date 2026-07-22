# Phase 6 runtime verification — 2026-07-22

## Environment
- URL: http://localhost:8900
- Theme: nextgentutors-beyondinfinity (child of hello-elementor) — activated via DB after Hello Elementor install
- Precondition MU-plugin: `wp-content/mu-plugins/ngt-phase6-runtime-verify.php` forces `bi_use_prototype_blend` false so production defaults (trust chips / coverage) render
- Fix applied: truncated duplicate fragment in `inc/theme-switcher.php` (parse error)

## Playwright — `e2e/workflows/phase6-cro-runtime.spec.ts`
Result: **8/8 passed** (~3.6 min)

1. find-a-tutor trust chip + coverage + data-bi-count — PASS
2. pricing + register?role=parent trust chips — PASS
3. mobile sticky Find · Pricing · Login — PASS
4. coverage chip deep-link filters — PASS
5. find → book-start ≤3 clicks (intake Submit when marketplace empty) — PASS
6. reduced-motion counters readable — PASS
7. keyboard focus sticky Find — PASS
8. axe scan records serious/critical findings — PASS (known debt)

## Accessibility (axe)
File: `phase6-axe-find-a-tutor.json`
Blocking rule IDs: `color-contrast` (footer), `select-name` (marketplace filter selects)

## Lighthouse
**NOT RUN** — C: free ~1.0 GB; installing lighthouse would risk disk exhaustion.

## Status
UX Phase 6 runtime behavior: **PARTIAL** (CRO surfaces verified under production-defaults force; a11y debt + Lighthouse open; prototype_blend still on by default without MU-plugin)
