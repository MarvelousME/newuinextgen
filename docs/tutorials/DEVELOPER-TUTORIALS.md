# Developer Tutorials

Hands-on tutorials for engineers extending the NextGen Tutors platform.

**Prerequisites:** [DEVELOPER-GUIDE.md](../DEVELOPER-GUIDE.md), Docker @ http://localhost:8900  
**Last updated:** 2026-07-13

---

## Tutorial D1: Local environment setup

**Goal:** Running WordPress with all six packages and verification green.

### Steps

1. Open workspace `newuinextgen/`
2. Start Docker:
   ```powershell
   cd docker
   Copy-Item .env.example .env
   .\start.ps1
   ```
3. Install fleet plugins:
   ```powershell
   .\scripts\install-registry-zips.ps1
   ```
4. Verify:
   ```powershell
   cd ..
   powershell -File scripts/verify-solution.ps1
   php NextGenTutors-Companion/scripts/validate.php
   php NextGenTutors-Companion/scripts/verify-ui-library.php
   ```
5. Log in: http://localhost:8900/wp-admin (`admin` / `NextGenAdmin!2026`)

### Expected result

| Check | Pass criteria |
|-------|---------------|
| Theme | `nextgentutors-beyondinfinity` active |
| Companion | Active, 44 `wp_ngc_*` tables |
| Plugin Manager | Active, registry visible |
| Content packs | Command Center + Completion Suite activatable |
| Pages | 23 launch pages from `content/page-map.json` |

**Status:** VERIFIED (static checks); runtime UAT environment-dependent.

---

## Tutorial D2: Add a launch page

**Goal:** New marketing page with UI Library component and CMS section.

### Steps

1. Add slug to `content/page-map.json`:
   ```json
   { "slug": "partnerships", "title": "Partnerships", "template": "full-width" }
   ```
2. Create default content: `inc/defaults/partnerships.php`
3. Register in `inc/pages-registry.php` if not auto-loaded
4. Add UI component usage in default:
   ```php
   echo ng_ui_component( 'hero', [ 'headline' => 'Partner with NextGen' ] );
   ```
5. Re-run page sync (theme activation hook or admin **WordPress Setup** page)
6. Add to primary menu via **Appearance → Menus**

### Verify

- Page loads at `/partnerships/`
- No hardcoded tutor data in partial (scan: `verify-ui-library.php`)

---

## Tutorial D3: Extend Companion with a custom adapter

**Goal:** Integrate a hypothetical external scheduling API.

### Steps

1. Create `NextGenTutors-Companion/includes/adapters/class-ngc-myscheduler-adapter.php`:
   ```php
   class NGC_MyScheduler_Adapter implements NGC_Integration_Adapter {
       public function id(): string { return 'myscheduler'; }
       public function is_available(): bool {
           return defined( 'MYSCHEDULER_API_KEY' );
       }
       public function sync_booking( array $booking ): bool {
           // API call
           return true;
       }
   }
   ```
2. Register in `NGC_Workflow_Orchestrator::adapters()` or via filter
3. Dispatch on booking create:
   ```php
   add_action( 'ngc_booking_created', function ( $booking_id ) {
       $adapter = NGC_MyScheduler_Adapter::instance();
       if ( $adapter->is_available() ) {
           $adapter->sync_booking( NGC_Bookings::get( $booking_id ) );
       }
   } );
   ```
4. Add health check in `NGC_Health_Scanner` if needed
5. Document in `docs/technical/integration-documentation.md`

### Golden rule

Never call adapter from theme — only from Companion.

---

## Tutorial D4: Add a workflow event end-to-end

**Goal:** New form submission → Companion dispatch → theme RTM + email.

### Steps

1. **Companion form handler** — ensure `ngc_form_submitted` fires with form ID
2. **Map event** in `NGC_Workflows::on_form_submitted()` or orchestrator
3. **Theme pack** — add workflow to `content/nextgen-workflow-pack.json`:
   ```json
   {
     "key": "partnership_inquiry",
     "trigger": { "event": "ngt.partnership.submitted" },
     "actions": [
       { "type": "create_rtm_message", "room": "staff", "message": "Partnership inquiry from {{name}}" },
       { "type": "wp_mail_admin", "subject": "Partnership inquiry", "message": "{{name}} — {{email}}" }
     ]
   }
   ```
4. **Bridge event** in `inc/workflows.php` if Companion uses dotted notation:
   ```php
   // Map partnership.submitted → ngt.partnership.submitted for theme pack
   ```
