# Workflow Integration Package — Registration Workflows

**Plugin:** NextGen Companion v1.0.0+  
**Validation:** `php scripts/validate.php` — 42 PHP files, exit 0  
**Admin:** WP Admin → **Workflows** (8 screens)

Full source is in the repository paths below (not inlined).

---

## 1. File tree (created / changed)

```
nextgencompanion/
├── nextgencompanion.php                          (autoload paths, activation bootstrap)
├── WORKFLOW-INTEGRATION-PACKAGE.md
├── scripts/validate.php
├── includes/
│   ├── class-ngc-database.php                    (+ ngc_workflow_runs table)
│   ├── class-ngc-roles.php                       (+ tutor_applicant role)
│   ├── class-ngc-registration.php              (orchestrator hooks)
│   ├── class-ngc-tutor-lifecycle.php             (orchestrator hooks)
│   ├── class-ngc-workflows.php                   (skip duplicate form dispatch)
│   ├── class-ngc-plugin.php                      (bootstrap orchestrator + admin)
│   ├── adapters/
│   │   ├── interface-ngc-integration-adapter.php
│   │   ├── class-ngc-adapter-base.php
│   │   ├── class-ngc-fluentcrm-adapter.php       NEW
│   │   ├── class-ngc-amelia-adapter.php          NEW
│   │   ├── class-ngc-masterstudy-adapter.php     NEW
│   │   ├── class-ngc-email-adapter.php           NEW
│   │   ├── class-ngc-audit-adapter.php           NEW
│   │   └── class-ngc-verification-adapter.php    NEW
│   ├── workflows/
│   │   ├── class-ngc-workflow-orchestrator.php   NEW — 7 workflows
│   │   ├── class-ngc-workflow-email-templates.php NEW — 20 templates
│   │   └── class-ngc-workflow-retry-queue.php    NEW
│   ├── admin/
│   │   └── class-ngc-workflow-admin.php          NEW — 8 admin screens
│   └── integrations/
│       └── class-ngc-fluentcrm.php               (shim only)
```

---

## 2. Workflow trigger matrix

| Trigger | Source Form/Event | Actor | FluentCRM | Amelia | MasterStudy | Email | Audit Event |
|---|---|---|---|---|---|---|---|
| TUTOR_REGISTERED | `become_tutor` POST | Applicant | List Tutor + tag Tutor Applicant | — | — | tutor_registration_received + admin_new_tutor_application | TUTOR_REGISTERED |
| TUTOR_APPROVED | Admin approve / REST | Admin | List Tutor + tag Tutor Approved, detach Applicant | create_employee | create_instructor | tutor_approved + onboarding + admin | TUTOR_APPROVED |
| TUTOR_REJECTED | Admin reject / REST | Admin | tag Tutor Rejected | — | — | not_approved + resubmission_invite | TUTOR_REJECTED |
| TUTOR_RESUBMITTED | REST resubmit | Tutor/Admin | tag Tutor Resubmitted | — | — | resubmission_received + admin review | TUTOR_RESUBMITTED |
| PARENT_REGISTERED | `parent_register` POST | Parent | List Parent + tag Parent Registered | — | — | parent_welcome + admin | PARENT_REGISTERED |
| STUDENT_REGISTERED | `student_register` POST | Student | List Student + tags | — | create_student | student_welcome + parent + admin | STUDENT_REGISTERED |
| CHILD_REGISTERED | `child_name` on parent form | Parent | tag Child Learner (if child email) | — | optional LMS | child_learner_created + admin | CHILD_REGISTERED |
| CRM_CONTACT_CREATED | CRM upsert | System | createOrUpdate | — | — | crm_sync_failed on error | CRM_CONTACT_CREATED |
| CRM_CONTACT_UPDATED | CRM upsert | System | createOrUpdate | — | — | — | CRM_CONTACT_UPDATED |
| AMELIA_EMPLOYEE_CREATED | TUTOR_APPROVED | System | — | POST /users/providers | — | amelia_sync_failed | AMELIA_EMPLOYEE_CREATED |
| MASTERSTUDY_INSTRUCTOR_CREATED | TUTOR_APPROVED | System | — | — | stm_lms_instructor role | masterstudy_sync_failed | MASTERSTUDY_INSTRUCTOR_CREATED |
| MASTERSTUDY_STUDENT_CREATED | STUDENT_REGISTERED | System | — | — | stm_lms_student role | masterstudy_sync_failed | MASTERSTUDY_STUDENT_CREATED |
| EMAIL_SENT | All workflows | System | — | — | — | wp_mail | EMAIL_SENT |
| EMAIL_FAILED | All workflows | System | — | — | — | admin alert | EMAIL_FAILED |
| WORKFLOW_FAILED | Orchestrator | System | — | — | — | workflow_verification_failed | workflow_runs.status=failed |

