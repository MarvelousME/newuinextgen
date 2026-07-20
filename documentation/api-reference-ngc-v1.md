# NGC REST API Reference (`ngc/v1`)

**Audience:** integrators, frontend developers  
**Base URL (local):** `http://localhost:8900/wp-json/ngc/v1`  
**Auth:** WordPress cookie / Application Password / Bearer (metrics token)  
**Namespace constant:** `NGC_Rest::NAMESPACE` in `includes/rest/class-ngc-rest.php`

> Do not publish production secrets or demo passwords in clients. Prefer Application Passwords for machine access.

---

## 1. Conventions

| Item | Behavior |
|------|----------|
| Success | JSON body; HTTP 2xx |
| Error | `{ "error": "...", "code": "..." }` via `NGC_Rest::error_response` |
| Admin | `manage_options` or `ngc_admin_operations` |
| Public | Often `NGC_Rest::public_throttled()` |
| Login | `is_user_logged_in()` |

Permission helpers: `require_login`, `require_admin`, `require_support`, `public_throttled`.

---

## 2. Route groups (by controller)

### Dashboards — `NGC_Rest_Dashboard`

Typical role dashboards (parent / student / tutor / admin). Requires login; returns live repository data (empty-state zeros when no rows).

### Matching — `NGC_Rest_Matching`

Create find-tutor matches, accept/reject, list. Server calls `NGC_Matching::*` (scoring + workflows).

### Bookings — `NGC_Rest_Bookings`

Create / update / transition bookings. Conflict detection on tutor slot. Object-level checks via `NGC_Access`.

### Finance — `NGC_Rest_Finance`

Wallet / invoice / payout surfaces for authorized roles.

### Reviews — `NGC_Rest_Reviews`

Submit and list reviews tied to bookings.

### Platform analytics — `NGC_Rest_Platform`

Analytics snapshot, profiling, demo seed/clear (admin). Prefer Phase 14 Control Centre for relational demo ops.

### Platform services — `NGC_Rest_Platform_Services`

| Method | Path | Auth | Purpose |
|--------|------|------|---------|
| GET | `/platform/gamification/scorecard` | login | Scorecard |
| GET | `/platform/gamification/leaderboard/{board}` | login | Leaderboard |
| GET/POST | `/platform/export` | admin | Export jobs |
| GET | `/platform/audit` | admin | Audit feed |
| GET | `/platform/audit/timeline/{user_id}` | admin | User timeline |
| GET | `/platform/diagnostics/scan` | admin | Health scan |

### System log — `NGC_Rest_System_Log`

| Method | Path | Auth |
|--------|------|------|
| GET | `/platform/system-log` | admin |
| GET | `/platform/system-log/stats` | admin |

### Metrics (OBS-001) — `NGC_Rest_Metrics`

| Method | Path | Auth | Notes |
|--------|------|------|-------|
| GET | `/metrics` | Bearer `ngc_metrics_token` or admin | Prometheus text (raw) |
| GET | `/metrics/json` | same | JSON snapshot |
| POST | `/metrics/push` | admin | Push to configured webhook |

Example scrape header:

```http
GET /wp-json/ngc/v1/metrics HTTP/1.1
Authorization: Bearer <token-from-Platform-Observability>
```

Sample metrics: `ngc_up`, `ngc_bookings_open`, `ngc_child_learners_active`, `ngc_log_errors_1h`, `ngc_safeguarding_open`, `ngc_fraud_open`.

### AI — `NGC_Rest_Ai`

Models / agents / chat (admin). Subject to `BIA_Policy` redaction and egress allowlists.

### Studio — `NGC_Rest_Studio`

Workflows, forms, emails, notifications, dashboards (large surface — see Studio developer manual under Companion `docs/studio/`).

### Tutor calendar — `NGC_Rest_Tutor_Calendar`

Also registered under `nextgen/v1` for theme shortcodes.

### Legacy aliases — `NGC_Rest_Legacy_Alias`

Compatibility shims; prefer canonical `ngc/v1` paths for new work.

---

## 3. Domain events (not REST, but API-adjacent)

Seeded / runtime workflows dispatch keys such as:

`match.proposed`, `booking.created`, `lesson.completed`, `payment.received`

Bridged into agent envelopes via `NGC_Domain_Event_Bridge` (outbox). Demo seed records sandbox notification deliveries in `NGC_Demo_Notifications` with `correlation_id`.

---

## 4. WP-CLI companions

```bash
wp ngc verify
wp ngc demo_seed
wp ngc demo_verify
wp ngc demo_run_journey --id=JOURNEY-PARENT-001
wp ngc demo_export_evidence
```

Full list: Companion `includes/cli/class-ngc-cli.php`.

---

## 5. Error codes (common)

| Code | Meaning |
|------|---------|
| `ngc_rate_limited` | Public throttle |
| `ngc_forbidden` / 403 | Capability failure |
| `ngc_booking_conflict` | Slot taken |
| `ngc_demo_blocked` | Demo op outside demo/non-prod |
| `ngc_metrics_forbidden` | Missing metrics token |

---

## 6. Related docs

- [platform-architecture.md](./platform-architecture.md)
- [ops-privacy-observability.md](./ops-privacy-observability.md)
- [tutorials/01-phase14-demo-walkthrough.md](./tutorials/01-phase14-demo-walkthrough.md)
