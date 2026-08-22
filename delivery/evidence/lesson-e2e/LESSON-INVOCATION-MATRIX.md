# Lesson Invocation Matrix

**Updated:** 2026-08-09  
**Environment:** local Docker WordPress `http://localhost:8890`  
**Meeting technology:** Jitsi Meet (`NGC_Meetings` + `NGC_Jitsi_Meeting_Adapter`)  
**Learning shell:** `NGC_Session_Classroom` (`?ngt_classroom={session_id}`) → MasterStudy Course Player iframe + Enter live meeting

## Discovery summary

| Mechanism | Runtime | Distinct? |
| --- | --- | --- |
| Dashboard Join → `POST /ngc/v1/sessions/{id}/launch` | Classroom URL (Course Player → live meeting) | **Authoritative** orchestration path |
| Dashboard Join legacy `POST /bookings/{id}/join` | Booking meeting URL (Jitsi) | Alias / fallback when no session row |
| Chat `#chat-video-btn` | Ad-hoc `meet.jit.si/NextGenTutors-Room-{roomId}` | **Different** — not booking-bound |
| Zoom / custom WebRTC / MediaRecorder product recording | Not implemented | Unsupported |

## Matrix

| ID | Invocation mechanism | Student entry | Tutor entry | Provider | UI/API | Supported | E2E required |
| --- | --- | --- | --- | --- | --- | --- | --- |
| INV-01 | Student Dashboard → Next session → Join → session launch → classroom | Yes | N/A | Classroom + Jitsi | UI | Yes | Yes (full AV) |
| INV-02 | Student Dashboard → Recent sessions → Join | Yes | N/A | Same as INV-01 | UI | Yes | Entry validation (alias) |
| INV-03 | Parent Dashboard → Join | Parent | N/A | Same | UI | Yes | Yes (entry + same room) |
| INV-04 | Tutor Dashboard → Join/Start | N/A | Yes | Same | UI | Yes | Yes (full AV) |
| INV-05 | Tutor Recent → Join | N/A | Yes | Same | UI | Yes | Alias of INV-04 |
| INV-06 | REST `/ngc/v1/sessions/{id}/launch` | Yes | Yes | Classroom + MS + Jitsi | API | Yes | Yes (auth + window + URLs) |
| INV-06b | REST `/ngc/v1/bookings/{id}/join` | Yes | Yes | Jitsi | API | Yes | Yes (legacy alias) |
| INV-07 | Direct meeting URL | Yes | Yes | Jitsi | Deep link | Yes | Covered by live hop |
| INV-08 | Chat Video call | Ad-hoc | Ad-hoc | Jitsi | UI | Partial | Distinct; not booking E2E |
| INV-09 | Email join templates | Template | Template | Jitsi | Email | Partial | Entry if URL present |
| INV-10 | Amelia appointment UI join | No | No | N/A | UI | No | N/A |
| INV-11 | MasterStudy via classroom Course Player | Yes | Yes | MS + Jitsi | UI | Yes | Yes (player present + live hop) |
| INV-12 | Product session recording | No | No | N/A | UI | **Not implemented** | UNVERIFIED |

## Equivalence classes

1. **Orchestrated classroom** — INV-01…05, INV-06, INV-11 — `ensure_provisioned` + `authorize_launch` → `NGC_Session_Classroom` → optional MS player → Jitsi meeting.
2. **Legacy booking join** — INV-06b, INV-07 — same Jitsi room meta when session absent.
3. **Chat ad-hoc** — INV-08.
4. **Unsupported recording** — INV-12.

## Defects / remediations

| ID | Defect | Fix |
| --- | --- | --- |
| DEF-TUTOR-DASH-001 | Tutor dashboard empty sessions | Populated via REST + JS |
| DEF-LAUNCH-MS-001 | Join skipped Course Player | `launch_url` → classroom shell with MS iframe + live meeting CTA |
| DEF-READY-UNPAID-001 | Unpaid sessions marked ready | Orchestrator gates READY/meeting/MS on paid |
| DEF-REC-001 | No product recording | Remains UNVERIFIED — do not invent |
