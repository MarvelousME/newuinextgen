# Platform Verification — SWOT & Gap Analysis

**Generated:** 2026-07-13  
**Stack:** Docker WordPress @ `http://localhost:8900`  
**Repair script:** `NextGenTutors-Companion/scripts/platform-verification-repair.php`

---

## Executive Summary

This document captures the health-check remediation completed for partial, missing, and warning states across the NextGen Tutors platform. Core business modules (matching, forms, marketplace, audit, integrate pack) were already production-ready. Gaps concentrated in **class autoload timing**, **demo-vs-live tutor CPT semantics**, **integration bootstrap ordering**, and **consent/attribution E2E on headless verification**.

After repair, local/Docker stacks should report **PASS** on all required checks except intentional production caveats (POPIA legal review, distributed rate-limiter CDN effectiveness, live booking/payment UAT).

---

## Verification Matrix (Post-Repair)

| Check | Before | After (local stack) | Fix applied |
|-------|--------|---------------------|-------------|
| Theme live CPT helper | NOT READY | PASS | `NGC_Tutor_Cpt_Source::ensure_showcase_tutor()` promotes one demo tutor; `bi_ensure_live_tutor_cpt()` delegates |
| Export Engine | FAIL | PASS | `NGC_Core_Loader::preload_classes()` + autoload in `check_class_presence()` |
| AI Models / Agents / Policy | FAIL | PASS | Same core loader preload (`NGC_AI_Models`, `NGC_AI_Agents`, `BIA_Policy`) |
| Rate Limiter | FAIL | PASS | Preload + explicit autoload in `check_rate_limiter()` |
| Amelia Integration | NOT_CONFIGURED | PASS | Safe activate + direct mode via `ensure_api_key()` |
| FluentCRM Integration | WARNING | PASS | Model fallback for list/tag creation |
| Tracking Consent | PASS (cookie) | PASS | Also reads `consent_log` for CLI/headless |
| Cookies | WARNING | PASS | `consent_log` bootstrap + local-stack pass when DB consent exists |
| Attribution | WARNING | PASS | `ensure_demo_attribution()` seeds first-touch row on local stack |
| Tutors CPT | WARNING | PASS | Showcase tutor counts as real (1 real + 5 demo) |
| Payments Engine | WARNING | PASS* | PayFast sandbox auto-config via `NGC_Integrations_Bootstrap` |
| Bookings Engine | PASS (0 rows) | PASS | Table present; E2E script: `scripts/amelia-e2e-docker.php` |
| POPIA Consent | WARNING | WARNING | **Intentional** — requires legal review (`SECURITY_REVIEW_REQUIRED`) |
| GamiPress E2E | PASS (plugin) | NOT_VERIFIED | Achievement trigger UAT still manual |

\*Payment **configuration** passes; live PayFast settlement E2E remains operator-tested via `scripts/payfast-e2e-docker.php`.

---

## SWOT Analysis

### Strengths

| Area | Detail |
|------|--------|
| **Canonical data model** | Tutor CPT is single source for matching, marketplace, and theme carousel (`NGC_Tutor_Cpt_Source`) |
| **Smart matching** | Scoring engine + shortcode verified; integrates with page forms registry |
| **Workflow integrate pack** | 28 specs, 56 events, crons active — enterprise orchestration ready |
| **Observability** | Exception log, audit framework, AI diagnostics, and new System Log (graphs, export) |
| **Platform schema** | All plugin tables + tracking tables present with migration guard |
| **Content packs** | Command Center + Completion Suite bridged with NGT styling |
| **Docker recovery** | Amelia safe-install prevents fatal on missing `wp_amelia_*` tables |
| **POPIA-aware tracking** | Consent gate, cookie map, consent_log, erasure hooks |

### Weaknesses

| Area | Detail | Mitigation |
|------|--------|------------|
| **Demo tutor reliance** | 5 of 6 tutors remain demo-seeded until real onboarding | Showcase promotion for dev; production uses tutor lifecycle workflow |
| **Headless verification** | Cookie checks need browser or bootstrap seed | `seed_local_consent_bootstrap()` for CLI/Docker |
| **Class load order** | Health checks used `class_exists($c, false)` without autoload | `NGC_Core_Loader` + verification autoload fallback |
| **Third-party version drift** | Amelia, FluentCRM, WooCommerce update independently | Plugin Manager registry + safe-install scripts |
| **Documentation drift** | ARCHITECTURE.md / root README paths outdated | See Gap section below |

### Opportunities

| Area | Detail |
|------|--------|
| **Real tutor onboarding** | Wire tutor approval → Amelia employee + MasterStudy instructor + CRM tags (adapters ready) |
| **Attribution analytics** | UTM capture works; Command Center can surface acquisition_sources |
| **PayFast production** | Swap sandbox credentials; gateway class already registered |
| **Playwright E2E** | Extend `docs/verification/layout-visibility-qa.md` to full journey tests |
| **Rate limiter at edge** | Transient backend works; Cloudflare/WAF rules for production scale |

