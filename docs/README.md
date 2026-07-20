# NextGen Tutors — Enterprise Documentation Suite

**Commercial / production documentation for the NextGen Tutors WordPress platform.**

| Field | Value |
|-------|--------|
| **Product** | NextGen Tutors — accessible tutoring marketplace (South Africa) |
| **Stack** | WordPress 6.7+ · PHP 8.2+ · MySQL 8.0 |
| **Theme** | BeyondInfinity (`BI_VERSION` 1.9.9) — workspace root |
| **Core plugin** | NextGenTutors-Companion (`NGC_VERSION` 1.9.0) |
| **Local dev** | Docker @ http://localhost:8900 |
| **Last doc refresh** | 2026-07-13 |

---

## Start here

| Audience | Document | Purpose |
|----------|----------|---------|
| **Everyone** | [SYSTEM-OVERVIEW.md](SYSTEM-OVERVIEW.md) | Whole-system map, data flow, verification status |
| **Operators (post-plugin)** | [tutorials/BEYONDINFINITY-SEQUENTIAL-SETUP.md](tutorials/BEYONDINFINITY-SEQUENTIAL-SETUP.md) | **Step-by-step: run BeyondInfinity + all features after required plugins are installed** |
| **Executives / clients** | [client/NEXTGEN-TUTORS-ENTERPRISE-PRODUCT-SPECIFICATION.md](client/NEXTGEN-TUTORS-ENTERPRISE-PRODUCT-SPECIFICATION.md) | Product spec, personas, competitive positioning |
| **Commercial deploy** | [COMMERCIAL-DEPLOYMENT-GUIDE.md](COMMERCIAL-DEPLOYMENT-GUIDE.md) | Licensed deployment, environments, go-live |
| **Production ops** | [PRODUCTION-READINESS.md](PRODUCTION-READINESS.md) | Enterprise checklist, SLOs, sign-off |
| **Developers** | [DEVELOPER-GUIDE.md](DEVELOPER-GUIDE.md) | Onboarding, contracts, extension points |
| **Architects** | [../ARCHITECTURE.md](../ARCHITECTURE.md) | SOLID package boundaries (repo root) |
| **Operators** | [tutorials/OPERATOR-TUTORIALS.md](tutorials/OPERATOR-TUTORIALS.md) | Plugin Manager, Docker, fleet install |
| **End users** | [tutorials/user-manuals.md](tutorials/user-manuals.md) | Parent, tutor, admin, finance, support manuals |

---

## Status taxonomy

All capability statements use one of:

| Status | Meaning |
|--------|---------|
| **VERIFIED** | Implemented in code; syntax/static checks pass |
| **PARTIAL** | Implemented; runtime or third-party dependency gaps remain |
| **NOT VERIFIED** | Not proven in target environment (requires UAT) |
| **BLOCKED** | External service, license, or host capability required |

---

## Package documentation

