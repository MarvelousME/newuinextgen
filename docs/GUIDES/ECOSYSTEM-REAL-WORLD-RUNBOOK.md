# NextGen Tutors — Real-World Ecosystem Runbook

Hands-on tutorial to bring up the stack, operate Beyond Measure, run a parent→tutor booking journey, and optionally enable Talent Intelligence / Agent Memory. Values below are the **repo defaults**; replace with your host if `WP_PORT` differs.

---

## 0. Defaults (copy these)

| Item | Value |
|------|--------|
| Site URL | `http://localhost:8890` |
| WP Admin | `http://localhost:8890/wp-admin` |
| Admin user | `admin` |
| Admin password | `NextGenAdmin!2026` |
| Admin email | `admin@nextgentutors.local` |
| phpMyAdmin | `http://127.0.0.1:8082` |
| Agent Gateway health | `http://localhost:8787/health` |
| Gateway secret (local only) | `staging-local-secret` |
| DB name / user / pass | `wordpress` / `wordpress` / `wordpress` |
| MySQL root | `rootpass` |

If your `docker/.env` has `WP_URL=http://127.0.0.1:8081`, use **8081** everywhere below instead of **8890**.

**Safety:** Never set `NGC_ALLOW_DEMO_SEED=1` on a public/paying host. For production-like mode use `docker-compose.production.yml` (demo seed off, phpMyAdmin off).

---

## 1. Prerequisites

- Docker Desktop (or Engine + Compose v2)
- Git clone of this monorepo
- Ports free: `8890` (or your `WP_PORT`), `8787`, `8082`
- Optional: Node 20+ only if you want to webpack-build Beyond Measure (`admin/app`); otherwise `build/fallback.js` is enough

---

## 2. Start the core stack

```powershell
cd docker
Copy-Item .env.example .env   # first time only
# Edit .env if needed: WP_PORT, WP_URL, passwords
.\start.ps1
# Or:
docker compose up -d --build db agent-gateway wordpress
```

Wait until WordPress answers:

```powershell
curl -s -o $null -w "%{http_code}`n" http://localhost:8890/
# Expect 200 or 302
```

Bootstrap / activate theme + fleet plugins (if not already done by setup profile):

```powershell
cd docker
docker compose --profile setup run --rm wpcli
# Or helpers:
.\scripts\install-companion.ps1
# Or inside WP:
# docker compose exec wordpress wp plugin activate NextGenTutors-Companion/nextgencompanion --allow-root
```

Activate the Control Plane explicitly:

```powershell
docker compose exec wordpress wp plugin activate NextGenTutors-BeyondMeasure/nextgentutors-beyond-measure --allow-root
docker compose exec wordpress wp theme activate nextgentutors-beyondinfinity --allow-root
```

Optional MCP staging seed:

```powershell
cd docker
.\scripts\seed-mcp-staging.ps1
```

### Sanity checklist

| Check | URL / command | Expect |
|-------|----------------|--------|
| Home | http://localhost:8890/ | BeyondInfinity theme |
| Login | http://localhost:8890/wp-login.php | Form loads |
| Gateway | http://localhost:8787/health | Healthy JSON |
| Companion REST | http://localhost:8890/wp-json/ngc/v1/ | Routes (may 401 without cookie) |
| Control Plane REST | http://localhost:8890/wp-json/nextgentutors-control/v1/ | Namespace present |

---

## 3. Log into WordPress admin

1. Open http://localhost:8890/wp-login.php  
2. User: `admin`  
3. Password: `NextGenAdmin!2026`  
4. Land on http://localhost:8890/wp-admin/

Confirm plugins **Active**:

- NextGenTutors-Companion  
- NextGenTutors-BeyondMeasure  
- NextGenTutors-Mission-Control (optional, coexists)  
- NextGenTutors-AI-Integration (if using agent transport)  
- WooCommerce (if booking/commerce path needs it — install via Plugin Manager if missing)

---

## 4. Beyond Measure Control Plane (day-to-day ops)

### Open the SPA

**Menu:** NextGen → **Beyond Measure**  
**Direct URL:**

```text
http://localhost:8890/wp-admin/admin.php?page=ngtbm-beyond-measure
```

Hash routes (after the SPA loads):

| Screen | URL hash |
|--------|----------|
| Command Center | `#/` |
| Talent Intelligence | `#/tutors/talent` |
| Talent config | `#/tutors/talent/config` |
| Subsystems | `#/ecosystem/subsystems` |
| Dependency Map | `#/ecosystem/dependency-map` |
| Access Matrix | `#/security/access-matrix` |
| Queues / DLQ | `#/operations/queues` |
| Audit | `#/governance/audit` |

