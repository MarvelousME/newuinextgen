# Tutor Profile Public Calendar Implementation Package

## 1) File tree (changed/created)

```txt
nextgencompanion/
├── demo/
│   └── demo_tutor_calendar.json
├── includes/
│   ├── class-ngc-plugin.php
│   ├── class-ngc-verification.php
│   ├── class-ngc-bookings.php
│   ├── class-ngc-platform-demo.php
│   ├── class-ngc-tutor-availability-repository.php                 NEW
│   ├── class-ngc-tutor-calendar-service.php                        NEW
│   ├── shortcodes/class-ngc-shortcodes.php
│   ├── rest/class-ngc-rest-tutor-calendar.php                      NEW
│   ├── adapters/class-ngc-amelia-availability-adapter.php          NEW
│   └── adapters/class-ngc-internal-booking-adapter.php             NEW
└── scripts/validate.php

beyondinfinity/
├── inc/template-tags.php
├── style.css
└── templates/tutor/calendar.php                                    NEW
```

## 2) REST endpoint definition

- **Endpoint**: `GET /wp-json/nextgen/v1/tutors/{tutor_id}/calendar`
- **Class**: `NGC_Rest_Tutor_Calendar`
- **File**: `nextgencompanion/includes/rest/class-ngc-rest-tutor-calendar.php`
- **Query params**: `from`, `to`, `subject`, `delivery_mode`, `timezone`, `demo`
- **Response envelope**:
  - `success`
  - `data.tutor_id`, `data.user_id`, `data.amelia_employee_id`, `data.timezone`, `data.slots[]`
  - `meta.source` (`amelia|internal|demo`), `meta.retrieved_at`

## 3) Shortcode implementation

- **Shortcode**: `[nextgen_tutor_calendar tutor_id="123"]`
- **Optional attrs**:
  - `view="month|week|day"`
  - `show_filters="yes|no"`
  - `subject=""`
  - `delivery_mode=""`
  - `timezone="Africa/Johannesburg"`
- **File**: `nextgencompanion/includes/shortcodes/class-ngc-shortcodes.php`
- **Behavior**:
  - validates tutor ID
  - resolves public-safe calendar through `NGC_Tutor_Calendar_Service`
  - renders legend + slot cards
  - available slots show CTA (`Book this time`)
  - booked/pending/unavailable show public labels only
  - click audit event via AJAX (`CALENDAR_SLOT_SELECTED`)

## 4) Amelia adapter implementation

- **Class**: `NGC_Amelia_Availability_Adapter`
- **File**: `nextgencompanion/includes/adapters/class-ngc-amelia-availability-adapter.php`
- **Capabilities**:
  - verifies Amelia/API key availability
  - reads appointments from Amelia DB tables when present
  - falls back to Amelia API endpoint calls if table read fails
  - maps raw records to public slot status (`booked|pending|cancelled|unavailable`)

## 5) Internal booking fallback implementation

- **Class**: `NGC_Internal_Booking_Adapter`
- **File**: `nextgencompanion/includes/adapters/class-ngc-internal-booking-adapter.php`
- **Capabilities**:
  - reads internal `ngc_bookings`
  - maps booking states to public slot statuses
  - returns privacy-safe slot data only

## 6) Tutor calendar service

- **Class**: `NGC_Tutor_Calendar_Service`
- **File**: `nextgencompanion/includes/class-ngc-tutor-calendar-service.php`
- **Responsibilities**:
  - tutor approval/suspension/public-profile checks
  - availability generation from working hours
  - Amelia/internal busy-slot overlay
  - status normalization and public labels
  - demo mode shim support
  - slot-selection audit event (`calendar_slot_selected`)

## 7) Tutor availability repository

- **Class**: `NGC_Tutor_Availability_Repository`
- **File**: `nextgencompanion/includes/class-ngc-tutor-availability-repository.php`
- **Responsibilities**:
  - resolve tutor context (`post_id`, `user_id`, `amelia_employee_id`, timezone, mode)
  - approved/suspended/published checks
  - working-hours defaults/normalization

## 8) Tutor profile template changes

- **Template partial**: `beyondinfinity/templates/tutor/calendar.php`
- **Injection point**: `bi_render_tutor_profile()` in `beyondinfinity/inc/template-tags.php`
- **Rules enforced**:
  - calendar renders only for approved tutors
  - hidden for suspended/incomplete profiles
  - public-safe labels only
  - includes CTAs: `Book lesson`, `Request custom time`

## 9) Calendar UI structure

- Header: calendar title + timezone
- Legend: available/booked/pending/unavailable
- Slot cards:
  - date
  - start/end
  - delivery mode
  - public label
  - CTA for available slots only
- Styles added in `beyondinfinity/style.css`

## 10) Demo JSON payload

- **File**: `nextgencompanion/demo/demo_tutor_calendar.json`
- Includes:
  - available slots
  - booked slots
  - blocked slots
  - pending slots
  - online/in-person/hybrid samples
- Source in demo response: `meta.source = "demo"`

## 11) Security/privacy controls implemented

- Sanitized request params in REST and shortcode handlers
- Escaped public output
- Nonce check for slot selection AJAX
- Approval/suspension/published checks before exposing calendar
- Public booked slots expose only label/status and time windows
- No student/parent/payment/private-note fields in public response
- Double-book prevention in `NGC_Bookings::create()` overlap check

## 12) Verification checklist

- [x] Tutor calendar service exists
- [x] Calendar REST endpoint exists (`nextgen/v1`)
- [x] Calendar shortcode exists
- [x] Approved tutor profiles can render calendar block
- [x] Suspended/incomplete tutors are blocked from public calendar
- [x] Amelia adapter implemented
- [x] Internal fallback adapter implemented
- [x] Demo tutor calendar shim exists
- [x] Public output hides private booking data
- [x] Slot selection creates audit event
- [x] Double-booking conflict check added

## 13) Test checklist

- [ ] Approved tutor profile displays calendar
- [ ] Rejected/suspended tutor profile hides calendar
- [ ] REST endpoint returns expected envelope
- [ ] Amelia active: `meta.source = amelia` where mappings exist
- [ ] Amelia unavailable: fallback returns `meta.source = internal` and site does not fatal
- [ ] Demo mode + `demo=1` returns `meta.source = demo`
- [ ] Slot CTA logs `CALENDAR_SLOT_SELECTED`
- [ ] Public response contains no parent/student/payment private fields
- [ ] Overlapping booking create request is blocked

## 14) Final status table

| Requirement | Status | Evidence |
|---|---|---|
| Tutor Calendar Service | DONE | `class-ngc-tutor-calendar-service.php` |
| Tutor Availability Repository | DONE | `class-ngc-tutor-availability-repository.php` |
| Amelia Availability Adapter | DONE | `class-ngc-amelia-availability-adapter.php` |
| Internal Booking Adapter | DONE | `class-ngc-internal-booking-adapter.php` |
| Calendar REST API | DONE | `class-ngc-rest-tutor-calendar.php` |
| Calendar Shortcode | DONE | `class-ngc-shortcodes.php` (`nextgen_tutor_calendar`) |
| Calendar Template Partial | DONE | `beyondinfinity/templates/tutor/calendar.php` |
| Tutor Profile Calendar Block | DONE | `bi_render_tutor_profile()` injection |
| Admin Verification Check | DONE | `class-ngc-verification.php`, platform health checks |
| Demo Calendar JSON Shim | DONE | `demo/demo_tutor_calendar.json` |
| Double-book conflict prevention | DONE | `class-ngc-bookings.php` overlap guard |
| Privacy-safe public labels | DONE | service mapping + shortcode rendering |

