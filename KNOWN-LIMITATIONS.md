# KNOWN-LIMITATIONS.md

Honest risk register — **REVAMP** (updated 2026-07-06).  
Packages: BeyondInfinity **1.9.1**, NextGen Companion **1.9.0**, Plugin Manager **1.3.1**.

Green health checks are **presence/configuration signals only** — not proof of bookings, payments, POPIA compliance, or live tutor onboarding.

---

## Environment

| ID | Limitation | Severity | Status |
|----|------------|----------|--------|
| ENV-01 | Live WordPress UAT not executed in this workspace | High | **NOT VERIFIED** |
| ENV-02 | Rate limiter uses single-site transients | Medium | **PARTIAL** — works per WP instance; not distributed |
| ENV-03 | Build zips verified locally via `scripts/build-release.ps1` | Low | **VERIFIED** (filesystem) |
| ENV-04 | Docker defines `NGC_ALLOW_DEMO_SEED` for local demo tutors | Low | **VERIFIED** — production must omit this constant |

## Security

| ID | Limitation | Severity | Status |
|----|------------|----------|--------|
| SEC-01 | POPIA lawful basis for tracking not legal-reviewed | Critical | **SECURITY_REVIEW_REQUIRED** |
| SEC-02 | Frontend exception capture disabled by default | Low | **VERIFIED** — opt-in filter |
| SEC-03 | Public REST throttled but not authenticated | Medium | **PARTIAL** — see REST-ENDPOINTS.md |

## Health / verification

| ID | Limitation | Severity | Status |
|----|------------|----------|--------|
| HL-01 | Cookie/tracking checks are presence or NOT_VERIFIED, not behavioral E2E | Medium | **VERIFIED** (honest labels) |
| HL-02 | Demo tutors visible in marketplace only when `NGC_ALLOW_DEMO_SEED` / filter allows | Medium | **VERIFIED** — excluded in production |
| HL-03 | `wp ngc verify` requires WP bootstrap | Low | **NOT VERIFIED** in CI without WP |

## Matching / marketplace

| ID | Limitation | Severity | Status |
|----|------------|----------|--------|
| MAT-01 | Canonical source is Tutor CPT | Low | **VERIFIED** in code |
| MAT-02 | Amelia / MasterStudy auto-link on sync | Medium | **NOT VERIFIED** — Amelia deactivated locally when DB tables missing |
| MAT-03 | Full `NGC_Marketplace` REST + shortcode + glass UI | Low | **VERIFIED** in REVAMP 1.9.x |

## Integrations

| ID | Limitation | Severity | Status |
|----|------------|----------|--------|
| INT-01 | Amelia booking E2E | High | **NOT VERIFIED** |
| INT-02 | FluentCRM workflows | High | **NOT VERIFIED** |
| INT-03 | GamiPress / WooCommerce payments | High | **NOT VERIFIED** |
| INT-04 | PayFast sandbox checkout E2E | High | **VERIFIED** — `scripts/payfast-e2e-docker.php` (checkout redirect + ITN) |

## Design system (Beyond-Infinity)

| ID | Limitation | Severity | Status |
|----|------------|----------|--------|
| DS-01 | Aurora / constellation / bento implemented in theme CSS+JS (not React/shadcn) | Low | **VERIFIED** — `nbi-infinity` assets |
| DS-02 | Spline/Three.js embed not wired; 2D canvas constellation used | Low | **PARTIAL** |
| DS-03 | Playwright workflow suite | Medium | **PARTIAL** — 26/31 PASS (form `ngc_submitted` redirect failures) |

## Test evidence (static)

```
Date: 2026-07-06
Command: php NextGenTutors-Companion/scripts/validate.php
Result: PHP lint + smoke + integrate — VERIFIED (when exit 0)

Command: docker/scripts/seed-demo-tutors.ps1
Result: Demo CPT + hero mod — run after redeploy

Command: scripts/run-playwright.ps1
Result: NOT VERIFIED this session — re-run before acceptance gate
```
