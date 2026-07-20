# Verification Documentation

## Verification Layers

| Layer | Mechanism | Coverage | Status |
|---|---|---|---|
| Syntax/static validation | `php NextGenTutors-Companion/scripts/validate.php` | plugin PHP files + required manifests | VERIFIED |
| Runtime health checks | `NGC_Verification::run_checks()` | tables/roles/routes/shortcodes/platform checks | VERIFIED |
| Local repair + report | `scripts/platform-verification-repair.php` | autoload, showcase tutor, CRM/Amelia bootstrap, consent/attribution seed | VERIFIED |
| Platform health checks | `NGC_Health_Scanner::full_scan()` | plugin, theme, DB, integrations, platform features | VERIFIED |
| SWOT / gap analysis | [PLATFORM-SWOT-GAP-ANALYSIS.md](./PLATFORM-SWOT-GAP-ANALYSIS.md) | strengths, weaknesses, opportunities, threats | CURRENT |
| Workflow verification | workflow admin verification dashboard | adapter readiness, template presence | VERIFIED |
| Calendar verification | tutor calendar checks | service/route/shortcode/demo/fallback | VERIFIED |
| E2E scripts | `amelia-e2e-docker.php`, `payfast-e2e-docker.php` | booking + payment paths | MANUAL / DOCKER |

## Required Verification Checklist

- [x] Required tables exist
- [x] Required columns/indexes represented in schema definitions
- [x] Required roles/caps exist
- [x] Required demo payloads exist
- [x] Required REST routes exist
- [x] Tracking/cookie/consent hooks are present
- [x] Consent_log + attribution bootstrap on local stack
- [x] Export engine, AI models/agents/policy, rate limiter load via `NGC_Core_Loader`
- [x] Theme live CPT helper (`bi_get_live_tutors`) with showcase tutor
- [x] Dashboard analytics endpoints return structured envelopes
- [x] Public tutor calendar route and shortcode exist
- [x] Booking conflict prevention guard exists
- [x] Amelia direct-mode verification without API key (Docker)
- [x] FluentCRM list/tag bootstrap before adapter verify

## Outstanding Runtime Checks (Production)

- [ ] WooCommerce PayFast **live** settlement E2E (sandbox configured; run `payfast-e2e-docker.php`)
- [ ] Amelia live appointment booking E2E (run `amelia-e2e-docker.php`)
- [ ] GamiPress achievement trigger E2E
- [ ] MasterStudy course enrollment from parent checkout
- [ ] POPIA lawful-basis legal sign-off (`SECURITY_REVIEW_REQUIRED`)
- [ ] Browser cookie banner → 3 cookies + page_view analytics chain
- [ ] End-to-end staging UAT for all parent/tutor journeys

## Quick Commands (Docker)

```powershell
# Repair local gaps and print verification matrix
docker compose --profile setup run --rm --entrypoint wp wpcli eval-file `
  wp-content/plugins/NextGenTutors-Companion/scripts/platform-verification-repair.php --allow-root

# Amelia safe activate (if plugin tables missing)
docker compose --profile setup run --rm --entrypoint wp wpcli eval-file `
  wp-content/plugins/NextGenTutors-Companion/scripts/amelia-safe-activate.php --allow-root
```

See [PLATFORM-SWOT-GAP-ANALYSIS.md](./PLATFORM-SWOT-GAP-ANALYSIS.md) for full SWOT and gap tables.
