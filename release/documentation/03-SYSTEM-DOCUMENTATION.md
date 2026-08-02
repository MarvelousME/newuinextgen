# 03 — System Documentation

**Release:** BI 1.9.17 / NGC 1.9.5 · **Generated:** 2026-08-02  
**PDF:** PDF export pending operator tooling (pandoc/wkhtmltopdf). Markdown is authoritative.

## 1. System context

External actors: Parents, Students, Tutors, Ops staff, Payment gateway (PayFast), Email provider (SMTP), optional AI providers.

Internal core: WordPress + BeyondInfinity theme + Companion domain plugin, with supporting first-party plugins and a third-party fleet.

Diagram: [`../diagrams/01-system-context.svg`](../diagrams/01-system-context.svg)

## 2. Component map

| Layer | Components |
|-------|------------|
| Presentation | BeyondInfinity, Elementor (optional), UI Library partials |
| Domain | Companion (matching, bookings, payments, workflows, privacy, demo) |
| Ops planes | Mission Control, Plugin Manager, Setup Wizard, Demo Control Centre |
| Intelligence | Companion AI suite + NextGenTutors-AI-Integration bridge |
| Commerce/comms | WooCommerce, PayFast gateway, FluentCRM, FluentSMTP, Amelia |
| Host | Docker local (`docker/`) or production host (Coolify/other) — production UNVERIFIED |

Diagrams: `02-wordpress-platform-components.svg`, `03-plugin-interoperability.svg`

## 3. Runtime data flow (high level)

1. User hits theme templates / shortcodes (`[ngc_*]`).
2. Companion REST (`ngc/v1`) or form handlers enforce `NGC_Access` gates.
3. Domain services mutate `wp_ngc_*` + WP users/roles; emit domain events / outbox.
4. Workflows / AutomatorWP / CRM adapters fan out notifications (sandbox in demo).
5. Payments settle via Woo → PayFast ITN → Companion payment handlers (idempotent).
6. Observability: health scanner, metrics endpoint, system log admin.

## 4. Shared orchestrator state

Mission Control and `wp ngt system` share option `ngt_system_orchestrator_state`. Provisioning uses separate options `ngc_provisioning_state` / `ngc_provisioning_lock`.

## 5. Sacred theme contracts

BeyondInfinity must **not** own: custom `ngc_*` tables, payment settlement, matching scores, CRM/booking adapter calls, AI BYOK keys. Domain stays in Companion.

## 6. Diagram index

| File | Topic |
|------|-------|
| 01–03 | Context, components, plugins |
| 04–06 | Roles, data, events/workflows |
| 07–11 | Sequence diagrams (registration → AI) |
| 12–15 | Security, deploy, observability, E2E |
