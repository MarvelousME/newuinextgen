# NextGen Tutors — Production Readiness Checklist

Enterprise sign-off checklist for commercial production deployments.

**Use with:** [COMMERCIAL-DEPLOYMENT-GUIDE.md](COMMERCIAL-DEPLOYMENT-GUIDE.md)  
**Last updated:** 2026-07-13

---

## Sign-off record

| Field | Value |
|-------|--------|
| Site URL | _________________________ |
| Environment | ☐ Staging  ☐ Production |
| Release version | Theme _____ · Companion _____ · PM _____ |
| Sign-off date | _________________________ |
| Approved by | _________________________ |

---

## 1. Infrastructure

| # | Requirement | Status | Notes |
|---|-------------|--------|-------|
| 1.1 | PHP 8.2+ with OPcache | ☐ | |
| 1.2 | MySQL 8.0+ / MariaDB 10.6+ | ☐ | |
| 1.3 | TLS certificate valid | ☐ | |
| 1.4 | System cron replaces WP-Cron | ☐ | See `operations/production-cron.md` |
| 1.5 | `memory_limit` ≥ 512M | ☐ | |
| 1.6 | `max_execution_time` ≥ 120s | ☐ | Large zip installs |
| 1.7 | Daily DB backups configured | ☐ | |
| 1.8 | File backup strategy | ☐ | |
| 1.9 | Monitoring / uptime alerts | ☐ | |
| 1.10 | Staging environment mirrors prod | ☐ | |

---

## 2. Core packages

| # | Requirement | Status | Notes |
|---|-------------|--------|-------|
| 2.1 | BeyondInfinity theme active | ☐ | `nextgentutors-beyondinfinity` |
| 2.2 | Hello Elementor parent installed | ☐ | |
| 2.3 | Companion active | ☐ | `NGC_VERSION` _____ |
| 2.4 | Plugin Manager active | ☐ | |
| 2.5 | `wp ngc verify` passes | ☐ | |
| 2.6 | All `wp_ngc_*` tables exist | ☐ | 44 tables |
| 2.7 | Roles installed (parent, student, tutor, etc.) | ☐ | |
| 2.8 | `NGC_ALLOW_DEMO_SEED` false on production | ☐ | |

---

## 3. Fleet plugins

| Plugin | Required | Installed | Active | Configured |
|--------|----------|-----------|--------|------------|
| WooCommerce | Yes | ☐ | ☐ | ☐ |
| Elementor | Yes | ☐ | ☐ | ☐ |
| FluentCRM | Yes | ☐ | ☐ | ☐ |
| FluentSMTP | Yes | ☐ | ☐ | ☐ |
| MasterStudy LMS | Yes | ☐ | ☐ | ☐ |
| GamiPress | Yes | ☐ | ☐ | ☐ |
| AutomatorWP | Yes | ☐ | ☐ | ☐ |
| User Role Editor | Yes | ☐ | ☐ | ☐ |
| Amelia Booking | Yes | ☐ | ☐ | ☐ |
| PayFast Gateway | Yes | ☐ | ☐ | ☐ |

---

## 4. Content packs (recommended)

| # | Requirement | Status |
|---|-------------|--------|
| 4.1 | Command Center installed + setup run | ☐ |
| 4.2 | Completion Suite installed + setup run | ☐ |
| 4.3 | RTM rooms seeded (6 rooms) | ☐ |
| 4.4 | Operational pages created (7 pages) | ☐ |
| 4.5 | Workflow catalog imported | ☐ |
| 4.6 | AutomatorWP recipes seeded | ☐ |

---

## 5. Launch content

| # | Requirement | Status |
|---|-------------|--------|
| 5.1 | 23 launch pages exist per `content/page-map.json` | ☐ |
| 5.2 | Home page kinetic layout active | ☐ |
| 5.3 | Navigation menus synced | ☐ |
| 5.4 | Legal pages (privacy, terms) populated | ☐ |
| 5.5 | Section CMS content reviewed (not demo) | ☐ |
| 5.6 | Tutor CPT has production roster (not demo seed) | ☐ |

