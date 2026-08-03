# Implementation dashboard → solution gap map

Source: `nextgentutors-implementation-dashboard.html` (deployment / workflow blueprint).  
Stack in this repo: **BeyondInfinity theme** + **NextGenTutors-Companion** (not the legacy `nextgen-tutors-theme` / `nextgen-tutors-platform` zip names in the HTML).

Generated: 2026-08-03 · Companion **1.9.9**

## Critical user journey (P0)

| HTML capability | Solution status | Owner |
|---|---|---|
| Live tutor lesson via video (student ↔ tutor) | **IMPLEMENTED** | `NGC_Jitsi_Meeting_Adapter`, `NGC_Meetings`, booking confirm → `join_url` in meta |
| Dashboard Join CTA | **IMPLEMENTED** | `format_session_row.joinUrl` + `dashboard-rest.js` “Join audio + video lesson” |
| REST join for authorized parties | **IMPLEMENTED** | `GET/POST ngc/v1/bookings/{id}/join` |
| Reminder email with join link | **IMPLEMENTED** | WF-03 templates `{{join_url}}` + reminder sender ensures meeting |
| Zoom/Teams branded provider | **LIMITATION** | Public Jitsi (`meet.jit.si` or `ngc_jitsi_base_url`); Zoom OAuth not required for A/V |

## HTML “CRITICAL (6)” gaps — remapped

| HTML item | This solution |
|---|---|
| nextgen-tutors-theme not installed | **Superseded** by `NextGenTutors-BeyondInfinity` |
| nextgen-tutors-platform plugin missing | **Superseded** by `NextGenTutors-Companion` |
| 5 custom DB tables | **Implemented** as Companion `ngc_*` schema (`NGC_Database`) |
| AutomatorWP inactive | Present via Companion AutomatorWP bridge / workflow specs (env-dependent) |
| Amelia Booking | Present via `NGC_Amelia*` adapters + bootstrap |
| MasterStudy LMS | Present via `NGC_Masterstudy_Adapter` / LMS bridge |

## Blueprint phases (student journey)

| Phase | HTML expectation | Status |
|---|---|---|
| Discovery | Browse tutors, filters, reviews | Implemented (marketplace / CPT) |
| Booking | Availability, payment, confirmation | Implemented (bookings + PayFast sandbox path) |
| Pre-session | Reminder + join link | Implemented (reminders + Jitsi URL) |
| During session | Live A/V lesson | **Implemented (Jitsi)** |
| Post-session | Rating, invoice, progress | Implemented (reviews/earnings/invoices; LMS recording optional) |
| Ongoing | Materials, rebook | Partial (LMS when MasterStudy active) |

## Still partial / external

- Elementor template pack named in HTML → BeyondInfinity page composer / section CMS instead.
- Ultimate Member / Capability Manager → Companion roles + NGC capabilities.
- Session recording into LMS → not auto-wired from Jitsi (optional future).
- Production Zoom/Teams SSO → optional; Jitsi covers audio+video join now.

## Smoke checklist for lesson join

1. Confirm a booking (`NGC_Bookings::transition(..., 'confirmed')` or paid order).
2. Student dashboard next-session hero shows **Join audio + video lesson**.
3. Tutor dashboard same booking shows Join CTA.
4. `GET /wp-json/ngc/v1/bookings/{id}/join` (logged in as student/tutor) returns `join_url` on `meet.jit.si` (or configured base).
5. Opening the URL starts a Jitsi room with mic/camera controls.
