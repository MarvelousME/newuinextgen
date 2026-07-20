# NextGen Tutors — Commercial Deployment Guide

Enterprise deployment guide for licensed commercial installations of the NextGen Tutors platform.

**Audience:** Solution architects, DevOps, implementation partners  
**Last updated:** 2026-07-13

---

## 1. Deployment overview

NextGen Tutors deploys as a **WordPress multisite-capable monolith** with:

- 1 active theme (BeyondInfinity)
- 1 required domain plugin (Companion)
- 1 recommended operator plugin (Plugin Manager)
- Optional migration plugin (Html Importer — deactivatable after import)
- Optional content packs (Command Center, Completion Suite)
- Third-party stack plugins (WooCommerce, Elementor, FluentCRM, Amelia, etc.)

---

## 2. Environment topology

| Environment | Purpose | Minimum spec |
|-------------|---------|--------------|
| **Local** | Developer workstations | Docker Desktop, 8 GB RAM |
| **Development** | Feature integration | VPS 2 vCPU / 4 GB or shared host |
| **UAT** | Role and workflow validation | Staging mirror of production |
| **Staging** | Production rehearsal | Full plugin stack + anonymised data |
| **Production** | Live operations | 4+ vCPU, 8+ GB RAM, SSD, TLS |

### Required services

| Service | Version | Notes |
|---------|---------|-------|
| PHP | 8.2+ | `memory_limit` ≥ 512M for large zip installs |
| MySQL / MariaDB | 8.0+ / 10.6+ | InnoDB, utf8mb4 |
| WordPress | 6.7+ | Permalinks: `/%postname%/` |
| TLS | Required (prod) | Let's Encrypt or commercial cert |
| SMTP | Required (prod) | FluentSMTP or transactional provider |
| Cron | System cron | Replace WP-Cron on production |

---

## 3. Pre-deployment checklist

### 3.1 Licenses and accounts

- [ ] WordPress hosting contract
- [ ] WooCommerce (if commerce enabled)
- [ ] Amelia Booking license
- [ ] Elementor Pro (if used)
- [ ] FluentCRM / FluentSMTP
- [ ] PayFast merchant account (sandbox + production keys)
- [ ] Domain + SSL certificate
- [ ] Backup storage (S3, host snapshots)

### 3.2 Artifact preparation

```powershell
# Build release zips from repo
powershell -File scripts/build-release.ps1
```

| Artifact | Deploy to |
|----------|-----------|
| `dist/nextgentutors-beyondinfinity.zip` | `wp-content/themes/` |
| `dist/NextGenTutors-Companion.zip` | `wp-content/plugins/` |
| `dist/NextGenTutors-Plugin-Manager.zip` | `wp-content/plugins/` |
| `dist/NextGenTutors-Html-Importer.zip` | `wp-content/plugins/` (optional) |
| `content/nextgen-command-center-v1.0.zip` | Extract → `plugins/nextgen-command-center` |
| `content/nextgen-completion-suite-v1.0.zip` | Extract → `plugins/nextgen-completion-suite` |
| Offline plugin zips | `wp-content/ngcpm-packages/` |

---

## 4. Installation sequence

### Phase 1 — WordPress core

1. Install WordPress 6.7+ on clean database
2. Set timezone: `Africa/Johannesburg`
3. Set permalinks: `/%postname%/`
4. Install **Hello Elementor** parent theme
5. Create admin account with strong password + 2FA (via security plugin)

### Phase 2 — Core packages

1. Upload and activate **NextGenTutors-Companion**
2. Upload and activate **nextgentutors-beyondinfinity** theme; activate it
3. Upload and activate **NextGenTutors-Plugin-Manager**
4. Confirm `wp_ngc_*` tables created (phpMyAdmin or `wp ngc verify`)

### Phase 3 — Fleet plugins

**Option A — Plugin Manager UI**

1. Copy all required zips to `wp-content/ngcpm-packages/`
2. WP Admin → Plugin Manager → Install Queue → Install all pending

**Option B — WP-CLI**

```bash
wp eval-file wp-content/plugins/NextGenTutors-Plugin-Manager/scripts/install-registry-zips.php --allow-root
```

Required plugins: WooCommerce, Elementor, FluentCRM, FluentSMTP, MasterStudy, GamiPress, AutomatorWP, User Role Editor, Amelia, PayFast gateway.

