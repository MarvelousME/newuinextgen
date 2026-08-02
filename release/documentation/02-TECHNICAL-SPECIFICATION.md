# 02 — Technical Specification

**Release:** BI 1.9.17 / NGC 1.9.5 · **Generated:** 2026-08-02  
**PDF:** PDF export pending operator tooling (pandoc/wkhtmltopdf). Markdown is authoritative.

## 1. Package matrix

| Package | Version (release) | Path / entry | Responsibility |
|---------|-------------------|--------------|----------------|
| NextGenTutors-BeyondInfinity | 1.9.17 (`BI_VERSION`, `style.css`) | Theme | Presentation, templates, tokens |
| NextGenTutors-Companion | 1.9.5 (`NGC_VERSION`) | `nextgencompanion.php` | Domain, REST `ngc/v1`, CLI, provision |
| NextGenTutors-AI-Integration | packaged (see build manifest) | Plugin | AI BYOK / health / bridge |
| NextGenTutors-Mission-Control | 1.0.0 header | Plugin | Ops control plane; shared orchestrator state |
| NextGenTutors-Plugin-Manager | 1.3.5 | Plugin | Fleet scan / fix |
| NextGenTutors-Html-Importer | packaged | Plugin | One-time HTML migration |
| ngt-ui-library | deploy separately / bridged | `ui-library/` | Shared UI components |

Parent theme: Hello Elementor (required). Third-party fleet (WooCommerce, Fluent*, Elementor, Amelia, PayFast gateway, etc.) detected by provisioning — not all are first-party.

## 2. Provisioning engine

| Item | Value |
|------|-------|
| Engine | `NGC_Provisioning_Engine` |
| Admin UI | WP Admin → Setup Wizard (`admin.php?page=ngc-setup-wizard`) |
| State option | `ngc_provisioning_state` |
| Lock option | `ngc_provisioning_lock` (TTL 900s) |
| Filter | `ngc_provisioning_steps` |

### 32-step catalogue (ids)

| # | ID | Label |
|---|----|-------|
| 1 | `env-preflight` | Environment preflight |
| 2 | `backups` | Backups and restore validation |
| 3 | `wordpress-baseline` | WordPress baseline |
| 4 | `theme` | Theme installation |
| 5 | `first-party-plugins` | First-party plugin installation |
| 6 | `third-party-detect` | Third-party dependency detection |
| 7 | `third-party-install` | Third-party installation/activation |
| 8 | `migrations` | Database migrations |
| 9 | `roles` | Roles and capabilities |
| 10 | `business-profile` | Business profile |
| 11 | `ui-library` | Design tokens and UI library |
| 12 | `pages` | Pages and templates |
| 13 | `menus` | Menus and navigation |
| 14 | `forms` | Forms |
| 15 | `crm` | CRM |
| 16 | `email` | Email and SMTP readiness |
| 17 | `domain-config` | Tutor and student domain configuration |
| 18 | `lms` | LMS |
| 19 | `booking` | Booking |
| 20 | `commerce` | Commerce and payment gateway readiness |
| 21 | `products` | Products, packages, and pricing |
| 22 | `finance` | Wallet, ledger, invoices, refunds, and payouts |
| 23 | `workflows` | Workflow automation |
| 24 | `gamification` | Gamification |
| 25 | `analytics` | Analytics and attribution |
| 26 | `ai-integration` | AI Integration |
| 27 | `mission-control` | Mission Control |
| 28 | `demo-journeys` | Relational demo journeys |
| 29 | `verification` | Verification and evidence |
| 30 | `hardening` | Production hardening |
| 31 | `packaging` | Packaging and release manifest |
| 32 | `deployment-docs` | Deployment and rollback documentation |

Secrets (PayFast / SMTP / AI) are **never** written by the wizard.

## 3. CLI surfaces

```text
wp ngt provision run|catalogue|status|rollback|clear-lock
wp ngt system inspect|preflight|configure|seed|verify|run-all
wp ngt system install|repair|export-report|reset-demo|status|provision
```

Flags commonly used: `--dry-run`, `--force-safe`, `--allow-demo`, `--from=<id>`, `--only=<id>`, `--output=json`.

Demo-specific Companion commands (Phase 14): `wp ngc demo_*` — see `.agent-audit/demo/README.md`.

## 4. Integration contracts

| Integration | Contract | Secret handling |
|-------------|----------|-----------------|
| WooCommerce + PayFast | Checkout + ITN | Operator-only; INPUTS IN-001–004 |
| FluentSMTP | `wp_mail` transport | IN-005 |
| FluentCRM | Contacts / lists | Marketing send gated (IN-019) |
| Amelia | Booking adapter (when present) | PARTIAL if plugin missing |
| AI-Integration | Health + matching assist | BYOK + HMAC (IN-006–007) |
| Prometheus metrics | `/ngc/v1/metrics` | No secrets in scrape path |

## 5. Data & IDs

| Prefix | Entity |
|--------|--------|
| NGT-T | Tutor |
| NGT-S | Student |
| NGT-P | Parent |
| NGT-DEMO-* | Demo personas (demo mode only) |

Custom tables: Companion `wp_ngc_*` (created via migrations / `NGC_Database`). Exact table count may grow with migrations — treat live `SHOW TABLES LIKE '%ngc_%'` as source of truth (UNVERIFIED on production).

## 6. Environments

| Env | URL / note |
|-----|------------|
| Local Docker | http://localhost:8900 |
| Production | https://www.nextgentutors.co.za — **UNVERIFIED** this session; deploy not authorized |
