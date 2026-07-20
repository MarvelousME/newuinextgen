# REST Endpoint Security Classification

## ngc/v1

| Route | Method | Classification | Permission |
|-------|--------|----------------|------------|
| `/match/smart` | GET | **PUBLIC_SAFE** | Throttled 30/10min; sanitized match rows |
| `/platform/gamification/leaderboard/{board}` | GET | **PUBLIC_SAFE** | Throttled 60/10min; aggregated scores |
| `/platform/gamification/scorecard` | GET | **AUTH_REQUIRED** | Logged-in |
| `/platform/gamification/achievements` | GET | **AUTH_REQUIRED** | Logged-in |
| `/platform/export/*` | * | **ADMIN_ONLY** | `require_admin` |
| `/platform/diagnostics/*` | * | **ADMIN_ONLY** | `require_admin` |
| `/platform/audit/*` | GET | **ADMIN_ONLY** | `can_view_audit` |
| `/reviews` | POST | **AUTH_REQUIRED** | Parent/support caps |
| `/reviews/tutor/{id}` | GET | **PUBLIC_SAFE** | Throttled; average only |
| `/dashboard/*` | * | **AUTH_REQUIRED** | Role-specific |

## nextgen/v1

| Route | Method | Classification | Permission |
|-------|--------|----------------|------------|
| `/tutors/{id}/calendar` | GET | **PUBLIC_SAFE** | Throttled 30/10min; anonymous gets sanitized slots (no user_id, amelia_employee_id, notes) |

## AJAX

| Action | Classification | Controls |
|--------|----------------|----------|
| `ngc_match_tutors` | **PUBLIC_SAFE** | Nonce + 20/10min rate limit + escaped JSON |

## Rate limiting

- Storage: WordPress transients
- Key: `sha256(REMOTE_ADDR + USER_AGENT)` — raw IP never stored
- Response: HTTP 429 JSON (`ngc_rate_limited`)

## SECURITY_REVIEW_REQUIRED

- CDN/cache in front of REST may weaken rate limits (per-edge IP)
- Logged-in calendar responses may include internal IDs — restrict caching