Example full URL:

```text
http://localhost:8890/wp-admin/admin.php?page=ngtbm-beyond-measure#/tutors/talent
```

### REST you can call while logged in (browser cookie + nonce)

Namespace: `nextgentutors-control/v1`

| Purpose | Method | Path |
|---------|--------|------|
| Nav IA | GET | `/wp-json/nextgentutors-control/v1/nav` |
| Health | GET | `/wp-json/nextgentutors-control/v1/health` |
| Subsystems | GET | `/wp-json/nextgentutors-control/v1/subsystems` |
| Talent stats | GET | `/wp-json/nextgentutors-control/v1/talent/stats` |
| Evaluations list | GET | `/wp-json/nextgentutors-control/v1/resources/talent-evaluation` |
| Explain | GET | `/wp-json/nextgentutors-control/v1/talent/evaluations/{id}/explain` |
| Access matrix | GET | `/wp-json/nextgentutors-control/v1/access-matrix` |
| Dependency graph | GET | `/wp-json/nextgentutors-control/v1/dependency-graph` |

**PowerShell sample (cookie jar after browser login is easier; for WP-CLI application passwords use Application Passwords in Users → Profile):**

```powershell
# After creating an Application Password for admin in WP Admin → Users → Profile:
$pair = "admin:YOUR_APPLICATION_PASSWORD"
$b64  = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
Invoke-RestMethod -Headers @{ Authorization = "Basic $b64" } `
  http://localhost:8890/wp-json/nextgentutors-control/v1/health
```

### What “healthy ops” looks like on Command Center

- Status pill: **Operational** (or Degraded with listed attention items)  
- Subsystems tile: e.g. `N/N` healthy  
- Open **Notifications**, acknowledge criticals  
- Open **Dependency Map** and click `bridge-talent-intelligence` / `companion` nodes  

Legacy PHP screens still exist (strangler):

| Legacy | URL |
|--------|-----|
| Talent PHP admin | http://localhost:8890/wp-admin/admin.php?page=ngc-talent-intelligence |
| Memory Center | http://localhost:8890/wp-admin/admin.php?page=ngc-memory-center |
| Platform Kernel | http://localhost:8890/wp-admin/admin.php?page=ngc-platform-kernel |
| Mission Control | http://localhost:8890/wp-admin/admin.php?page=ngtmc (slug may vary — use Plugins / NextGen menu) |

Prefer Beyond Measure for daily ops; use legacy only when migrating.

---

## 5. Real-world journey A — Parent finds a tutor and books

### Public pages (theme registry slugs)

| Step | URL |
|------|-----|
| Home | http://localhost:8890/ |
| Find a Tutor | http://localhost:8890/find-a-tutor/ |
| Become a Tutor | http://localhost:8890/become-a-tutor/ |
| Parent dashboard | http://localhost:8890/parent-dashboard/ |
| Student dashboard | http://localhost:8890/student-dashboard/ |
| Tutor dashboard | http://localhost:8890/tutor-dashboard/ |

If a page 404s, regenerate pages from theme/Companion page registry (Mission Control / Companion admin “ensure pages”, or WP-CLI seed used by your setup scripts).

### Example inputs (South Africa CAPS-style)

On **Find a Tutor** / marketplace filters, use:

| Field | Example value |
|-------|----------------|
| Subject | `Mathematics` |
| Grade | `12` |
| Curriculum | `CAPS` |
| Mode | `Online` |
| Language | `English` |

1. Open http://localhost:8890/find-a-tutor/  
2. Apply filters above → shortlist tutors  
3. Open a tutor card → Book / Request session  
4. Complete WooCommerce checkout if productized bookings are enabled  
5. Confirm booking appears on http://localhost:8890/parent-dashboard/  

Domain APIs (Companion):

```text
GET  /wp-json/ngc/v1/...          (dashboard, bookings — auth required)
POST /wp-json/ngc/v1/...          (create booking — capability-gated)
```

Exact booking paths depend on your Companion version; use browser Network tab on the dashboard while booking, or Beyond Measure → Queues for failures.

### If ranking feels “dumb”

Talent re-rank is **off by default** and never auto-approves tutors. Enable only after §7.

---

## 6. Real-world journey B — Tutor applies (human approval)

1. Open http://localhost:8890/become-a-tutor/  
2. Submit application with sample data:

| Field | Example |
|-------|---------|
| Full name | `Sipho Molefe` |
| Email | `sipho.tutor@example.com` |
| Subjects | `Mathematics, Physical Sciences` |
| Grades | `10-12` |
| Curriculum | `CAPS` |
| Bio | `BSc Education; 5 years CAPS Grade 12 Maths; online and after-school.` |
| Mode | `Online` |

3. In Beyond Measure → **Talent Intelligence** (`#/tutors/talent`):  
   - Review scoreboard  
   - Click **Explain →** on a row  
   - Confirm recommendation is **human review**, not auto-approve  