5. **Test:** Submit form → check RTM queue → Command Center mirror → admin email

### Verify

```bash
wp ngc integrate_status
# Check Companion → Workflows → Logs
```

---

## Tutorial D5: UI Library data provider

**Goal:** New `partnership-tier` component with live CMS data.

### Steps

1. **Provider** — `NextGenTutors-Companion/includes/ui-library/class-ngc-ui-partnership-data-provider.php`
2. Register in `NGC_UI_Library::providers()`
3. **Partial** — `template-parts/ui-library/partnership-tier.php`
4. **CSS** — `assets/css/ng-ui-partnership-tier.css`
5. **Enqueue** — register in `inc/ui-library/bootstrap.php`
6. **Shortcode:** `[ng_ui_component slug="partnership-tier"]`

### Verify

```bash
php NextGenTutors-Companion/scripts/verify-ui-library.php
curl http://localhost:8900/wp-json/ngc/v1/ui-library/verify
```

---

## Tutorial D6: Content pack bridge

**Goal:** Wire a new Completion Suite CPT to Companion workflows.

### Steps

1. Register CPT in Completion Suite plugin
2. On `save_post_{cpt}`, hook fires → `NGC_Content_Pack_Bridge::on_operational_post()`
3. Add event mapping in bridge class:
   ```php
   'ngt_my_record' => 'my_record.created',
   ```
4. Add theme pack workflow for `ngt.my_record.created`
5. Import catalog JSON to spec store (optional)
6. Seed AutomatorWP recipe (optional)

See [COMPLETION-SUITE.md](../content-packs/COMPLETION-SUITE.md).

---

## Tutorial D7: REST endpoint

**Goal:** Public read endpoint for partnership tiers.

### Steps

1. Create route in `NextGenTutors-Companion/includes/rest/class-ngc-rest-partnerships.php`
2. Register in REST bootstrap:
   ```php
   register_rest_route( 'ngc/v1', '/partnerships', [
       'methods'  => 'GET',
       'callback' => [ __CLASS__, 'list' ],
       'permission_callback' => '__return_true', // or capability check
   ] );
   ```
3. Document in `docs/apis/api-documentation.md` and `openapi-nextgen.yaml`
4. Classify security: public / authenticated / admin-only per `REST-ENDPOINTS.md`

### Test

```bash
curl http://localhost:8900/wp-json/ngc/v1/partnerships
```

---

## Tutorial D8: Playwright E2E test

**Goal:** Blueprint-aligned test for new form.

### Steps

1. Add spec in `e2e/tests/blueprint-wfXX-my-feature.spec.ts`
2. Follow pattern from existing specs:
   - Navigate to page
   - Fill form fields
   - Submit → expect `?ngc_submitted=form_id`
3. Run:
   ```powershell
   powershell -File scripts/run-playwright.ps1
   ```
4. Requires Docker @ :8900 with demo seed enabled

---

## Tutorial D9: Release build

**Goal:** Produce distributable zips for client handover.

### Steps

1. Align versions:
   ```bash
   php NextGenTutors-Companion/scripts/verify-versions.php
   ```
2. Full verify:
   ```powershell
   powershell -File scripts/verify-solution.ps1
   ```
3. Build:
   ```powershell
   powershell -File scripts/build-release.ps1
   ```
4. Output: `dist/*.zip` — theme, companion, plugin-manager, html-importer
5. Content pack zips: `content/*.zip`

### Handover checklist

See [PRODUCTION-READINESS.md](../PRODUCTION-READINESS.md).

---

## Tutorial D10: WP-CLI operations

**Goal:** Common CLI tasks for developers.

```bash
# Enter wpcli container
cd docker
docker compose --profile setup run --rm wpcli bash

# Inside container
wp plugin list
wp ngc verify
wp ngc integrate_status
wp ngc integrate import
wp ngc process_reminders --dry-run
wp user list --role=tutor
wp post list --post_type=tutors --fields=ID,post_title
```

---

## Related docs

- [DEVELOPER-GUIDE.md](../DEVELOPER-GUIDE.md) — onboarding reference
- [INTEGRATION-CATALOG.md](../workflows/INTEGRATION-CATALOG.md) — events
- [PACKAGES.md](../PACKAGES.md) — package reference
- [../../NextGenTutors-Companion/docs/studio/DEVELOPER-MANUAL.md](../../NextGenTutors-Companion/docs/studio/DEVELOPER-MANUAL.md) — Automation Studio
