# Appendix A — RBAC Matrix

**Theme:** BeyondInfinity v1.4.6  
**Evidence sources:** `inc/roles.php`, `inc/security.php`, `inc/admin.php`, `content/page-map.json`

---

## Custom WordPress Roles (theme-registered)

Registered on `after_switch_theme` via `bi_register_roles()`. All custom roles receive only the `read` capability.

| Role slug | Display name | Capabilities | Evidence |
|-----------|--------------|--------------|----------|
| `parent` | Parent | `read` | `inc/roles.php` |
| `parent_guardian` | Parent/Guardian | `read` | `inc/roles.php` |
| `tutor` | Tutor | `read` | `inc/roles.php` |
| `student` | Student | `read` | `inc/roles.php` |

**Note:** Workflow pack references `ngt_tutor` role on tutor approval; theme resolves to `tutor` via `bi_workflow_role_aliases()` when `ngt_tutor` is not registered. **VERIFIED**

---

## Dashboard Page Access (`bi_dashboard_page_map`)

Enforced on `template_redirect` by `bi_protect_dashboard_pages()`. Unauthenticated users redirect to `/login?redirect_to=…`. Wrong role redirects to home.

| Page slug | Allowed roles | Login required | Evidence |
|-----------|---------------|----------------|----------|
| `parent-dashboard` | `parent`, `parent_guardian`, `administrator` | Yes | `inc/security.php` |
| `student-dashboard` | `student`, `subscriber`, `administrator` | Yes | `inc/security.php` |
| `tutor-dashboard` | `tutor`, `administrator` | Yes | `inc/security.php` |
| `admin-dashboard` | `administrator` | Yes | `inc/security.php` |
| `onboarding` | `administrator` | Yes | `inc/security.php` |
| `wordpress-setup` | `administrator` | Yes | `inc/security.php` |

All other pages in `content/page-map.json` are public (no role gate in theme security layer).

---

## Post-Login Redirect (`bi_login_redirect`)

Priority: explicit `redirect_to` (internal only) → role-based default.

| Role(s) | Default destination |
|---------|---------------------|
| Any (with valid `redirect_to`) | Validated internal URL |
| `administrator` | `/admin-dashboard` |
| `tutor` | `/tutor-dashboard` |
| `parent`, `parent_guardian` | `/parent-dashboard` |
| `student`, `subscriber` | `/student-dashboard` |
| Other | WordPress default `$redirect_to` |

Open-redirect protection: `bi_validate_internal_redirect()` + `wp_validate_redirect()`.

---

## Admin / Capability Matrix

| Action | Required capability / role | Evidence |
|--------|---------------------------|----------|
| Theme options / customizer | `edit_theme_options` (default WP) | `inc/config/*` |
| Admin dashboard tools | `administrator` + page gate | `inc/security.php` |
| OpenWA status/send (admin UI) | `manage_options` | `inc/openwa.php`, `inc/admin.php` |
| Form submission (public) | None (nonce only) | `inc/shortcodes-fallback.php` |
| REST OpenWA webhook | Webhook secret token query param | `inc/openwa.php` |
| REST dashboard data (`ngc/v1`) | Companion plugin (not in theme) | **PARTIAL** |

---

## Persona → Role Mapping

| Persona | WP role(s) | Dashboard | Public actions |
|---------|------------|-----------|----------------|
| Visitor | — | — | Browse, submit forms |
| Parent | `parent`, `parent_guardian` | `parent-dashboard` | Register, find tutor |
| Student | `student`, `subscriber` | `student-dashboard` | Self-register form |
| Tutor (approved) | `tutor` (+ `ngt_tutor` external) | `tutor-dashboard` | Become-tutor form (pre-approval) |
| Admin | `administrator` | `admin-dashboard`, `onboarding`, `wordpress-setup` | Sync, monitoring |
| Operations / Finance / Support | No dedicated WP role | RTM room consumers (external) | **PARTIAL** |

---

## RBAC Gaps (from code review)

| Gap | Status |
|-----|--------|
| Tutor approval/rejection admin UI in theme | NOT VERIFIED |
| Fine-grained capabilities per dashboard widget | NOT VERIFIED |
| Multisite super-admin flows | NOT VERIFIED |
| Dedicated support/finance staff roles | NOT VERIFIED |
| Tutor verified meta enforcement on marketplace | PARTIAL (`tutor_verified` meta reads in companion/tutor-data) |