### Phase 4 — Content packs (recommended)

1. Deploy Command Center + Completion Suite plugins
2. Activate both
3. Command Center → **Run / Repair Setup**
4. Completion Suite → **Run / Repair Setup**
5. Companion → Workflows → Integrate Specs:
   - Import content-pack catalog
   - Seed AutomatorWP from v2 JSON

### Phase 5 — Configuration

```bash
# Companion local stack bootstrap (sandbox PayFast, CRM tags, etc.)
wp eval 'NGC_Integrations_Bootstrap::configure_local_stack(true);' --allow-root

# Theme defaults + launch pages
wp eval 'bi_sync_launch_pages();' --allow-root

# Customizer defaults
wp theme mod set visual_preset beyond-infinity --allow-root
wp theme mod set home_layout kinetic --allow-root
```

**Production:** Remove or set `NGC_ALLOW_DEMO_SEED` to false in `wp-config.php`. Configure live PayFast keys in WooCommerce → PayFast.

### Phase 6 — Content migration (optional)

1. Activate Html Importer
2. Place HTML sources in configured import directory
3. Run dry-run import
4. Review pages; deactivate Html Importer when complete

### Phase 7 — Verification

```bash
wp ngc verify --allow-root
php NextGenTutors-Companion/scripts/validate.php
```

Manual smoke tests:

- [ ] Home page loads (kinetic layout)
- [ ] Find a Tutor form submits
- [ ] Become a Tutor form submits
- [ ] Parent register form submits
- [ ] Admin can approve tutor application
- [ ] Command Center shows live metrics
- [ ] PayFast sandbox payment completes (UAT)
- [ ] Amelia booking creates (UAT)

---

## 5. Production hardening

### 5.1 wp-config.php

```php
define('DISALLOW_FILE_EDIT', true);
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
define('NGC_ALLOW_DEMO_SEED', false);  // disable demo seed on production
```

### 5.2 Cron

Replace WP-Cron with system cron. See `docs/operations/production-cron.md`.

### 5.3 Backups

- Daily automated database backup (retain 30 days)
- Weekly full file backup
- Pre-deploy snapshot mandatory

### 5.4 Security

- TLS 1.2+ only
- Rate limiting on login (`/wp-login.php`)
- WAF (Cloudflare, host WAF, or Wordfence)
- Least-privilege admin accounts
- Audit log review (Companion `ngc_audit` table)

### 5.5 Performance

- Object cache (Redis) recommended at scale
- OPcache enabled
- CDN for static assets
- `FS_METHOD` direct on VPS; ensure correct file permissions

---

## 6. Multi-site and white-label

The platform is designed for **single-site** deployment per tutoring brand. Multi-site is untested (`NOT VERIFIED`).

White-label via:

- Customizer (logo, colors, contact)
- `inc/config/options-schema.php` visual presets
- Section CMS (homepage copy)
- `content/page-map.json` page slugs

---

## 7. Rollback procedure

1. Put site in maintenance mode
2. Restore database from pre-deploy snapshot
3. Redeploy previous theme + plugin zip versions
4. `wp cache flush`
5. `wp ngc verify`
6. Run smoke test checklist
7. Remove maintenance mode

---

## 8. Support escalation matrix

| Tier | Scope | Contact |
|------|-------|---------|
| L1 | User-facing issues, form errors | Support staff — see `tutorials/support/manual.md` |
| L2 | Workflow failures, plugin health | Platform admin — `tutorials/admin/manual.md` |
| L3 | Code defects, integration adapters | Development team — `DEVELOPER-GUIDE.md` |
| L4 | Infrastructure, security incidents | DevOps / hosting provider |

---

## 9. Related documents

- [tutorials/BEYONDINFINITY-SEQUENTIAL-SETUP.md](tutorials/BEYONDINFINITY-SEQUENTIAL-SETUP.md) — **post-plugin sequential bring-up of BeyondInfinity + features**
- [PRODUCTION-READINESS.md](PRODUCTION-READINESS.md) — sign-off checklist
- [deployment/infrastructure-documentation.md](deployment/infrastructure-documentation.md)
- [operations/operations-documentation.md](operations/operations-documentation.md)
- [security/security-documentation.md](security/security-documentation.md)
- [KNOWN-LIMITATIONS.md](../KNOWN-LIMITATIONS.md)
