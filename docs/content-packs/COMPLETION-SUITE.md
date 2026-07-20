# NextGen Completion Suite

Operational MVP pages for progress reports, lesson notes, matching queue, support escalations, payouts, and learning resources.

| Field | Value |
|-------|--------|
| **Plugin slug** | `nextgen-completion-suite` |
| **Version** | 1.0.0 |
| **Source zip** | `content/nextgen-completion-suite-v1.0.zip` |
| **Extracted** | `content/_extracted/nextgen-completion-suite/` |

---

## Purpose

Fills the **front-end operational UI gap** for staff and tutors:

- Submit progress reports and lesson notes via forms
- View matching queue, support escalations, payout queue
- Browse learning resources
- Wow-factor homepage sections shortcode

Completion Suite creates CPT records and dispatches Companion workflows. Long-term, forms should migrate to Companion REST + NGT design system shortcodes; CPT structure remains the data contract.

---

## Installation

1. Extract zip to `wp-content/plugins/nextgen-completion-suite/`
2. Activate plugin
3. WP Admin → **NextGen Completion** → **Run / Repair Setup**
4. Optional: **Import Bundled Workflow JSON**

Docker: auto-mounted from `content/_extracted/nextgen-completion-suite/`.

---

## Custom post types

| CPT | Purpose |
|-----|---------|
| `ngt_match` | Tutor match records |
| `ngt_note` | Lesson notes |
| `ngt_report` | Progress reports |
| `ngt_support_log` | Support escalation logs |
| `ngt_payout` | Tutor payout queue items |
| `ngt_resource` | Learning resources |
| `ngt_workflow_log` | Imported workflow JSON log |

---

## Pages created by setup

| Slug | Shortcode | Purpose |
|------|-----------|---------|
| `/lesson-notes/` | `[ngt_lesson_notes]` | Tutor lesson notes form + list |
| `/progress-reports/` | `[ngt_progress_reports]` | Progress report form + list |
| `/tutor-matching/` | `[ngt_matching_queue]` | Matching queue UI |
| `/support-escalation/` | `[ngt_support_escalation]` | Support escalation tracker |
| `/tutor-payouts/` | `[ngt_payout_queue]` | Payout queue form + list |
| `/learning-resources/` | `[ngt_learning_resources]` | Resource library |
| `/nextgen-wow-home/` | `[ngt_wow_home]` | Marketing hero sections |

---

## REST API

| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `ngt/v1/create` | POST | Logged-in | Create operational CPT record |

Payload:

```json
{
  "type": "progress_report|lesson_note|payout|support|match",
  "title": "Weekly progress — Grade 10 Maths",
  "content": "Student improved algebra scores...",
  "student": "Jane Doe",
  "tutor": "John Tutor"
}
```

---

## Workflow integration

When CPTs are created, `NGC_Content_Pack_Bridge` dispatches:

| CPT | Companion event | Workflow pack trigger |
|-----|-----------------|----------------------|
| `ngt_report` | `progress_report.submitted` | `ngt.progress_report.submitted` |
| `ngt_note` | `lesson_note.created` | `ngt.lesson_note.created` |
| `ngt_payout` | `payout.calculated` | `ngt.payout.calculated` |
| `ngt_resource` | `resource.recommended` | `ngt.resource.recommended` |

Actions: RTM staff messages, admin email (via `content/nextgen-workflow-pack.json`).

---

## Workflow JSON (completion catalog)

Location: `workflows/` (6 files)

| ID | Name | Trigger |
|----|------|---------|
| 011 | Progress Report Submitted | `progress_report_created` |
| 012 | Tutor Lesson Note Created | `lesson_note_created` |
| 013 | Support Escalated | `support_escalated` |
| 014 | Tutor Payout Ready | `lesson_confirmed_paid` |
| 015 | Parent Review Created | `parent_review_created` |
| 016 | Learning Resource Recommended | `weakness_identified` |

Imported into Companion spec store via **Integrate Specs → Import content-pack catalog**.

---

## Styling (NGT theme bridge)

- Wrapper class: `ngt-suite bi-ngt-skin`
- Enqueues `assets/ngt/css/tokens.css` from theme when `BI_URI` defined
- Navy/lime design system, pill buttons, card layout

CSS: `assets/ngt-suite.css`

---

## Seeded learning resources

Setup creates placeholder resources (Mathematics CAPS, Physical Science, Accounting, English, Matric papers). Replace with production content via admin or Section CMS.

---

## Admin

**WP Admin → NextGen Completion**

- Run / Repair Setup
- Import Bundled Workflow JSON
- Links to all operational pages
- Workflow Logs submenu (`ngt_workflow_log`)

---

## Related docs

- [COMMAND-CENTER.md](COMMAND-CENTER.md) — Mission Control + RTM
- [INTEGRATION-CATALOG.md](../workflows/INTEGRATION-CATALOG.md)
- [tutorials/tutor/manual.md](../tutorials/tutor/manual.md)