---

## 6. Integrations

| # | Integration | Sandbox tested | Production keys |
|---|-------------|----------------|-----------------|
| 6.1 | PayFast | ☐ | ☐ |
| 6.2 | FluentCRM tags/lists | ☐ | ☐ |
| 6.3 | FluentSMTP delivery | ☐ | ☐ |
| 6.4 | Amelia services/employees | ☐ | ☐ |
| 6.5 | MasterStudy courses | ☐ | ☐ |
| 6.6 | GamiPress points/achievements | ☐ | ☐ |
| 6.7 | WooCommerce products | ☐ | ☐ |

---

## 7. Workflow verification

| # | Workflow | Tested |
|---|----------|--------|
| 7.1 | Find a Tutor intake (WF-07/09) | ☐ |
| 7.2 | Tutor application (WF-03) | ☐ |
| 7.3 | Tutor approval (WF-04) | ☐ |
| 7.4 | Parent registration (WF-01) | ☐ |
| 7.5 | Student registration (WF-02) | ☐ |
| 7.6 | Booking created (WF-10) | ☐ |
| 7.7 | Payment received (WF-11) | ☐ |
| 7.8 | Lesson completed (WF-15) | ☐ |
| 7.9 | Progress report submitted | ☐ |
| 7.10 | Support escalation (WF-19/20) | ☐ |
| 7.11 | Tutor payout (WF-16) | ☐ |
| 7.12 | Parent review (WF-17) | ☐ |

---

## 8. Security & compliance

| # | Requirement | Status |
|---|-------------|--------|
| 8.1 | `DISALLOW_FILE_EDIT` true | ☐ |
| 8.2 | Admin 2FA enabled | ☐ |
| 8.3 | POPIA privacy policy published | ☐ |
| 8.4 | Child safety page published | ☐ |
| 8.5 | Consent logging active | ☐ |
| 8.6 | No demo credentials in production | ☐ |
| 8.7 | File permissions correct (755 dirs, 644 files) | ☐ |
| 8.8 | Security headers / WAF | ☐ |

---

## 9. Performance & UX

| # | Requirement | Status |
|---|-------------|--------|
| 9.1 | Home LCP < 3s (target) | ☐ |
| 9.2 | Mobile responsive check | ☐ |
| 9.3 | Sticky header functional | ☐ |
| 9.4 | Floating action dock positioned correctly | ☐ |
| 9.5 | Forms validate client + server side | ☐ |
| 9.6 | Dashboard REST loads for all roles | ☐ |

---

## 10. Observability

| # | Requirement | Status |
|---|-------------|--------|
| 10.1 | Companion health scanner green | ☐ |
| 10.2 | Workflow logs accessible | ☐ |
| 10.3 | Failed workflow retry queue monitored | ☐ |
| 10.4 | Command Center metrics show live data | ☐ |
| 10.5 | Error log monitored (`WP_DEBUG_LOG` off in prod) | ☐ |
| 10.6 | Payout export tested | ☐ | See `operations/payout-export.md` |

---

## 11. Documentation handover

| Deliverable | Provided |
|-------------|----------|
| Admin manual | ☐ |
| Parent/tutor user manuals | ☐ |
| Operator runbook | ☐ |
| API documentation | ☐ |
| Known limitations register | ☐ |
| Support escalation matrix | ☐ |

---

## 12. Automated verification

Run before sign-off:

```powershell
powershell -File scripts/verify-solution.ps1
php NextGenTutors-Companion/scripts/validate.php
php NextGenTutors-Companion/scripts/verify-ui-library.php
npx playwright test   # from e2e/
```

Record results:

| Check | Pass | Date |
|-------|------|------|
| verify-solution.ps1 | ☐ | |
| validate.php | ☐ | |
| verify-ui-library.php | ☐ | |
| Playwright E2E | ☐ | |
| wp ngc verify | ☐ | |

---

## Approval

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Technical lead | | | |
| Product owner | | | |
| Operations | | | |

**Production go-live authorized:** ☐ Yes  ☐ No — blockers: _______________
