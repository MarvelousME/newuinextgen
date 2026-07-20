# Operator Tutorials

Fleet management, Docker operations, plugin installation, and production handover for platform operators.

**Audience:** DevOps, site administrators, implementation consultants  
**Last updated:** 2026-07-13

---

## Tutorial O1: Docker local stack

**Goal:** Start the full development environment.

### Steps

```powershell
cd docker
Copy-Item .env.example .env   # first run only
.\start.ps1
```

| Service | URL |
|---------|-----|
| WordPress | http://localhost:8900 |
| Admin | http://localhost:8900/wp-admin |
| phpMyAdmin | http://localhost:8082 |

**Credentials:** `admin` / `NextGenAdmin!2026` (override in `.env`)

### What is mounted

| Host | Container |
|------|-----------|
| Workspace root | `themes/nextgentutors-beyondinfinity` |
| `NextGenTutors-Companion/` | `plugins/NextGenTutors-Companion` |
| `NextGenTutors-Plugin-Manager/` | `plugins/NextGenTutors-Plugin-Manager` |
| `NextGenTutors-Html-Importer/` | `plugins/NextGenTutors-Html-Importer` |
| `content/_extracted/nextgen-command-center-v1.0/` | `plugins/nextgen-command-center` |
| `content/_extracted/nextgen-completion-suite/` | `plugins/nextgen-completion-suite` |
| `docker/ngcpm-packages/` | `wp-content/ngcpm-packages` |

### Common commands

```powershell
docker compose up -d
docker compose down
docker compose logs -f wordpress
docker compose restart wordpress
docker compose --profile setup run --rm wpcli bash
```

**Status:** VERIFIED

---

## Tutorial O2: Install fleet plugins (registry zips)

**Goal:** Install WooCommerce, Elementor, FluentCRM, Amelia, PayFast, and full stack.

### Option A — PowerShell script (recommended)

```powershell
cd docker
.\scripts\install-registry-zips.ps1
```

Runs `NextGenTutors-Plugin-Manager/scripts/install-registry-zips.php` via WP-CLI.

### Option B — Plugin Manager UI

1. WP Admin → **NextGen Plugin Manager**
2. Review registry health grid
3. Click **Install All** or per-plugin install
4. Local zips auto-detected from `ngcpm-packages/`

### Local zip drop folder

| Environment | Path |
|-------------|------|
| Docker host | `docker/ngcpm-packages/` |
| Container | `/var/www/html/wp-content/ngcpm-packages` |
| Production | Set `NGCPM_LOCAL_ZIP_DIR` in `wp-config.php` |

Drop `.zip` files → Plugin Manager scans on admin load → pending install queue.

### Required stack

WooCommerce, Elementor, FluentCRM, FluentSMTP, MasterStudy LMS, GamiPress, AutomatorWP, User Role Editor, Amelia Booking, PayFast Gateway.

### Troubleshooting install failures

| Error | Fix |
|-------|-----|
| "Package could not be installed" | Check PHP memory ≥ 512M (`docker/custom.ini`) |
| Zip not found | Place in `ngcpm-packages/` with registry filename |
| Permission denied | `FS_METHOD` = `direct` in Docker config |
| Amelia/PayFast missing | Manual zip — not on wordpress.org |

**Status:** VERIFIED (direct ZipArchive path); Amelia license PARTIAL

---

## Tutorial O3: Activate core packages

**Goal:** Theme + Companion + ops plugins active.

### Via WP Admin

1. **Appearance → Themes** → Activate **NextGen Tutors BeyondInfinity**
2. **Plugins** → Activate:
   - NextGenTutors-Companion
   - NextGenTutors-Plugin-Manager
   - NextGenTutors-Html-Importer (optional)
   - NextGen Command Center
   - NextGen Completion Suite

### Via WP-CLI

```bash
wp theme activate nextgentutors-beyondinfinity
wp plugin activate NextGenTutors-Companion NextGenTutors-Plugin-Manager
wp plugin activate nextgen-command-center nextgen-completion-suite
```

### Post-activation

Companion creates 44 database tables and installs roles automatically.

**Status:** VERIFIED

---

## Tutorial O4: Content pack setup wizards

**Goal:** Seed Command Center rooms and Completion Suite pages.

### Command Center

1. WP Admin → **NextGen Command Center**
2. Click **Run / Repair Setup**
3. Click **Import Workflow JSON v2** (optional)
4. Verify pages:
   - `/nextgen-command-center/` — Mission Control
   - `/nextgen-staff-rtm/` — RTM hub

### Completion Suite

1. WP Admin → **NextGen Completion**
2. Click **Run / Repair Setup**
3. Click **Import Bundled Workflow JSON** (optional)
4. Verify operational pages:
   - `/progress-reports/`, `/lesson-notes/`, `/tutor-payouts/`

