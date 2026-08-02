# ADR-004 — Enterprise Administration Platform

## Status

Accepted — 2026-07-27

## Context

NextGen Tutors exposed many plugin top-level menus and inconsistent admin UIs. Phase 0 unified them under a single WP parent. Stakeholders require a Dynamics/Salesforce-class administration experience: capability IA, design tokens, nested navigation, shared grids/CRUD/export, and a notification centre — while remaining native to WordPress.

## Decision

1. Extend the PHP + vanilla JS `NGC_Admin_*` shell (no full React SPA rewrite).
2. Branding via `NGC_Platform_Version` (`NEXT GEN TUTORS v1.0`); package versions remain `NGC_VERSION` / `BI_VERSION`.
3. Navigation is a custom capability sidebar with DnD layout persistence; WP submenus remain for deep links.
4. Visual styling is driven exclusively by `--ngt-admin-*` CSS tokens + Theme Designer.
5. Entity list/detail/export for pilots uses metadata registry + shared Grid/CRUD/Export adapters calling domain services.
6. WordPress admin notices on NGT screens are routed into a floating Notification Centre.

## Consequences

- Plugins inherit chrome by registering screens/entities — no per-plugin themes.
- Education drill-downs ship as placeholders until Phase 3 product CRUD.
- Dual WP menu + custom nav requires responsive hide of custom nav on small screens.

## Alternatives considered

- Full React admin SPA — rejected for delivery risk and WP integration cost.
- Replacing `#adminmenu` entirely — deferred to Phase 3.
