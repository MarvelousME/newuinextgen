# Appendix C — Companion Plugin Boundary

**Theme:** BeyondInfinity v1.4.6  
**Evidence status:** **PARTIAL** — companion plugin (`nextgencompanion`) not present in REVAMP workspace; boundary derived from theme interop code + `COMPARE-DIFFERENCES.md` inventory.

---

## Purpose

BeyondInfinity is a **presentation + orchestration shell**. Business data, CPTs, custom tables, dashboard payloads, and most `ngc_*` shortcodes are owned by **`nextgencompanion`** (or bridged NGT platform when co-installed).

The theme provides:
- Page shells, defaults, RBAC gates (`inc/security.php`)
- Fallback form handlers when companion is inactive (`inc/shortcodes-fallback.php`)
- Workflow pack runner (`inc/workflows.php` + `content/nextgen-workflow-pack.json`)
- OpenWA adapter (`inc/openwa.php`, namespace `bi/v1`)
- REST client config for dashboards (`inc/companion.php`, `assets/js/dashboard-rest.js`)

---

## Detection & version bridge

| Function | Behavior | Evidence |
|----------|----------|----------|
| `bi_bridge_ngc_version()` | Mirrors `NGT_VERSION` → `NGC_VERSION` if companion undefined | `inc/companion.php` **VERIFIED** |
| `bi_companion_active()` | True if `NGC_Plugin` class or bridged NGT functions exist | **VERIFIED** |
| `bi_dashboard_rest_available()` | Logged-in + REST handler or companion | **PARTIAL** |
| `bi_rest_namespace()` | `ngc/v1` when companion; else `ngt/v1` fallback | **VERIFIED** |

---

## Shortcode ownership

| Shortcode | Theme (fallback) | Companion (target owner) | Status |
|-----------|------------------|--------------------------|--------|
| `ngc_find_tutor_form` | `bi_ngc_form_find_tutor` | Primary when active | **VERIFIED** fallback |
| `ngc_become_tutor_form` | `bi_ngc_form_become_tutor` | Primary | **VERIFIED** |
| `ngc_contact_support_form` | `bi_ngc_form_contact_support` | Primary | **VERIFIED** |
| `ngc_parent_register_child_form` | `bi_ngc_form_parent_register` | Primary | **VERIFIED** |
| `ngc_student_register_form` | `bi_ngc_form_student_register` | Primary | **VERIFIED** |
| `ngc_login_form` / `ngc_forgot_password_form` | Theme fallback | Primary | **VERIFIED** |
| `ngc_parent_dashboard` | REST shell + fallback UI | Data provider | **PARTIAL** |
| `ngc_student_dashboard` | REST shell | Data provider | **PARTIAL** |
| `ngc_tutor_dashboard` | REST shell | Data provider | **PARTIAL** |
| `ngc_admin_dashboard` | REST shell | Data provider | **PARTIAL** |

**Dedupe rule:** Theme registers fallbacks only when shortcode missing (`bi_ngc_register_fallback_shortcodes`). Companion must not duplicate theme page sync or RBAC.

---

## REST API boundary

### Theme-owned (verified in repo)

| Namespace | Routes | Purpose |
|-----------|--------|---------|
| `bi/v1` | `/openwa/webhook`, `/openwa/status`, `/openwa/send` | WhatsApp integration |

### Companion-owned (documented target, not in workspace)

| Namespace | Routes (from canonical NGT inventory) | Purpose |
|-----------|---------------------------------------|---------|
| `ngc/v1` | Dashboard, tutors, ratings, chat, workflows, onboarding | **PARTIAL** — scaffold referenced in registry |
| `ngt/v1` | 22 routes (legacy canonical theme) | **PARTIAL** — alias/deprecate toward `ngc/v1` |

### Dashboard REST paths (theme client)

| Dashboard | REST path | Config source |
|-----------|-----------|---------------|
| Parent | `/dashboard/student` | `bi_dashboard_rest_path()` |
| Student | `/dashboard/student` | Same (parent views child data) |
| Tutor | `/dashboard/tutor` | — |
| Admin | `/dashboard/admin` | — |

Client: `assets/js/dashboard-rest.js` — fetches when shortcodes absent.

---

## Data layer (companion scope)

From canonical NGT theme inventory (`COMPARE-DIFFERENCES.md` §4–6). **NOT in BeyondInfinity theme.**

### Custom tables (6)

| Table | Purpose |
|-------|---------|
| `{prefix}ngt_earnings` | Tutor payouts per booking |
| `{prefix}ngt_ratings` | Post-session ratings |
| `{prefix}ngt_payouts` | Period payout batches |
| `{prefix}ngt_referrals` | Referral rewards |
| `{prefix}ngt_session_logs` | Attendance / notes |
| `{prefix}ngt_chat_messages` | In-platform chat |

### CPTs & taxonomies

| Slug | Notes |
|------|-------|
| `tutors`, `testimonials`, `resources` | Theme reads via `inc/tutor-data.php` (CPT or demo) |
| `subject`, `province`, `curriculum`, `grade`, `format` | Taxonomies on tutors/resources |

### Theme `wp_options` only (verified)

| Option | Purpose |
|--------|---------|
| `ngc_form_queue` | Fallback form queue |
| `bi_rtm_queue` | Workflow staff messages |
| `bi_workflow_log` | Workflow audit |
| `bi_openwa_inbox` | WhatsApp inbound |

---

## Role alignment gap

| Role | Theme (`inc/roles.php`) | Canonical NGT | Action |
|------|----------------------|---------------|--------|
| `parent` | `read` only | `parent_guardian` + caps | **PARTIAL** — dedupe on companion install |
| `parent_guardian` | `read` only | Full caps | **PARTIAL** |
| `tutor` | `read` only | + `ngt_view_earnings`, etc. | **PARTIAL** |
| `student` | `read` only | + `ngt_book_sessions` | **PARTIAL** |
| `ngt_tutor` | Resolved to `tutor` via `bi_workflow_role_aliases()` | Workflow pack `add_user_role` | **VERIFIED** (alias) |

---

## Integration plugins (companion scope)

Amelia, FluentCRM, FluentSupport, WooCommerce, MasterStudy, GamiPress, AutomatorWP — hooks belong in companion, not theme. Theme retains:

- Woo `order_status_completed` → workflow dispatch (**PARTIAL**)
- Workflow pack contracts for `amelia.booking.created`, etc. (**PARTIAL** — external emitter)

---

## Deployment checklist (companion required for production)

1. Install `nextgencompanion` at `wp-content/plugins/nextgencompanion/`
2. Verify `NGC_VERSION` defined; `NGC_Plugin` class loads
3. Confirm all 11 `ngc_*` shortcodes register (theme fallbacks should defer)
4. Run dashboard UAT per role against `ngc/v1` endpoints
5. Migrate canonical NGT tables if upgrading from full `nextgen-tutors` theme
6. Resolve role/capability overlap (`parent` vs `parent_guardian`, `tutor` caps)

**Label:** NOT PRODUCTION-VERIFIED until live WordPress + companion UAT complete.

---

## Code anchors (theme side)

| File | Responsibility |
|------|----------------|
| `inc/companion.php` | Detection, REST namespace, dashboard config |
| `inc/shortcodes-fallback.php` | Form/dashboard fallbacks |
| `assets/js/dashboard-rest.js` | REST hydration client |
| `inc/tutor-data.php` | Tutor roster (CPT or demo) |
| `COMPARE-DIFFERENCES.md` | Full canonical vs BI diff |