---

## 3. Plugin adapter matrix

| Adapter | Class | is_available() | verify() | create_or_update() |
|---|---|---|---|---|
| FluentCRM | `NGC_Fluentcrm_Adapter` | `FluentCrmApi` + Subscriber model | Lists + tags bootstrap | `createOrUpdate` + tags/lists/detach |
| Amelia | `NGC_Amelia_Adapter` | `AMELIA_VERSION` | API key configured | `create_employee` via REST |
| MasterStudy | `NGC_Masterstudy_Adapter` | `STM_LMS_VERSION` | Plugin active | `create_instructor` / `create_student` |
| Email | `NGC_Email_Adapter` | `wp_mail` | 20 templates exist | `send_template` |
| Audit | `NGC_Audit_Adapter` | audit table | table exists | `log_event` |
| Verification | `NGC_Verification_Adapter` | always | all adapters + templates | `verify_workflow` |

Each adapter implements: `get_existing()`, `map_payload()`, `handle_error()`, `audit_result()`.

---

## 4. Email template matrix (20 templates)

| # | Key | Subject trigger | Recipient |
|---|---|---|---|
| 1 | tutor_registration_received | WF-TUTOR-REGISTERED | Tutor |
| 2 | admin_new_tutor_application | WF-TUTOR-REGISTERED | Admin |
| 3 | tutor_approved | WF-TUTOR-APPROVED | Tutor |
| 4 | tutor_onboarding_next_steps | WF-TUTOR-APPROVED | Tutor |
| 5 | admin_tutor_approval_completed | WF-TUTOR-APPROVED | Admin |
| 6 | tutor_application_not_approved | WF-TUTOR-REJECTED | Tutor |
| 7 | tutor_resubmission_invitation | WF-TUTOR-REJECTED | Tutor |
| 8 | tutor_resubmission_received | WF-TUTOR-RESUBMITTED | Tutor |
| 9 | admin_tutor_resubmission_review | WF-TUTOR-RESUBMITTED | Admin |
| 10 | parent_welcome | WF-PARENT-REGISTERED | Parent |
| 11 | admin_new_parent_registration | WF-PARENT-REGISTERED | Admin |
| 12 | parent_student_profile_created | WF-STUDENT-REGISTERED | Parent |
| 13 | student_welcome | WF-STUDENT-REGISTERED | Student |
| 14 | child_learner_profile_created | WF-CHILD-REGISTERED | Parent |
| 15 | admin_new_student_registration | WF-STUDENT-REGISTERED | Admin |
| 16 | admin_child_learner_created | WF-CHILD-REGISTERED | Admin |
| 17 | crm_sync_failed | SYSTEM | Admin |
| 18 | amelia_sync_failed | SYSTEM | Admin |
| 19 | masterstudy_sync_failed | SYSTEM | Admin |
| 20 | workflow_verification_failed | SYSTEM | Admin |

Merge fields: `{{first_name}}`, `{{last_name}}`, `{{email}}`, `{{phone}}`, `{{role}}`, `{{workflow_status}}`, `{{tutor_status}}`, `{{student_name}}`, `{{parent_name}}`, `{{subjects}}`, `{{grades}}`, `{{location}}`, `{{approval_status}}`, `{{rejection_reason}}`, `{{dashboard_url}}`, `{{login_url}}`, `{{support_email}}`, `{{site_name}}`

**Test send:** Workflows → Email Templates → Test send (admin email).

---

## 5. Admin screens

| # | Screen | Slug | Shows |
|---|---|---|---|
| 1 | Workflow Trigger Manager | `ngc-workflow-triggers` | Active workflows, run stats |
| 2 | FluentCRM Integration Status | `ngc-workflow-fluentcrm` | verify + bootstrap lists/tags |
| 3 | Amelia Integration Status | `ngc-workflow-amelia` | verify + API key settings |
| 4 | MasterStudy Integration Status | `ngc-workflow-masterstudy` | verify |
| 5 | Email Template Manager | `ngc-workflow-emails` | 20 templates + test send |
| 6 | Workflow Logs | `ngc-workflow-logs` | `ngc_workflow_runs` table |
| 7 | Failed Workflow Retry Queue | `ngc-workflow-retries` | retry action |
| 8 | Verification Dashboard | `ngc-workflow-verification` | full adapter report JSON |

