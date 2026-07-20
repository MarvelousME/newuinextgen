# Infrastructure Documentation

## Infrastructure Topology (Repository Scope)

- WordPress application host (Apache/Nginx + PHP-FPM equivalent).
- MySQL/MariaDB datastore.
- File deployment under `wp-content/themes` and `wp-content/plugins`.

## Required Services

| Service | Purpose | Required for Full Platform | Status |
|---|---|---|---|
| WordPress + PHP runtime | core application | Yes | VERIFIED |
| MySQL/MariaDB | persistence | Yes | VERIFIED |
| Cron scheduler | health/retry jobs | Yes | VERIFIED |
| WooCommerce plugin | finance hooks | For full finance flows | PARTIAL |
| FluentCRM plugin | CRM workflows | For CRM flows | BLOCKED |
| Amelia plugin/API | scheduling integration | For external calendar parity | PARTIAL |
| MasterStudy plugin | LMS mapping | For LMS workflows | BLOCKED |

## Monitoring/Alerting

- In-app operational visibility: health checks, workflow logs, audit logs.
- Infra-level external monitoring stack is not defined in repository (`NOT VERIFIED`).

