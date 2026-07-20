# NextGen Tutors — Documentation Index

**Updated:** 2026-07-20

## Reading paths

| If you are… | Read this first |
|-------------|-----------------|
| New engineer | [platform-architecture.md](./platform-architecture.md) → [tutorials/01-phase14-demo-walkthrough.md](./tutorials/01-phase14-demo-walkthrough.md) |
| Architect | [platform-architecture.md](./platform-architecture.md) → ADRs |
| API / frontend | [api-reference-ngc-v1.md](./api-reference-ngc-v1.md) |
| Ops / privacy / SRE | [ops-privacy-observability.md](./ops-privacy-observability.md) |
| Demo evaluator | [tutorials/01-phase14-demo-walkthrough.md](./tutorials/01-phase14-demo-walkthrough.md) + [../.agent-audit/demo/LIVE-DEMONSTRATION-RUNBOOK.md](../.agent-audit/demo/LIVE-DEMONSTRATION-RUNBOOK.md) |

Doc generation plan: [DOCUMENTATION-PLAN.md](./DOCUMENTATION-PLAN.md)

## Start here

| Doc | Audience | Purpose |
|-----|----------|---------|
| [../BESPOKE-THEME-AND-COMPANION-GUIDE.md](../BESPOKE-THEME-AND-COMPANION-GUIDE.md) | Implementers | Step-by-step bespoke of BeyondInfinity + Companion |
| [platform-architecture.md](./platform-architecture.md) | Architects / engineers | Living system architecture |
| [master-directive-status.md](./master-directive-status.md) | All | Stage status, paths, smoke results |
| [project-boundaries.md](./project-boundaries.md) | Architects | What not to activate / merge |
| [architecture.md](./architecture.md) | Architects | Shorter live summary (UI-focused) |

## Platform APIs & ops

| Doc | Purpose |
|-----|---------|
| [api-reference-ngc-v1.md](./api-reference-ngc-v1.md) | REST `ngc/v1` + CLI companions |
| [ops-privacy-observability.md](./ops-privacy-observability.md) | PRIV-001 retention/export + OBS-001 metrics |
| [../.agent-audit/demo/README.md](../.agent-audit/demo/README.md) | Phase 14 demo commands |

## Tutorials

| Doc | Purpose |
|-----|---------|
| [tutorials/01-phase14-demo-walkthrough.md](./tutorials/01-phase14-demo-walkthrough.md) | Hands-on relational demo seed → login → evidence |

## UI library

| Doc | Purpose |
|-----|---------|
| [ui-library-guide.md](./ui-library-guide.md) | Developer guide — APIs, tokens, assets, adding components |
| [dual-ui-stack.md](./dual-ui-stack.md) | NGC_UI_Library + NGT_UI coexistence |
| [component-catalogue-stage5.md](./component-catalogue-stage5.md) | Component list and shortcodes |
| [gutenberg-guide.md](./gutenberg-guide.md) | Block editor |
| [elementor-guide.md](./elementor-guide.md) | Elementor widget |
| [wpbakery-guide.md](./wpbakery-guide.md) | WPBakery adapter (`ngt_ui_vc` verified; js_composer optional) |

## Security & ops

| Doc | Purpose |
|-----|---------|
| [legacy-plugin-guard-runbook.md](./legacy-plugin-guard-runbook.md) | Deny-list ops and verification |
| [adr/ADR-001-legacy-plugin-deny.md](./adr/ADR-001-legacy-plugin-deny.md) | Legacy plugin deny policy |
| [adr/ADR-002-dual-ui-coexistence.md](./adr/ADR-002-dual-ui-coexistence.md) | Dual UI coexistence policy |
| [content-enhancement/README.md](../content-enhancement/README.md) | DO NOT ACTIVATE archived zips |

## Refactor & quality

| Doc | Purpose |
|-----|---------|
| [refactor-backlog.md](./refactor-backlog.md) | Prioritized clean-up plan |
| [sanitisation-report.md](./sanitisation-report.md) | Stage 8 gate (not started) |
| [stage-2-safety-baseline.md](./stage-2-safety-baseline.md) | Safety baseline |

## Audit artifacts (not for deploy)

- `audit-reports/content-enhancement/FINAL-REPORT.md`
- `audit-reports/magicui-conversion-matrix.json`
- `.agent-audit/AUTONOMOUS-CODING-AGENT-MASTER-DIRECTIVE.md`
- `.agent-audit/reports/demo-implementation-report.md`

## Quick verification

```powershell
php NextGenTutors-Companion/tests/run.php
php ui-library/tests/catalog-snapshot.php
php ui-library/tests/integration-smoke.php
cd docker
docker compose run --rm --entrypoint wp wpcli --path=/var/www/html eval "echo NGC_Legacy_Plugin_Guard::is_denied('nextgen-tutors-core/nextgen-tutors-core.php')?'DENY_OK':'FAIL';" --allow-root
```

## CI (GitHub Actions)

| Workflow | Triggers | Jobs |
|----------|----------|------|
| `ci.yml` | push / PR | `php-unit` (run.php, PHPUnit, snapshots, integration), `docker-smoke` (guard, IC, VC, demo seed, **full Playwright**) |
| `docs-lint.yml` | documentation changes | Internal markdown link check |
