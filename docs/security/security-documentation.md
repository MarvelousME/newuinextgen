# Security Documentation

## Security Control Matrix

| Domain | Implementation | Evidence | Status |
|---|---|---|---|
| Authentication | WordPress native auth/session | login forms + WP auth | VERIFIED |
| Authorization/RBAC | custom `ngc_*` caps + role maps | `NGC_Roles`, security guards | VERIFIED |
| Nonce protection | form submit + calendar slot CTA + admin actions | shortcode handlers, admin checks | VERIFIED |
| CSRF mitigation | nonce + capability checks | admin-post/admin-ajax/REST | VERIFIED |
| Input sanitization | `sanitize_*`, `sanitize_key`, `wp_unslash` handling | REST/services/forms | VERIFIED |
| Output escaping | `esc_html`, `esc_attr`, `esc_url` in templates | theme/plugin render layers | VERIFIED |
| SQL protection | `wpdb->prepare` for dynamic queries | repository/services | VERIFIED |
| API security | permission callbacks + auth scoping | `NGC_Rest*` classes | VERIFIED |
| Session management | WP session model + tracked session IDs | tracking service | PARTIAL |
| Cookie policy | consent-aware cookies, secure/samesite | platform tracking | VERIFIED |
| Privacy/POPIA posture | public data minimization + consent logging | public calendar + consent logs | VERIFIED |
| Audit logging | action/object/context logging | `NGC_Audit` | VERIFIED |

## Public Data Protection Rules (Implemented)

- Public tutor calendars expose:
  - date/time slot
  - status/public label
  - delivery mode
- Public tutor calendars do **not** expose:
  - student/parent names/emails/phones
  - payment/invoice details
  - private notes/internal context

## Known Security Gaps

| Area | Gap | Status |
|---|---|---|
| External plugin hardening | depends on plugin security posture/version | PARTIAL |
| Centralized secret management | API keys stored via WP options | PARTIAL |
| WAF/IDS/SIEM | infrastructure outside repository | NOT VERIFIED |

