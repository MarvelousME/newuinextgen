# 06 — Workflow Comparison

## Primary user journeys

| Journey | Reference theme | Target (BI + Companion) | Consolidation |
|---------|-----------------|---------------------------|---------------|
| Discover tutor | `find-a-tutor.html` + static `tutors.js` grid | `/find-a-tutor` + `ngc_tutor_marketplace` REST | **Companion** owns matching |
| View profile | `single-tutors` prototype | `single-tutors.php` + NGC tutor meta | **Companion** |
| Contact / book tutor | Ref modal "Contact Tutor" UI | Match → book slot → checkout | **Companion**; ref UI not duplicated |
| Pay | WooCommerce prototype checkout | `parent-checkout` + PayFast sandbox | **Companion** commerce |
| Attend lesson | Dashboard link / Amelia hooks | Session orchestrator + classroom join | **Companion** |
| Become tutor | Static form prototype | `ngc_become_tutor_form` + vetting workflow | **Companion** |
| Parent register child | Register page prototype | `ngc_parent_register_child_form` | **Companion** |
| Sign in | login.html / dashboard.html | `/login` + role dashboards | **Keep** BI auth pages |

## Canonical commerce chain (retained)

```
Find Tutor → Match → Book → Pay → Lesson
```

All steps execute through **NextGenTutors-Companion** domain services and REST. Reference theme booking UI is **not** ported as an alternate flow.

## Reference-only workflows (not migrated as domain)

| Ref workflow | Disposition |
|--------------|-------------|
| Static tutor contact modal | Dropped — use NGC booking drawer |
| `dashboard-api.js` mock endpoints | Replaced by `dashboard-rest.js` + NGC REST |
| Amelia / FluentCRM demo hooks | Companion adapters (`class-ngc-*`) |
| Workflows admin JSON triggers | Companion + bundled automations |
| Setup wizard first-run | BI `wordpress-setup` + Companion plugin activation |

## Navigation / auth workflow

| Step | Reference | Target |
|------|-----------|--------|
| Anonymous browse | Full nav + Get Started | Full nav + Sign In only |
| Sign in | login.html | `/login` |
| Post-auth | dashboard.html (generic) | Role dashboard URL via `bi_user_role_home_url()` |
| Register | Get Started CTA | `/register` (deep links only; no header CTA) |

## Admin / ops workflows

| Workflow | Owner |
|----------|-------|
| Tutor vetting pipeline | Companion + onboarding page |
| Invoice / booking reconciliation | Companion (`class-ngc-bookings`, invoices) |
| Session lifecycle | Companion session orchestrator |
| Demo seed / evidence | Companion scripts + Phase 14 policy |