| Package | Folder | Doc |
|---------|--------|-----|
| BeyondInfinity theme | Workspace root | [PACKAGES.md#1-beyondinfinity-theme](PACKAGES.md#1-beyondinfinity-theme) |
| Companion | `NextGenTutors-Companion/` | [PACKAGES.md#2-nextgentutors-companion](PACKAGES.md#2-nextgentutors-companion) |
| Plugin Manager | `NextGenTutors-Plugin-Manager/` | [PACKAGES.md#3-nextgentutors-plugin-manager](PACKAGES.md#3-nextgentutors-plugin-manager) |
| Html Importer | `NextGenTutors-Html-Importer/` | [PACKAGES.md#4-nextgentutors-html-importer](PACKAGES.md#4-nextgentutors-html-importer) |
| Command Center | `content/_extracted/nextgen-command-center-v1.0/` | [content-packs/COMMAND-CENTER.md](content-packs/COMMAND-CENTER.md) |
| Completion Suite | `content/_extracted/nextgen-completion-suite/` | [content-packs/COMPLETION-SUITE.md](content-packs/COMPLETION-SUITE.md) |

---

## Tutorials

| Tutorial | Path |
|----------|------|
| **BeyondInfinity sequential setup (after plugins)** | [tutorials/BEYONDINFINITY-SEQUENTIAL-SETUP.md](tutorials/BEYONDINFINITY-SEQUENTIAL-SETUP.md) |
| Sequential platform tutorials (install → journeys) | [tutorials/sequential-tutorials.md](tutorials/sequential-tutorials.md) |
| Developer hands-on tutorials | [tutorials/DEVELOPER-TUTORIALS.md](tutorials/DEVELOPER-TUTORIALS.md) |
| Operator / fleet tutorials | [tutorials/OPERATOR-TUTORIALS.md](tutorials/OPERATOR-TUTORIALS.md) |
| Parent manual | [tutorials/parent/manual.md](tutorials/parent/manual.md) |
| Student manual | [tutorials/student/manual.md](tutorials/student/manual.md) |
| Tutor manual | [tutorials/tutor/manual.md](tutorials/tutor/manual.md) |
| Admin manual | [tutorials/admin/manual.md](tutorials/admin/manual.md) |
| Finance manual | [tutorials/finance/manual.md](tutorials/finance/manual.md) |
| Support manual | [tutorials/support/manual.md](tutorials/support/manual.md) |
| Automation Studio (Companion) | [../NextGenTutors-Companion/docs/studio/](../NextGenTutors-Companion/docs/studio/) |

---

## Workflows & integrations

| Document | Contents |
|----------|----------|
| [workflows/INTEGRATION-CATALOG.md](workflows/INTEGRATION-CATALOG.md) | **Complete** event catalog, integrate pack, v2 JSON, AutomatorWP |
| [workflows/workflow-documentation.md](workflows/workflow-documentation.md) | WF-01–WF-25 blueprint catalog |
| [workflows/FLOW-GAP-REPORT.md](workflows/FLOW-GAP-REPORT.md) | Blueprint SVG → runtime gaps |
| [enterprise-blueprint/workflows/README.md](enterprise-blueprint/workflows/README.md) | Per-workflow BPMN specs |
| [enterprise-blueprint/APPENDIX-A-RBAC-MATRIX.md](enterprise-blueprint/APPENDIX-A-RBAC-MATRIX.md) | Roles & capabilities |
| [enterprise-blueprint/APPENDIX-B-TRIGGER-MATRIX.md](enterprise-blueprint/APPENDIX-B-TRIGGER-MATRIX.md) | Event triggers |
| [enterprise-blueprint/APPENDIX-C-COMPANION-BOUNDARY.md](enterprise-blueprint/APPENDIX-C-COMPANION-BOUNDARY.md) | Theme vs Companion boundary |
| [../NextGenTutors-Companion/integrate/README.md](../NextGenTutors-Companion/integrate/README.md) | Integrate pack runtime + WP-CLI |

---

## Architecture & specifications

| Document | Contents |
|----------|----------|
| [architecture/solution-architecture.md](architecture/solution-architecture.md) | Five architecture views |
| [architecture/business-requirements-specification.md](architecture/business-requirements-specification.md) | Business requirements |
| [architecture/system-requirements-specification.md](architecture/system-requirements-specification.md) | System requirements |
| [architecture/functional-requirements-specification.md](architecture/functional-requirements-specification.md) | Functional requirements |
| [architecture/non-functional-requirements-specification.md](architecture/non-functional-requirements-specification.md) | NFR matrix |
| [technical/technical-specification.md](technical/technical-specification.md) | Technical stack |
| [technical/integration-documentation.md](technical/integration-documentation.md) | Third-party integration matrix |
| [functional/functional-specification.md](functional/functional-specification.md) | Module inventory |
| [database/database-documentation.md](database/database-documentation.md) | Schema documentation |
| [apis/api-documentation.md](apis/api-documentation.md) | REST/AJAX families |
| [apis/openapi-nextgen.yaml](apis/openapi-nextgen.yaml) | OpenAPI machine-readable spec |

---

## Operations & deployment

| Document | Contents |
|----------|----------|
| [deployment/deployment-documentation.md](deployment/deployment-documentation.md) | Deployment procedures |
| [deployment/infrastructure-documentation.md](deployment/infrastructure-documentation.md) | Infrastructure topology |
| [operations/operations-documentation.md](operations/operations-documentation.md) | Ops runbook |
| [operations/production-cron.md](operations/production-cron.md) | Cron replacement |
| [operations/payout-export.md](operations/payout-export.md) | PayFast payout export |
| [operations/audit-documentation.md](operations/audit-documentation.md) | Audit framework |
| [administration/administration-documentation.md](administration/administration-documentation.md) | Admin screen catalog |
| [security/security-documentation.md](security/security-documentation.md) | Security controls |
| [troubleshooting/troubleshooting-guide.md](troubleshooting/troubleshooting-guide.md) | Issue recovery matrix |
| [../docker/README.md](../docker/README.md) | Local Docker stack |
| [../KNOWN-LIMITATIONS.md](../KNOWN-LIMITATIONS.md) | Honest risk register |

---

## Verification & quality

| Document | Contents |
|----------|----------|
| [verification/verification-documentation.md](verification/verification-documentation.md) | Verification layers |
| [verification/testing-documentation.md](verification/testing-documentation.md) | Test strategy, E2E, UAT |
| [verification/layout-visibility-qa.md](verification/layout-visibility-qa.md) | Layout QA checklist |
| [ui-library/VERIFICATION-CHECKLIST.md](ui-library/VERIFICATION-CHECKLIST.md) | UI Library acceptance |
| [ENTERPRISE-PRODUCTION-SWOT.md](ENTERPRISE-PRODUCTION-SWOT.md) | Production SWOT |
| [CODE-REVIEW-2026-07-06.md](CODE-REVIEW-2026-07-06.md) | UI Library code review |

---

## Companion implementation packages (developer deep-dive)

| Document | Contents |
|----------|----------|
| [../NextGenTutors-Companion/IMPLEMENTATION-PACKAGE.md](../NextGenTutors-Companion/IMPLEMENTATION-PACKAGE.md) | Core CPTs, REST, admin |
| [../NextGenTutors-Companion/WORKFLOW-INTEGRATION-PACKAGE.md](../NextGenTutors-Companion/WORKFLOW-INTEGRATION-PACKAGE.md) | Workflow orchestrator |
| [../NextGenTutors-Companion/REAL-DATA-FIRST-IMPLEMENTATION-PACKAGE.md](../NextGenTutors-Companion/REAL-DATA-FIRST-IMPLEMENTATION-PACKAGE.md) | Analytics, real data layer |
| [../NextGenTutors-Companion/TUTOR-CALENDAR-IMPLEMENTATION-PACKAGE.md](../NextGenTutors-Companion/TUTOR-CALENDAR-IMPLEMENTATION-PACKAGE.md) | Public tutor calendar |
| [../NextGenTutors-Companion/REST-ENDPOINTS.md](../NextGenTutors-Companion/REST-ENDPOINTS.md) | REST security classification |
| [../NextGenTutors-Companion/MATCHING-DATA-FLOW.md](../NextGenTutors-Companion/MATCHING-DATA-FLOW.md) | Matching pipeline |

---

## Verification commands (quick reference)

```powershell
# Full solution verify
powershell -File scripts/verify-solution.ps1

# Companion validate
php NextGenTutors-Companion/scripts/validate.php

# UI Library scan
php NextGenTutors-Companion/scripts/verify-ui-library.php

# Version alignment
php NextGenTutors-Companion/scripts/verify-versions.php

# Playwright E2E (from e2e/)
npx playwright test

# Docker local stack
cd docker; .\start.ps1

# Registry plugin install
cd docker; .\scripts\install-registry-zips.ps1
```

---

## Document maintenance

When code changes, update in this order:

1. `ARCHITECTURE.md` — package boundaries
2. `docs/PACKAGES.md` — versions and entry points
3. `docs/workflows/INTEGRATION-CATALOG.md` — events and integrations
4. `docs/SYSTEM-OVERVIEW.md` — whole-system map
5. Relevant tutorial or operations doc
6. `KNOWN-LIMITATIONS.md` — if verification status changes
