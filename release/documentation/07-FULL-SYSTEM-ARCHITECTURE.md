# 07 — Full System Architecture

**Release:** BI 1.9.17 / NGC 1.9.5 · **Generated:** 2026-08-02  
**PDF:** PDF export pending operator tooling (pandoc/wkhtmltopdf). Markdown is authoritative.

## 1. Architectural style

WordPress-hosted **modular monolith**: presentation theme + domain plugin + optional side plugins, integrating a third-party fleet (commerce, CRM, booking, LMS). Domain events and workflow orchestration coordinate side effects; payment and email secrets stay outside the monorepo.

## 2. Logical layers

```text
[ Actors ] → [ BeyondInfinity UI ] → [ Companion domain + REST ]
                    ↓                         ↓
            [ UI Library ]          [ wp_ngc_* + WP users ]
                    ↓                         ↓
            [ Elementor pages ]     [ Woo / Amelia / Fluent / AI ]
```

## 3. Trust boundaries

| Boundary | Inside | Outside |
|----------|--------|---------|
| WP application | Theme, plugins, options, DB | Browser clients |
| Payment | Woo + Companion handlers | PayFast network |
| Email | FluentSMTP | SMTP provider |
| AI | AI-Integration + Companion | Model provider APIs |
| Ops admin | Capability-gated WP Admin | Public internet |

See `12-security-trust-boundaries.svg`.

## 4. Provisioning as architecture

The 32-step `NGC_Provisioning_Engine` is the **canonical install architecture**: ordered, versioned, lockable, filterable, with verify/rollback hooks per step. It complements (does not replace) `wp ngt system` orchestration used for inspect/configure/seed/verify.

## 5. Diagram catalogue

| # | File | Description |
|---|------|-------------|
| 01 | `01-system-context.svg` | Actors and external systems |
| 02 | `02-wordpress-platform-components.svg` | Theme vs plugins |
| 03 | `03-plugin-interoperability.svg` | First-/third-party links |
| 04 | `04-user-roles-and-portals.svg` | Roles and surfaces |
| 05 | `05-data-architecture.svg` | SSOT + tables + IDs |
| 06 | `06-event-and-workflow-architecture.svg` | Events / workflows |
| 07 | `07-parent-minor-registration-sequence.svg` | Registration sequence |
| 08 | `08-tutor-approval-sequence.svg` | Tutor approval |
| 09 | `09-matching-sequence.svg` | Matching |
| 10 | `10-booking-payment-sequence.svg` | Booking + PayFast |
| 11 | `11-ai-governance-sequence.svg` | AI assist governance |
| 12 | `12-security-trust-boundaries.svg` | Trust zones |
| 13 | `13-deployment-topology.svg` | Local vs production |
| 14 | `14-observability-and-recovery.svg` | Metrics / recovery |
| 15 | `15-complete-end-to-end-platform.svg` | Full E2E map |

All under `release/diagrams/`.