---

## 6. Verification checklist

- [ ] Activate plugin — `ngc_workflow_runs` table created
- [ ] Role `tutor_applicant` exists
- [ ] Workflows → Verification — email + audit VERIFIED
- [ ] With FluentCRM: bootstrap lists/tags — all green
- [ ] Submit `become_tutor` — TUTOR_REGISTERED log + emails + CRM contact
- [ ] Approve tutor — TUTOR_APPROVED + Amelia (if API key) + MasterStudy instructor
- [ ] Reject tutor — emails + CRM tag Tutor Rejected
- [ ] Parent register with child — PARENT_REGISTERED + CHILD_REGISTERED
- [ ] Student register — STUDENT_REGISTERED + LMS student role
- [ ] Workflow Logs show completed runs
- [ ] Simulate CRM failure — admin receives crm_sync_failed
- [ ] Retry queue processes hourly cron

---

## 7. Test checklist

```bash
php nextgencompanion/scripts/validate.php
```

| Test | Expected |
|---|---|
| `become_tutor` form | User `tutor_applicant`, application row, WF-TUTOR-REGISTERED |
| REST approve tutor | WF-TUTOR-APPROVED, tutor role, marketplace publish |
| REST reject tutor | WF-TUTOR-REJECTED, draft marketplace |
| `parent_register` | parent user, WF-PARENT-REGISTERED + CHILD if child_name |
| `student_register` | student user, WF-STUDENT-REGISTERED |
| Email test send | Admin receives rendered template |
| Amelia without API key | PARTIAL — no fatal, amelia_sync_failed email |
| FluentCRM absent | PARTIAL — CRM skipped, other steps continue |

---

## 8. Final status table

| Workflow | FluentCRM | Amelia | MasterStudy | Email | Audit | Final Status |
|---|---|---|---|---|---|---|
| WF-TUTOR-REGISTERED | **CODE VERIFIED** — `createOrUpdate` when plugin active | N/A | N/A | **CODE VERIFIED** — 2 templates | **CODE VERIFIED** | **PARTIAL** until FluentCRM installed on host |
| WF-TUTOR-APPROVED | **CODE VERIFIED** | **PARTIAL** — needs Amelia Elite + API key | **CODE VERIFIED** — role-based when STM active | **CODE VERIFIED** — 3 templates | **CODE VERIFIED** | **PARTIAL** — Amelia blocked without API key |
| WF-TUTOR-REJECTED | **CODE VERIFIED** | N/A (by design) | N/A (by design) | **CODE VERIFIED** | **CODE VERIFIED** | **CODE VERIFIED** |
| WF-TUTOR-RESUBMITTED | **CODE VERIFIED** | N/A | N/A | **CODE VERIFIED** | **CODE VERIFIED** | **CODE VERIFIED** |
| WF-PARENT-REGISTERED | **CODE VERIFIED** | N/A | N/A | **CODE VERIFIED** | **CODE VERIFIED** | **PARTIAL** until FluentCRM on host |
| WF-STUDENT-REGISTERED | **CODE VERIFIED** | N/A | **CODE VERIFIED** | **CODE VERIFIED** | **CODE VERIFIED** | **PARTIAL** until MasterStudy on host |
| WF-CHILD-REGISTERED | **CODE VERIFIED** (email optional) | N/A | **OPTIONAL** | **CODE VERIFIED** | **CODE VERIFIED** | **CODE VERIFIED** |

**Production VERIFIED** = install FluentCRM + MasterStudy + Amelia (with API key) on WordPress staging and run §7 tests.

### Blocked dependencies

| Dependency | Missing in workspace | Safe fallback |
|---|---|---|
| FluentCRM plugin | Yes — no plugin source | Admin notice; CRM steps return PARTIAL; `CRM_SKIPPED_NO_EMAIL` when no email |
| Amelia plugin + Elite API key | Yes | Employee step skipped; `amelia_sync_failed` admin email; tutor approval continues |
| MasterStudy LMS | Yes | Student/instructor steps skipped; `masterstudy_sync_failed` admin email |

---

## 9. Enable full functionality

1. Install **FluentCRM** → Workflows → FluentCRM Status → **Bootstrap lists & tags**
2. Install **MasterStudy LMS** → student/instructor roles provision automatically
3. Install **Amelia** (Elite) → Workflows → Amelia → enter API key + default service ID
4. Submit test registrations on staging
5. Review **Workflows → Workflow Logs** and **Verification**