4. Approve/reject in Companion tutor lifecycle / tutor manager UI (WordPress caps: `ngc_manage_matches` / Tutor Manager role)  

**Do not** treat suitability % as a hiring decision alone — safeguarding / documents still apply.

---

## 7. Enable Talent Intelligence (optional)

Talent is **flag-gated** and decision-support only.

### Optional NLP sidecar (bio ↔ requirement text)

```powershell
cd docker
docker compose --profile talent -f docker-compose.yml -f talent/docker-compose.talent.yml up -d talent-nlp
```

Host health: http://127.0.0.1:8090 (see `docker/talent/README.md`)  
In-compose URL for PHP: `http://talent-nlp:8090`

### Configure in Beyond Measure

1. Open `#/tutors/talent/config`  
2. Example values:

| Setting | Value |
|---------|--------|
| Enabled | `ON` |
| Provider | `ngt-talent-suitability-v1` |
| Timeout | `10` seconds |
| Async evaluation | `ON` |
| Strong match threshold | `85` |
| Review threshold | `65` |
| Scoring weights | Must sum to **100** (default: Subject 25, Curriculum 15, Qualification 20, Experience 15, Availability 15, Language 10) |

3. **Save & Validate**  
4. Legacy fallback: http://localhost:8890/wp-admin/admin.php?page=ngc-talent-intelligence  

### Verify

```text
GET /wp-json/nextgentutors-control/v1/talent/stats
GET /wp-json/ngc/v1/talent/health
```

Unit check (host):

```powershell
php NextGenTutors-Companion/tests/run-talent-unit.php
```

---

## 8. Enable Agent Memory (optional — not HA)

SQLite memory profile is **not** multi-node HA.

```powershell
cd docker
# Set in .env if needed:
# MEMORY_CORE_HOST_PORT=8420
# TDAI_GATEWAY_API_KEY=your-local-bearer
docker compose --profile memory -f docker-compose.yml -f memory/docker-compose.memory.yml up -d memory-core
```

| Setting | Value |
|---------|--------|
| Core base URL (in Docker network) | `http://memory-core:8420` |
| Core base URL (from host browser tools) | `http://127.0.0.1:8420` |
| Companion admin | http://localhost:8890/wp-admin/admin.php?page=ngc-memory-center |

Enable order in Memory Center:

1. Master **enabled** + mode `REMOTE` or `LOCAL`  
2. `retrieve_enabled`  
3. `write_enabled`  
4. Leave Skills / Wiki / CodeGraph **OFF** until separately approved  

Verify bookings/payments still work with memory **DISABLED** (memory must not be transaction-critical).

```powershell
php NextGenTutors-Companion/tests/run-memory-unit.php
```

---

## 9. RBAC for a real ops team

Beyond Measure seeds role bundles (do **not** authorize by role slug in code — use capabilities).

