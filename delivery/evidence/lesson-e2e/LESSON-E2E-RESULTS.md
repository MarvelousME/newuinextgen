# Lesson E2E Results

MEDIA_MODE: browser_fake_media_streams (not physical devices)

HUMAN_AUDIO_AUDIBILITY_VERIFIED: false

Product recording: UNVERIFIED (not implemented)

TEST_RANDOM_SEED=20260809

| Test | Invocation | Student | Tutor | Audio S→T | Audio T→S | Video S→T | Video T→S | Recording | DB | Auth | Result |
| ---- | ---------- | ------- | ----- | --------- | --------- | --------- | --------- | --------- | -- | ---- | ------ |
| LESSON-E2E-000 | inventory + seeded RNG | — | — | — | — | — | — | — | seed-graph | — | PASS |
| LESSON-E2E-001 | INV-01 student dashboard Join | demo.student.adult@nextgen.local | demo.tutor.online@nextgen.local | PASS_LOCAL_TRACK | PASS_LOCAL_TRACK | PASS_OR_PARTIAL | PASS_OR_PARTIAL | UNVERIFIED | PASS_SNAPSHOT | PASS | PASS_WITH_LIMITATIONS |
| LESSON-E2E-002 | INV-04 tutor dashboard Join | (same booking 154) | demo.tutor.online@nextgen.local | PASS_LOCAL_TRACK | PASS_LOCAL_TRACK | PASS_OR_PARTIAL | PASS_OR_PARTIAL | UNVERIFIED | PASS_SNAPSHOT | PASS | PASS_WITH_LIMITATIONS |
| LESSON-E2E-003 | INV-06 REST join negatives | unauth / wrong tutor | — | — | — | — | — | — | — | PASS (401/403) | PASS |
| LESSON-E2E-005 | INV-11 classroom Course Player → live meeting | demo.parent (child session 28) | demo.tutor.online | — | — | UI_CONTROL + player shell | live hop same meeting id | UNVERIFIED | seed JSON | PASS | PASS |

## Classroom headed (2026-08-09)

| Proof | Status |
| --- | --- |
| CLASSROOM-001 parent classroom + live CTA | PASS |
| CLASSROOM-002 tutor same session/meeting | PASS |
| CLASSROOM-003 REST launch `classroom_url` | PASS |
| Product recording | UNVERIFIED |

Evidence: `delivery/evidence/booking-commerce/classroom-2026-08-09T12-45-31-840Z/`


| Level | Status |
| --- | --- |
| UI_CONTROL_PRESENT | PASS |
| MEDIA_DEVICE_ACQUIRED | PASS (fake streams) |
| LOCAL_TRACK_ACTIVE | PASS |
| REMOTE_TRACK_RECEIVED | PARTIAL (live video tile count ≥ 1 on tutor view) |
| PARTICIPANT_CONNECTED | PASS (same room URL both actors) |
| RECORDING_CREATED | UNVERIFIED / not implemented |
| RECORDING_MEDIA_VALIDATED | UNVERIFIED |
| DATABASE_PERSISTENCE_VERIFIED | PARTIAL (REST booking snapshots before/after) |

## Evidence roots

`delivery/evidence/lesson-e2e/` — screenshots 01–14, webrtc/, api/, network/, console/, videos/, inventory/
