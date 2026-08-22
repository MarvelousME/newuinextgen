# 09 — Implementation Plan

**Migration:** reference theme → NextGenTutors-BeyondInfinity  
**Nav schema:** `2026-08-09-reference-nav-v1`

## Phase 1 — Chrome & navigation ✓

| Task | Status |
|------|--------|
| Rewrite `bi_nav_menu_structure()` to reference order/labels | ✓ Done |
| Compliance dropdown with 5 sub-items | ✓ Done |
| Remove Get Started from header + chrome.js | ✓ Done |
| `bi_render_header_sign_in_cta()` in default + transparent headers | ✓ Done |
| Schema version bump + menu sync | ✓ Done |

## Phase 2 — Footer ✓

| Task | Status |
|------|--------|
| Migrate Explore / Company / Get In Touch columns | ✓ Done |
| Brand copy + Proudly South African chip | ✓ Done |
| Live contact helpers (phone, email, service area) | ✓ Done |
| Legal bar: Privacy, Terms, POPIA → child-safety | ✓ Done |

## Phase 3 — Content merges ✓

| Task | Status |
|------|--------|
| Home kinetic + ref section merge | ✓ Done |
| About + brand-content merge | ✓ Done |
| Become-a-tutor ref steps merge | ✓ Done |
| defaults-production sync (home, about, become-a-tutor) | ✓ Done |

## Phase 4 — Page enhancements (in progress)

| Task | Status |
|------|--------|
| find-a-tutor: NGC matching + ref directory UX | ✓ Code; ⏳ E2E verify |
| contact: live form + ref FAQ | ✓ Code; ⏳ E2E verify |
| pricing: live rates + ref tiers | ✓ Done |
| Remaining trust/legal pages | ✓ Defaults aligned |

## Phase 5 — Verification (pending)

| Task | Status |
|------|--------|
| Nav order/labels visual QA (desktop + mobile drawer) | ⏳ Pending |
| Footer link crawl (all columns + legal) | ⏳ Pending |
| Sign In → login; logged-in → role dashboard | ⏳ Pending |
| Compliance dropdown active states on sub-pages | ⏳ Pending |
| Booking commerce E2E (Find→Pay→Lesson) | ⏳ Pending |
| Phase 14 demo evidence pack | ⏳ Pending |

## Phase 6 — Decommission reference (future)

| Task | Status |
|------|--------|
| Archive `nextgen-tutors-theme` desktop copy | Not started |
| Remove duplicate chrome from non-canonical paths | Partial (`assets/ngt/js/chrome.js` synced) |
| Document canonical theme in deployment runbook | ⏳ Pending |

## Risk register

| Risk | Mitigation |
|------|------------|
| Stale WP nav menu on live site | `bi_nav_public_schema` forces rebuild |
| Get Started remnants in prototypes | Acceptable; not rendered in production nav |
| Ref static tutor data vs live CPT | Companion marketplace is source of truth |
| POPIA legal copy on child-safety | Legal review recommended |

## Success criteria

- [ ] All 13 ref page templates reachable with correct labels
- [ ] Nav matches reference order minus Get Started
- [ ] Footer columns match reference structure with live contact data
- [ ] No alternate booking flow bypassing Companion
- [ ] E2E evidence under `delivery/evidence/` green
