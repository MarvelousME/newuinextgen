# PLACEHOLDER-SCREEN-AUDIT

Generated: 2026-08-03  
Source of truth: Companion catalog + admin callbacks (code inspection + unit governance suite).

## Status legend

VERIFIED FUNCTIONAL | PARTIAL | UI-ONLY | BACKEND-ONLY | MOCKED | PLACEHOLDER | BROKEN | DUPLICATED | INACCESSIBLE | MISSING

## Findings (high priority)

| Menu path | Screen/page slug | Render callback | Data source | Actions | Placeholder evidence | Root cause | Required implementation | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Education → Students | `ngt-edu-students` | `NGC_Education_Admin::render_students` | `get_users(role__in)` | Search | Was `render_placeholder` | Nav scaffold only | Live directory | PARTIAL |
| Education → Student Directory | `ngt-edu-student-directory` | `NGC_Education_Admin::render_student_directory` | WP users | Search | Was placeholder | Same | Same as students | PARTIAL |
| Education → Attendance | `ngt-edu-attendance` | `NGC_Education_Admin::render_attendance` | Bookings when available | List | Was placeholder | No dedicated attendance store | Booking-derived list | PARTIAL |
| Education → Assessments | `ngt-edu-assessments` | `NGC_Education_Admin::render_assessments` | Reviews count | Informational | Was placeholder | LMS gradebook not wired | LMS bridge | PARTIAL |
| Education → Certificates | `ngt-edu-certificates` | `NGC_Education_Admin::render_certificates` | Empty actionable state | None | Was placeholder | No issuer | LMS certificates | PARTIAL |
| Education → Parents | `ngt-edu-parents` | `NGC_Education_Admin::render_parents` | WP parent roles | Search | Was placeholder | Nav scaffold | Live directory | PARTIAL |
| Education → Lessons | `ngt-edu-lessons` | `NGC_Education_Admin::render_lessons` | Bookings | List | Was placeholder | No lesson CPT | Booking list | PARTIAL |
| Education → Subjects | `ngt-edu-subjects` | `NGC_Education_Admin::render_subjects` | Taxonomy / marketplace | List | Was placeholder | Sparse taxonomy | Live subjects | PARTIAL |
| AI → Agentic Hub | `ngc-agentic-hub` | `NGC_Agentic_Admin::render_hub` | Live option counts | Nav links | Was MISSING | New module | Metrics + links | PARTIAL |
| AI → MCP Servers | `ngc-mcp-servers` | `NGC_Agentic_Admin::render_mcp` | `ngc_mcp_servers` option | Upsert | Was MISSING | New module | Registry + SSRF | PARTIAL |
| AI → A2A Agents | `ngc-a2a-agents` | `NGC_Agentic_Admin::render_a2a` | `ngc_a2a_*` options | Pin agent | Was MISSING | Gateway service separate | Pin + durable tasks | PARTIAL |
| Website → Social Connections | `ngc-social-connections` | `NGC_Agentic_Admin::render_social` | Connections option | OAuth begin | Was MISSING | Needs app credentials | OAuth only (no passwords) | PARTIAL |
| Website → Content Studio | `ngc-content-studio` | `NGC_Agentic_Admin::render_content` | Studio option | Draft/approve | Was MISSING | Sandbox publish | Full connectors | PARTIAL |
| Website → Content Calendar | `ngc-content-calendar` | `NGC_Agentic_Admin::render_calendar` | RRULE preview | Preview | Was MISSING | Durable worker TBD | Preview works | PARTIAL |
| CRM → Tutor Leads | `ngc-tutor-leads` | `NGC_Agentic_Admin::render_leads` | Leads option | Create/sync | Was MISSING | FluentCRM runtime dep | Ethical pipeline | PARTIAL |
| CRM → Lead Sources | `ngc-lead-sources` | `NGC_Agentic_Admin::render_sources` | Source policy matrix | Read-only | Was MISSING | Policy UI | Compliance matrix | VERIFIED FUNCTIONAL |

## Intentionally still incomplete (not deleted)

- Full LMS gradebook / certificate issuer screens remain PARTIAL until MasterStudy (or equivalent) is configured.
- Live Meta/X/LinkedIn token exchange requires external app credentials (`INPUTS-REQUIRED`).
- A2A task *execution* requires separate Agent Gateway + official `a2a-js` (WordPress holds pins/tasks only).

## Placeholder screens remaining (strict “coming soon” shell)

`0` under Next Gen Tutors education nav (replaced with live/partial data screens).

Other historical UI-ONLY dumps (acquisition/affiliates JSON `<pre>`) are outside this pass — track separately if still menu-visible.