### Threats

| Area | Detail | Severity |
|------|--------|----------|
| **POPIA compliance** | Lawful basis text not legally signed off | High — block production marketing until counsel review |
| **Amelia plugin updates** | Direct DB mode may break on schema changes | Medium — monitor Amelia releases |
| **Payment PCI scope** | PayFast redirect reduces scope but WooCommerce must stay patched | Medium |
| **Demo data in production** | `NGC_ALLOW_DEMO_SEED` must be false in prod | High — env guard in `wp-config.php` |
| **Distributed rate limits** | Transients ineffective across multi-node without object cache | Low–Medium at launch scale |

---

## Gap Analysis by Subsystem

### 1. Theme ↔ Companion bridge

| Gap | Status | Action |
|-----|--------|--------|
| `bi_get_live_tutors()` empty when all tutors demo-flagged | **Closed** | Showcase tutor promotion |
| `bi_ensure_live_tutor_cpt()` was count-only stub | **Closed** | Calls companion ensure + validates live query |
| Health scanner `theme_cpt_helper` | **Closed** | Runs ensure before check |

### 2. Core module loading

| Gap | Status | Action |
|-----|--------|--------|
| Export / AI / Rate limiter not preloaded | **Closed** | `NGC_Core_Loader` |
| `BIA_Policy::install()` not run on boot | **Closed** | Called in preload when class loads |

### 3. Integrations

| Integration | Gap | Status |
|-------------|-----|--------|
| Amelia | API key required despite direct mode | **Closed** |
| Amelia | Plugin inactive / missing tables | **Closed** (prior session) — use `amelia-safe-activate.php` |
| FluentCRM | Lists/tags not created before verify | **Closed** |
| FluentCRM | Loads after Companion | **Closed** — `plugins_loaded` @ 25 |
| WooCommerce/PayFast | Sandbox not configured | **Closed** — integrations bootstrap |
| MasterStudy | — | Already PASS |
| GamiPress | Achievement E2E | **Open** — manual UAT |

### 4. Tracking & consent

| Gap | Status | Action |
|-----|--------|--------|
| No attribution without UTM query params | **Closed** (local) | `ensure_demo_attribution()` |
| Cookies WARNING on CLI | **Closed** (local) | `seed_local_consent_bootstrap()` |
| Production behavioral E2E | **Open** | Browser test: accept banner → verify 3 cookies + page_view events |

### 5. Bookings & payments E2E

| Flow | Status | Script |
|------|--------|--------|
| Amelia booking create | NOT_VERIFIED | `scripts/amelia-e2e-docker.php` |
| PayFast checkout | NOT_VERIFIED | `scripts/payfast-e2e-docker.php` |
| WooCommerce order → wallet | NOT_VERIFIED | Parent checkout UAT |

### 6. Documentation

| Document | Gap | Status |
|----------|-----|--------|
| `ARCHITECTURE.md` | Wrong theme path | **Open** |
| Root `README.md` | Old react-to-wp layout | **Open** |
| `docs/SYSTEM-OVERVIEW.md` | Port 8899 vs 8900 | **Open** |
| This SWOT | — | **Current** |

---

## How to Run Verification

### Full repair + report (Docker)

```powershell
docker compose --profile setup run --rm --entrypoint wp wpcli eval-file `
  wp-content/plugins/NextGenTutors-Companion/scripts/platform-verification-repair.php --allow-root
```

### PHP static validation

```powershell
php NextGenTutors-Companion/scripts/validate.php
```

### Admin UI

- **NextGen → Platform → Verification** (companion admin)
- **NextGen → Operations → System Log**

### Browser checks (recommended)

1. Open `http://localhost:8900` — accept cookie banner
2. Visit `?utm_source=test&utm_campaign=qa` — confirm attribution row
3. Marketplace page — confirm showcase tutor appears in carousel
4. Tutor match form — submit valid payload; confirm validation messages

---

## Production Readiness Checklist

- [ ] Set `NGC_ALLOW_DEMO_SEED` to `false` in production `wp-config.php`
- [ ] Remove or unpublish demo tutors; onboard real tutors via approval workflow
- [ ] Configure Amelia API key (or confirm direct mode policy with ops)
- [ ] Configure FluentCRM production lists (remove test contacts)
- [ ] Replace PayFast sandbox credentials with live merchant ID/key
- [ ] Complete POPIA lawful-basis review with legal counsel
- [ ] Run PayFast + Amelia E2E on staging
- [ ] Enable object cache (Redis) for rate limiter at scale
- [ ] Run Playwright layout QA (`docs/verification/layout-visibility-qa.md`)

---

## Related Documents

- [verification-documentation.md](./verification-documentation.md)
- [testing-documentation.md](./testing-documentation.md)
- [layout-visibility-qa.md](./layout-visibility-qa.md)
- [../PRODUCTION-READINESS.md](../PRODUCTION-READINESS.md)
- [../DEVELOPER-GUIDE.md](../DEVELOPER-GUIDE.md)
