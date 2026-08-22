# 02 — OpenWA-Dev Source Analysis

**Upstream:** https://github.com/Calanca/OpenWA-Dev  
**Branch:** `main`  
**Version (package.json):** `0.1.6`  
**License:** MIT  
**Evidence date:** 2026-08-11  

> Note: README clone URLs reference `rmyndharis/OpenWA`; Calanca/OpenWA-Dev appears to be the same lineage / fork. Analysis used Calanca repo contents.

## Stack (verified via package.json)

| Layer | Technology |
|-------|------------|
| Runtime | Node.js 22 LTS (advertised) |
| Framework | NestJS 11.x |
| Language | TypeScript 5.x |
| WA engine | **`whatsapp-web.js` ^1.26.1-alpha.3** (unofficial WhatsApp Web) |
| ORM | TypeORM |
| DB | sqlite3 + pg |
| Queue/cache | BullMQ, ioredis (optional) |
| Storage | Local + `@aws-sdk/client-s3` |
| Security | helmet, throttler |
| Dashboard | React app under `dashboard/` |
| Tests | Jest (+ e2e config); coverage thresholds low (~10–15%) |

## Modules present under `src/modules/` (verified)

`session`, `message`, `webhook`, `group`, `contact`, `auth`, `health`, `audit`, `channel`, `label`, `catalog`, `queue`, `events`, `stats`, `status`, `settings`, `infra`, `docker`, `plugins`

This supports README claims of multi-session, messaging, webhooks, groups/channels/labels, health, audit.

## Ports (documented)

| Service | Port |
|---------|------|
| API | 2785 |
| Dashboard | 2886 |
| Swagger | 2785/api/docs |

## Advertised capability verification

| Capability | README | Source evidence | Confidence |
|------------|--------|-----------------|------------|
| REST API | ✅ | Nest modules + Swagger deps | High |
| Multi-session | ✅ | `session` module | High |
| Webhooks + HMAC | ✅ | `webhook` module + security docs | High (verify HMAC implementation in Stage 2) |
| Dashboard | ✅ | `dashboard/` | High |
| API-key auth | ✅ | `auth` module | High |
| Text/media/reactions/bulk | ✅ | `message` module + README | Medium–High (media paths need Stage 2 contract tests) |
| Delivery/read status | ✅ | `status` / message modules | Medium — do not fabricate unread statuses |
| Groups/channels/labels | ✅ | dedicated modules | Medium |
| Rate limit / CIDR | ✅ | throttler + security design doc | Medium |
| Audit logging | ✅ | `audit` module | High |
| SQLite/Postgres/Redis/S3 | ✅ | deps + compose profiles | High |
| Docker / health | ✅ | Dockerfile, compose, `health` module | High |
| Data migration | ✅ | docs migration guide | Medium |

## Security model (docs)

Security design document covers API keys, HMAC webhooks, rate limiting, CIDR allowlisting, helmet. Treat as design + partial implementation until Stage 2 penetration tests.

## Unofficial transport risks (mandatory)

Because the engine is **whatsapp-web.js**:

| Risk | Implication |
|------|-------------|
| Account ban | Business numbers may be restricted by Meta |
| Session expiry / QR reauth | Ops burden; QR must stay admin-only |
| ToS | Not equivalent to official WhatsApp Business Cloud API |
| Throughput | Unofficial clients have soft limits / instability |
| Session corruption | Persistent auth state must be backed up |
| WA Web breakage | Upstream Puppeteer/Web changes can break overnight |
| Multi-device linkage | Linked-device constraints apply |

**Must never be marketed as “Official WhatsApp Business API.”**

## Fit vs existing theme OpenWA

| | Theme today | OpenWA-Dev |
|--|-------------|------------|
| Engine | `@open-wa/wa-automate` | `whatsapp-web.js` |
| Default port | 8080 | 2785 |
| API style | Easy API method POST | Nest REST `/api/sessions/.../messages/...` |
| Dashboard | Theme Customizer + Sync Launch | React :2886 |
| Multi-session | session-id query | First-class multi-session |
| Maturity in NGT | Code present, not prod-verified | External v0.1.6 |

## Classification for NextGen

**USED AS A PROVIDER** (preferred gateway candidate) behind Companion contracts, with legacy wa-automate as **FALLBACK PROVIDER** during migration — **not** embedded into WordPress business logic.