### Companion integration

1. **Companion → Workflows → Integrate Specs**
2. **Import content-pack catalog**
3. **Seed AutomatorWP from v2 JSON** (if AutomatorWP active)

**Status:** VERIFIED

---

## Tutorial O5: Plugin Manager tour and health

**Goal:** Use the operator console for fleet health.

### UI features

| Feature | Location |
|---------|----------|
| Registry grid | Main dashboard — install status per plugin |
| Health scanner | Green/yellow/red per dependency |
| Local zip inventory | Sidebar — pending installs |
| Guided tour | Launch button (center-right floating) |
| 3D button interactions | Install/activate buttons |

### Health checks

- Missing parent theme (Hello Elementor)
- Inactive required plugins
- PHP version / memory warnings
- Companion table count

### After fleet install

Run Companion bootstrap:

```bash
wp ngc verify
```

**Status:** VERIFIED

---

## Tutorial O6: Configure integrations

**Goal:** Wire PayFast, Amelia, FluentCRM for production.

### PayFast

1. WooCommerce → Settings → Payments → PayFast
2. Enter merchant ID, key, passphrase
3. Set sandbox mode for staging
4. Test ITN: place test order → verify `ngc_workflow_runs`

### Amelia

1. Amelia → Employees → map approved tutors
2. Amelia → Services → create lesson services
3. Companion → Workflows → Amelia API key (if using REST bridge)

### FluentCRM

1. FluentCRM → Lists → create Parent, Tutor, Applicant lists
2. Companion → Workflows → FluentCRM settings
3. Verify tags on tutor approval workflow

### FluentSMTP

1. FluentSMTP → configure provider (SendGrid, SMTP, etc.)
2. Send test email from workflow action

**Status:** PARTIAL — requires live credentials

---

## Tutorial O7: Production deployment

**Goal:** Move from staging to production.

Follow [COMMERCIAL-DEPLOYMENT-GUIDE.md](../COMMERCIAL-DEPLOYMENT-GUIDE.md):

1. Provision host (PHP 8.2+, MySQL 8.0+, TLS)
2. Upload `dist/*.zip` packages or Git deploy
3. Import database or fresh install + content migration
4. Set `NGC_ALLOW_DEMO_SEED` = false
5. Configure system cron (see `operations/production-cron.md`)
6. Run [PRODUCTION-READINESS.md](../PRODUCTION-READINESS.md) checklist
7. Sign off

**Status:** NOT VERIFIED until client UAT

---

## Tutorial O8: Backup and recovery

**Goal:** Protect production data.

### Database

```bash
wp db export backup-$(date +%Y%m%d).sql
```

### Files

Backup `wp-content/uploads/`, `wp-config.php`, and custom `ngcpm-packages/`.

### Recovery

1. Restore DB dump
2. Restore uploads
3. `wp cache flush`
4. `wp ngc verify`
5. Re-run health scanner

See [operations/operations-documentation.md](../operations/operations-documentation.md).

---

## Tutorial O9: Monitoring workflow health

**Goal:** Detect and retry failed automations.

### Admin locations

| View | Path |
|------|------|
| Workflow logs | Companion → Workflows → Logs |
| Retry queue | Companion → Workflows → (retry cron hourly) |
| Mission Control | Command Center dashboard |
| Platform analytics | Companion → NextGen → Platform |

### CLI

```bash
wp ngc integrate_status
wp cron event list | grep ngc
```

### Alerts to configure

- Failed workflow count > threshold
- PayFast ITN failures
- Amelia sync errors
- Disk space on upload dir

---

## Tutorial O10: Html Importer (one-time migration)

**Goal:** Import static HTML into launch pages.

1. Place HTML in `webpages-content/`
2. Activate NextGenTutors-Html-Importer
3. WP Admin → Html Importer
4. Run **dry-run** first
5. Execute import
6. Review pages — rollback available

**Does not touch** `ngc_*` tables.

**Status:** VERIFIED

---

## Related docs

- [BEYONDINFINITY-SEQUENTIAL-SETUP.md](BEYONDINFINITY-SEQUENTIAL-SETUP.md) — theme + features after required plugins are installed
- [COMMERCIAL-DEPLOYMENT-GUIDE.md](../COMMERCIAL-DEPLOYMENT-GUIDE.md)
- [PRODUCTION-READINESS.md](../PRODUCTION-READINESS.md)
- [../../docker/README.md](../../docker/README.md)
- [../administration/administration-documentation.md](../administration/administration-documentation.md)
- [../troubleshooting/troubleshooting-guide.md](../troubleshooting/troubleshooting-guide.md)
