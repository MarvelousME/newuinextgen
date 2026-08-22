# Frontend SWOT / GAP Analysis

**Audit date:** 2026-08-14

| ID | Severity | Issue | Root Cause | Evidence | Impact | Fix |
| -- | -------- | ----- | ---------- | -------- | ------ | --- |
| FE-001 | CRITICAL | Homepage body missing; footer looked like duplicate menu | Elementor/Hello `page.php` owned front page when kinetic fallback false; printed only `<!-- bi-prototype:home -->` | `site-main:0`, between-nav-footer len=35 | Entire home UX broken | `template_include` pri **9999**; widget-aware `bi_elementor_has_content`; ignore prototype markers |
| FE-002 | CRITICAL | Offcanvas links invisible / drawer under footer | Light text on light panel; `--navy`→cyan; header stacking context z-40 | Prior CDP; footer y=0 over drawer | Unusable mobile nav | Hard `#092746` + white links; elevate header to `--ngt-z-header-elevated` |
| FE-003 | HIGH | Duplicate mobile CTA vs primary nav | Sticky CTA always printed | Marketing pages showed Find a Tutor twice | Perceived duplicate menu | Skip when marketing header chrome |
| FE-004 | HIGH | Ad-hoc z-index 1200 vs token scale | Sticky/offcanvas escalation | CSS grep | Maintainability | Semantic tokens 450–900 |
| FE-005 | HIGH | Body scroll lock stuck after resize | No breakpoint listener | Spec risk | Desktop unusable after rotate | `matchMedia` + orientation close in `nav-menu.js` |
| FE-006 | MEDIUM | Competing home templates | `page-templates/home.php`, `template-home.php`, kinetic | Inventory | Wrong template assignment | Document ownership; prefer kinetic |
| FE-007 | MEDIUM | Subjects defaults missing | No `defaults-production/subject.php` | File search | Blank/fragile subjects page | Add production default |
| FE-008 | MEDIUM | Multiple tutor-card renderers | PHP partial + UI lib + Companion marketplace + JS | Inventory | Visual drift | Shared renderer consolidation |
| FE-009 | MEDIUM | Breakpoint proliferation | 20+ unique media queries | CSS audit | Inconsistent collapse | Normalize docs; nav stays 1099 |
| FE-010 | MEDIUM | Toast/theme-switcher z outliers | Legacy ceilings | CSS | Stacking surprises | Migrate to tokens |
| FE-011 | LOW | Root `functions.php` drift | Overlay not mounted | v1.9.28 vs 1.9.33 | Confusion | Treat theme `functions.php` as SSOT |
| FE-012 | LOW | Cursor browser `innerWidth`≠`clientWidth` | Emulation artifact | CDP cw=390 iw=473 | False overflow positives | Cross-check real devices |
| FE-013 | INFORMATIONAL | Elementor preferred but kinetic home PHP | Product choice | Runtime | Elementor-native home deferred | Keep until real Elementor home authored |

## SWOT

- **Strengths:** Shared nav SSOT, builder adapters, token system, Companion shortcodes  
- **Weaknesses:** Template authority races with Elementor; overlay/theme dual trees; z-index history  
- **Opportunities:** Elementor Theme Styles bridge; unified scroll-lock manager; Playwright matrix  
- **Threats:** Empty Elementor documents reclaim pages; third-party LMS/Elementor CSS collisions
