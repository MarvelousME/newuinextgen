# Administrator guide

- WP-Admin → NextGen Companion / Mission Control for domain data, provisioning, and health.
- Plugin Manager shows first-party stack status (Companion required; 3D/subjects required for visual parity; Html Importer optional).
- Pages: keep slugs in PAGE-MATRIX.csv. Do not delete Home, Find a Tutor, dashboards, login, register.
- Menu: Primary location must use **NextGen Primary Grouped**.
- Users: assign roles `parent` / `tutor` / `student` (Companion caps). Do not rely on theme read-only stubs in production.
- Integrations: Woo + PayFast for checkout; Amelia for booking calendars; MasterStudy for LMS; FluentCRM for lifecycle email. Missing plugins must not fatal the site.
- Html Importer: dry-run, import, verify, deactivate.