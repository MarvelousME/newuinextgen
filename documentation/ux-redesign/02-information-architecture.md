# Deliverables 4–6 — Information Architecture, Navigation, User Journeys

**Constraint:** no new business functionality. Every node below maps to an existing page (`inc/pages-registry.php`), shortcode, or REST route.

---

## 1. IA principles for NextGenTutors

1. **Two front doors, one platform.** Parents/students want *find & trust*; tutors want *earn & grow*. The IA splits at the first click, then converges on shared surfaces (booking, dashboard).
2. **Trust is a journey layer, not a page.** Vetting/guarantee/safety content is injected contextually (pricing objections, pre-booking, tutor cards) instead of living only under a "Trust" menu.
3. **Dashboards are role homes.** Post-login, the dashboard is the hub; everything reachable in ≤2 clicks from it.
4. **Admin operates in wp-admin.** The front-end admin dashboard becomes a read-only ops summary linking into the NextGen wp-admin menus (removes the dual-admin ambiguity found in the audit).

## 2. Sitemap (target)

```text
PUBLIC
├─ Home (kinetic)
├─ Find a Tutor ──────────► marketplace + intake + [match wizard]
├─ Become a Tutor ────────► pitch + calculator + application
├─ Pricing ───────────────► cards + toggle + FAQ + guarantee link
├─ How it works (journey section, anchor-linked from nav)
├─ Trust: Tutor Vetting · Guarantee · Child Safety · Safety Guide
├─ Company: About · Blog · Contact · Support
├─ Legal: Privacy · Terms
└─ Auth: Login · Register · Forgot password · Thank you

LOGGED-IN (role homes)
├─ Parent dashboard ──► children · bookings · invoices · find tutor · support
├─ Student dashboard ─► next lesson · progress · achievements · book
├─ Tutor dashboard ───► calendar · earnings · application status · profile
└─ Admin summary ─────► deep-links into wp-admin NextGen menus

WP-ADMIN (operator)
├─ NextGen: Ops · Applications · Matches · Payouts · Health · Errors
│   └─ AI Suite · System Log · Agent Ops · Safeguarding · Fraud · Registry · Exports · Audit
├─ Automation Studio (SPA)
├─ Workflows (9 screens)
└─ Platform (analytics … demo control)
```

## 3. Navigation structure (updated)

### Header (public) — evolves current `inc/nav-menu.php` grouped fallback
| Slot | Content | Change vs today |
|---|---|---|
| Logo | home | — |
| Primary | Find a Tutor · Become a Tutor · Pricing · How it works | Discover/Trust/Help dropdown groups flatten to 4 primary items; trust moves to footer + contextual injection |
| Secondary | Support (dropdown: Contact, Safety, FAQ) | consolidates Help group |
| CTA pair | **Find a Tutor** (primary, high-contrast) + Login | Become-a-Tutor leaves the CTA slot (it is a primary nav item) — ends the double-CTA competition found in the audit |
| Mobile | Sticky bottom bar: Find · Pricing · Login | extends existing sticky mobile CTA in `footer.php` |

### Logged-in header (exists: `templates/header/minimal.php`)
Role home link · notifications (existing toast system) · profile menu · Help. Phase 4 adds a command palette (⌘K) in this slot.

### Footer
Keep 4-column layout; convert the hardcoded lists in `templates/footer/default.php` to WP menu locations (`footer_quicklinks`, `footer_audience`, `footer_legal`) so ops can edit without code.

## 4. User journey maps

Legend: ▢ page/screen · ◆ decision · ⚠ friction found · ✦ redesign.

### 4.1 Parent: find → book → pay
```text
▢ Home ─► ▢ Find a Tutor ─► ◆ browse cards / intake form
  ⚠ two competing entries (marketplace vs form) on one page
  ✦ marketplace primary; form becomes "Get matched instead" side panel
─► ▢ tutor profile (single-tutors.php) ─► ▢ calendar slots
  ⚠ slot CTA deep-links back to /find-a-tutor?… (context loss — biggest friction found)
  ✦ Phase 2: slot click opens an in-place booking drawer carrying tutor + slot
─► ◆ logged in? ─ no ─► ▢ Register (role pre-set = parent)  ⚠ generic register
─► ▢ parent-checkout ─► PayFast ─► ▢ thank-you
  ✦ thank-you gains a "what happens next" timeline
```

### 4.2 Tutor: land → apply → verify → earn
```text
▢ Become a Tutor ─► ✦ calculator promoted above the fold on mobile
─► ▢ application form  ⚠ long form, no autosave, no progress
  ✦ Phase 2: stepper + localStorage autosave + progress bar (validation JS exists)
─► ▢ thank-you ─► (ops approve in ngc-applications) ─► email ─► ▢ tutor dashboard
  ⚠ applicant has no status visibility between apply and approve
  ✦ Phase 4: application-status card in a lightweight logged-in state
```

### 4.3 Registration / login / password reset
```text
▢ Register  ⚠ role ambiguity (parent/student/tutor share one page)
  ✦ role selector cards first → role-specific form (all three forms already exist as shortcodes)
▢ Login ─► ◆ fail ─► ⚠ WP default error with page reload
  ✦ inline error + password visibility toggle (Phase 2)
▢ Forgot password ─► WP flow (keep; add expectation copy)
```

### 4.4 Matching wizard
```text
▢ [ngc_match_tutor] step 1..n ─► submit ─► results
  ⚠ reload loses step state · ⚠ no step indicator
  ✦ stepper component (Phase 3) + sessionStorage state
```

### 4.5 Payments / invoices / wallet
```text
dashboard ─► finance section (REST: NGC_Rest_Finance)
  ⚠ KPI-only view; no drill-down list with status badges
  ✦ Phase 4: invoice list with status timeline; payout status for tutors
```

### 4.6 Reviews
```text
lesson.completed workflow ─► review request ─► review form
  ✦ show review impact copy + keyboard-accessible star input
```

### 4.7 Support / notifications / logout
Support: help cards → form / WhatsApp FAB (exists). Add response-time promise + relevant-FAQ surfacing.
Notifications: toast system (`ngt-toast.js`, aria-live) is the delivery surface. Logout: standard WP + confirmation toast on return.

## 5. Contextual trust injection map (CRO)

| Moment | Injection | Source content (existing) |
|---|---|---|
| Pricing cards | "Every tutor passes 5-step vetting" chip → tutor-vetting | tutor-vetting default |
| Tutor card hover/profile | verification badges | achievement-badge component |
| Pre-payment panel | guarantee summary + refund promise | guarantee default |
| Register (parent) | child-safety one-liner + link | child-safety default |
| Become-a-tutor form | payout reliability stat | impact section stats |