| Role slug | Who | Typical access |
|-----------|-----|----------------|
| `ngt_platform_admin` | Tech lead | Full Control Plane |
| `ngt_ops_manager` | Ops | Health, queues, DLQ, notifications |
| `ngt_tutor_manager` | Academics | Talent read/evaluate/override |
| `ngt_safeguarding` | Safeguarding | Read + audit |
| `ngt_ai_admin` | AI ops | Talent configure, subsystem configure |
| `ngt_auditor` | Compliance | Read-only matrix/audit |
| `ngt_support` | Support | Restricted operational |

Assign in **Users → Edit user → Role**, or:

```powershell
docker compose exec wordpress wp user set-role USERNAME ngt_tutor_manager --allow-root
```

Inspect matrix in SPA: `#/security/access-matrix`  
or `GET /wp-json/nextgentutors-control/v1/access-matrix`

Example deny check: a user **without** `ngt_config_manage` must get **403** on:

```text
PUT /wp-json/nextgentutors-control/v1/configuration/bridge-talent-intelligence
```

---

## 10. Architecture / quality gates (engineers)

From monorepo root:

```powershell
node rad-platform/cli/discover.mjs
node rad-platform/cli/validate.mjs
node rad-platform/cli/gate.mjs
php NextGenTutors-BeyondMeasure/tests/run-unit.php
```

Expect: `gate: PASS`, Beyond Measure unit **11 PASS**.

Optional headed E2E (stack must be up):

```powershell
cd e2e
$env:BEYOND_MEASURE="1"
$env:WP_URL="http://localhost:8890"
$env:WP_ADMIN_USER="admin"
$env:WP_ADMIN_PASSWORD="NextGenAdmin!2026"
npx playwright test tests/beyond-measure-control-plane.spec.ts
```

---

## 11. Production-minded checklist

| Item | Action |
|------|--------|
| Demo seed | `NGC_ALLOW_DEMO_SEED=0` |
| Environment | `WP_ENVIRONMENT_TYPE=production` |
| Compose | `docker compose -f docker-compose.yml -f docker-compose.production.yml up -d` |
| Secrets | Rotate `NGT_GATEWAY_SHARED_SECRET`, DB passwords, SMTP, PayFast keys |
| phpMyAdmin | Off / bind `127.0.0.1` only |
| Talent | Keep off until policy + fairness review signed off |
| Memory | No SQLite HA claim; vault bearer keys; PII/minors write policy |
| TLS | Terminate TLS at reverse proxy; set `WP_URL` to `https://your.domain` |
| Backups | DB + `wp-content/uploads` + Companion tables `wp_ngc_*` |

---

## 12. Troubleshooting

| Symptom | Fix |
|---------|-----|
| Theme looks broken | Activate `nextgentutors-beyondinfinity`; parent `hello-elementor` installed |
| Beyond Measure blank | Confirm plugin active; open console; ensure `build/fallback.js` or run `npm run build` in `NextGenTutors-BeyondMeasure` |
| 403 on REST | Missing cap; use admin or grant `ngt_cp_access` / relevant `ngt_*` cap |
| Find-a-tutor empty | Seed tutors / ensure Companion marketplace data; Talent flags off still shows hard-filter results |
| Port conflict | Change `WP_PORT` in `docker/.env` and matching `WP_URL` |
| Disk full / ENOSPC | Free space before `npm install` for full SPA build |

---

## 13. One-page “first hour” script

```text
1. cd docker → copy .env → start.ps1
2. Login admin / NextGenAdmin!2026
3. Open wp-admin/admin.php?page=ngtbm-beyond-measure  → Command Center green/amber
4. Open /find-a-tutor/  → filter Mathematics / Grade 12 / CAPS / Online
5. Open /become-a-tutor/ → submit Sipho Molefe sample (or use existing tutor)
6. Beyond Measure → #/tutors/talent → Explain one row (demo or live)
7. Parent dashboard → confirm booking or attention items
8. (Optional) talent profile + memory profile only after flags reviewed
```

**Principle to remember:** Companion owns business logic; Beyond Measure owns administration; Talent/Memory are optional decision-support and must not break bookings or payments when disabled.
