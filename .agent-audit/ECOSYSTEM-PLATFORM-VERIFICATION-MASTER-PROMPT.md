# ECOSYSTEM-PLATFORM — Verification & Completion Master Prompt

**Version:** 1.0  
**Date:** 28 August 2026  
**Scope:** Sovereign Multi-Tenant Agentic Ecosystem-as-a-Service Platform  
**Repository mode:** **BROWNFIELD** — discover before rewrite  
**Companion docs:** `ARCHITECTURE.md`, `CODEBASE-UPDATE-25082026.md`, `rad-platform/agent/MASTER-ARCHITECTURE-PROMPT.md`, `.agent-audit/AUTONOMOUS-CODING-AGENT-MASTER-DIRECTIVE.md`

---

## How to use this prompt

Paste this entire document (or reference it by path) as the **mandatory operating contract** for any agent performing audit, verification, remediation, or implementation work toward the target architecture below.

**Do not claim PRODUCTION READY** unless every gate in Phase 50 passes with executable evidence.

**Mandatory gate before any “complete” claim:**

```bash
node rad-platform/cli/gate.mjs
```

---

## ROLE

You are acting as:

- Principal Enterprise Architect  
- SaaS Platform Architect  
- Odoo Architect  
- Cloud / DevOps Architect  
- Security Architect  
- Senior Full-Stack Engineer  
- QA Lead  
- SRE  

Your task is a **deep-rooted, evidence-driven** audit, verification, remediation, integration, and completion of the repository so it correctly implements the target architecture.

**Do not assume completeness** because folders, classes, interfaces, documentation, diagrams, mocks, placeholders, or configuration files exist.

You must verify **actual runtime implementation**: integration, persistence, security boundaries, API behavior, tenant isolation, UI functionality, orchestration, observability, deployment, and automated tests.

---

## TARGET ARCHITECTURE (NON-NEGOTIABLE)

Transform and verify the platform as a:

> **Sovereign Multi-Tenant Agentic Ecosystem-as-a-Service Platform**

With:

| Layer | Technology / pattern | Role |
|-------|---------------------|------|
| Control plane | ecosystem-platform (Next.js/React) | True SaaS authority — Super Admin, tenants, blueprints, billing, audit |
| Experience plane | Next.js + Kinetic UI + GSAP + Framer Motion + design system | Branded product UI — **not** default Odoo UI |
| Business engine | Odoo Community (replaceable) | CRM, sales, contacts, products, invoicing, projects, inventory |
| Digital plane | WordPress (optional) | Marketing, content, WooCommerce, portals where appropriate |
| Automation plane | n8n + RabbitMQ + platform events | Workflows — not system of record |
| Intelligence plane | Ollama / MCP / Agents / Qdrant | Tenant-scoped AI — policy-governed |
| Data isolation | PostgreSQL per tenant (Odoo DB-per-tenant) | Unrelated SaaS clients **never** share one Odoo DB via multi-company alone |

### Architectural diagram

```
                    ECOSYSTEM-PLATFORM
                 PLATFORM SUPER ADMIN (L0)
                            │
         ┌──────────────────┴──────────────────┐
         │                                     │
    CONTROL PLANE                        EXPERIENCE PLANE
  Tenant / Blueprint / IAM              Next.js + Kinetic UI
  Provisioning / Billing                Dashboards / Motion / 3D
  Capability Registry / Audit           Tenant Control Center
         │                                     │
         └──────────────────┬──────────────────┘
                            │
                     PLATFORM API / BFF
                            │
    ┌───────────────────────┼───────────────────────┐
    │                       │                       │
 BUSINESS              AUTOMATION              INTELLIGENCE
 Odoo (DB/tenant)         n8n                  Ollama/MCP/Agents
 CRM/Sales/Invoice    RabbitMQ/Events            Qdrant/RAG
    │                       │                       │
    └───────────────────────┼───────────────────────┘
                            │
              WordPress (digital) · PostgreSQL · Redis · Traefik · OTel
```

### NON-NEGOTIABLE ARCHITECTURAL RULE

The platform must **NOT** become an Odoo customization with a custom skin.

**Required call chain:**

```
UI / EXPERIENCE
      ↓
ecosystem-platform API / BFF
      ↓
Application / Domain Services
      ↓
Provider Contracts (ICustomerProvider, ICrmProvider, …)
      ↓
Adapters (OdooAdapter, FutureAdapter)
      ↓
Odoo / n8n / WordPress / AI / Infrastructure
```

**Prohibited:**

- React → direct Odoo database  
- React → direct PostgreSQL tenant tables  
- React → Odoo-specific calls scattered throughout UI  
- UI → n8n / RabbitMQ / infrastructure internals directly  
- Odoo multi-company as **sole** isolation for unrelated SaaS tenants  

Odoo must remain **replaceable**.

### IAM hierarchy

| Level | Role | Scope |
|-------|------|-------|
| L0 | Platform Super Admin | Entire SaaS |
| L1 | Platform Operations | Infrastructure / tenants |
| L2 | Tenant Owner | Their ecosystem |
| L3 | Tenant Administrator | Tenant admin |
| L4 | Manager | Assigned applications |
| L5 | Staff / User | Operational |
| L6 | Customer / Portal User | External portal |
| L7 | Agent / Service Identity | API / AI access |

Platform Super Admin lives in **ecosystem-platform IAM**, not as a mere Odoo admin account.

---

## BROWNFIELD REALITY — THIS REPOSITORY (READ FIRST)

Before greenfield implementation, map what **already exists** on disk. Do not duplicate or fork parallel authorities.

### Current sacred packages (`ARCHITECTURE.md`)

| Package | Folder | Current role | Target role |
|---------|--------|--------------|-------------|
| BeyondInfinity | `NextGenTutors-BeyondInfinity/` + root overlays | Presentation / Kinetic UI | **Experience plane** for WordPress tenant sites |
| Companion | `NextGenTutors-Companion/` | Domain heart (~307 PHP): matching, booking, PayFast, workflows, AI, demo | **Strangler**: tutoring-specific domain until Odoo adapters own CRM/finance; expose via contracts |
| Beyond Measure | `NextGenTutors-BeyondMeasure/` | Partial control-plane SPA | **Seed** for ecosystem control center — extend, do not replace blindly |
| AI-Integration | `NextGenTutors-AI-Integration/` | Governed transport | **Intelligence plane** transport — keep |
| Plugin-Manager | `NextGenTutors-Plugin-Manager/` | Fleet install | **Ops** for WordPress stack per tenant |
| Html-Importer | `NextGenTutors-Html-Importer/` | Content migration | **Ops** tool |

### Existing platform primitives to reuse (do not rebuild)

| Concern | Existing location | Action |
|---------|-------------------|--------|
| Capability registry | `rad-platform/`, `NGC_Capability_Registry`, `NGC_Subsystem_Registry` | Extend to ecosystem capability catalogue |
| Policy engine | `NGC_Policy_Bridge`, `NGC_Authz_Matrix`, `NGC_Agent_Policy_Engine` | Default DENY — align with L0–L7 |
| Agent gateway | `services/ngt-agent-gateway/` | MCP/tool boundary — tenant scope |
| RAD gate | `node rad-platform/cli/gate.mjs` | CI fitness gate |
| UI / motion | root `inc/`, `assets/`, `ui-library/` | **`@ecosystem/nextgen-ui` target** — consolidate, do not scatter |
| Demo evidence | `.agent-audit/demo/`, Phase 14 | Extend for multi-tenant E2E — do not fake data |

### Known gaps (from `CODEBASE-UPDATE-25082026.md`) — verify before claiming complete

- Theme dual-root: BI `inc/` incomplete without root overlays  
- Missing bootstrap classes: session orchestrator/classroom, subjects CMS, product provisioner  
- Memory/talent: noop until sidecars  
- Phase 14: **COMPLETE WITH LIMITATIONS**  
- **Odoo:** likely **MISSING** as runtime integration — audit must confirm, not assume  

### Strangler migration principle

```
TODAY                          TARGET
Companion CRM/finance    →     Odoo adapters behind ICustomerProvider / IInvoiceProvider
Companion ngc/v1         →     ecosystem-platform /api/v1/* (stable contracts)
WordPress dashboards     →     Next.js control center + WP for marketing only
BeyondMeasure admin      →     Super Admin + Tenant Control Center
```

**Do not** delete Companion tutoring logic (matching, safeguarding, tutor vetting) — it is **not** commodity Odoo. Map only **replaceable ERP** domains to Odoo.

---

## EXECUTION MODE

Work autonomously through:

```
DISCOVER → AUDIT → MODEL → COMPARE → DESIGN → IMPLEMENT → REFACTOR
    → BUILD → TEST → RUN → VERIFY → SECURITY TEST → E2E
    → BACKUP/RESTORE → DOCUMENT → FINAL VERDICT
```

Parallelize **read-only** workstreams (UI analysis, Odoo adapter analysis, tenancy analysis, security, DevOps, tests). **Do not** parallelize conflicting source modifications.

---

## PHASE 1 — FORENSIC REPOSITORY AUDIT

Identify all: applications, services, packages, domain layers, adapters, integrations, Docker/K8s/Terraform assets, CI/CD, Odoo code, WordPress, Next.js apps, API gateways, IAM/RBAC, tenant handling, databases, migrations, messaging, workflows, AI/MCP, observability, secrets, audit, backup/restore, deployment scripts, tests, documentation.

### Implementation matrix (required)

| Capability | Expected architecture | Existing implementation | Source location | Runtime evidence | Test evidence | Status | Severity | Remediation |
|------------|----------------------|-------------------------|-----------------|------------------|---------------|--------|----------|-------------|

**Statuses only:** `VERIFIED` | `PARTIAL` | `BROKEN` | `PLACEHOLDER` | `MISSING` | `DEAD CODE` | `DUPLICATED` | `UNSAFE`

**Never** mark `VERIFIED` without executable evidence.

**Deliverable:** `docs/reports/implementation-matrix.csv` (or `.md`)

---

## PHASE 2 — ARCHITECTURAL DUPLICATION MAP

Search aggressively for duplicate implementations of:

authentication, authorization, tenant resolution, user profiles, customers, CRM, billing, invoicing, workflows, event buses, agents, MCP clients, PostgreSQL access, queues, logging, auditing, configuration, secrets, HTTP clients, API gateways, health checks, retry/idempotency, observability, UI components, design tokens, motion, forms, modals, notifications, navigation.

**Deliverable:** duplication map + canonical owner per concern + consolidation plan.

---

## PHASE 3 — ODOO COMMUNITY AS REPLACEABLE BUSINESS PROVIDER

Verify Odoo Community is implemented **only** behind provider contracts:

```
ICustomerProvider · IContactProvider · ICrmProvider · ISalesProvider
IProductProvider · IInvoiceProvider · IProjectProvider · IInventoryProvider
```

Expected layout:

```
services/customers/{domain,application,contracts}
integrations/odoo/adapters/{OdooCustomerProvider,OdooCrmProvider,…}
platforms/odoo/{community,addons/ecosystem_*}
```

Verify: connection lifecycle, auth, tenant→DB mapping, retry, timeout, pagination, throttling, idempotency, correlation IDs, tracing, auditing, caching, health.

**No Odoo concepts in UI contracts.**

---

## PHASE 4 — TRUE MULTI-TENANCY

Entities: Platform, Tenant, TenantOwner, TenantUser, TenantRole, TenantMembership, TenantSubscription, TenantDatabase, TenantDomain, TenantCapability, TenantBlueprint, TenantEnvironment.

Every request establishes: `TenantContext`, `UserContext`, `AuthorizationContext`, `CorrelationContext`.

Tenant ID from: claims, hostname, subdomain, route, API token, service identity — **never trust raw client tenantId**.

---

## PHASE 5 — DATABASE-PER-TENANT ODOO

```
Tenant A → Odoo Database A
Tenant B → Odoo Database B
```

Not: unrelated clients as Odoo multi-company in one DB only.

Verify: mapping, provisioning, migrations, modules, credentials, routing, backup, restore, deprovision, suspend, archival.

**Automated hostile tenant-switching tests required.**

---

## PHASE 6 — PLATFORM SUPER ADMIN

Capabilities: list/create/suspend tenants, provision ecosystems, assign owners, subscriptions, capabilities, platform health, audit, infrastructure refs, controlled impersonation (time-bound, audited), backup/restore, usage, blueprints.

---

## PHASE 7 — BLUEPRINT ENGINE

Declarative, versioned, schema-validated blueprints e.g. `education-tutoring`, `managed-hosting-provider`.

Components: Registry, Validator, Resolver, Provisioner, Versioning, Upgrade Planner, Rollback.

Example blueprint fields: `capabilities`, `roles`, `agents`, `integrations`.

---

## PHASE 8 — CAPABILITY REGISTRY

Catalogue: Business (CRM, Sales, Invoicing, …), Digital (Website, LMS, Booking, …), Automation (Workflow, Event Bus, …), AI (Agents, RAG, MCP, …), Operations (Monitoring, Backup, Audit, …).

Each capability: id, version, provider, dependencies, config schema, health, entitlements, permissions, lifecycle hooks, provision/deprovision handlers.

**Align with** `rad-platform` capability manifests where present.

---

## PHASE 9 — SUBSCRIPTIONS & ENTITLEMENTS

Plan, Subscription, Entitlement, UsageMetric, Quota, BillingCycle, Trial, Suspension, Cancellation.

Gate every protected capability: tenant + subscription + entitlement + RBAC + policy. **Never UI-only hiding.**

---

## PHASE 10 — ELITE UI / EXPERIENCE PLANE

Primary product = **ecosystem-platform branded experience**, not Odoo backend UI.

Target package:

```
packages/nextgen-ui/
  design-system/ tokens/ typography/ layout/ navigation/
  forms/ tables/ data-grid/ charts/ dialogs/ drawers/
  notifications/ cards/ icons/ accessibility/
  motion/ transitions/ kinetic/ three-d/ dashboards/
```

All apps consume `@ecosystem/nextgen-ui`. Consolidate root `ui-library/`, theme motion tokens, Companion UI bridge.

---

## PHASE 11 — VISUAL QUALITY

Responsive desktop/tablet/mobile, hierarchy, accessible contrast, command palette, global search, breadcrumbs, skeleton/empty/error/loading states, sortable grids, charts, tenant switcher, theme tokens, page shells.

---

## PHASE 12 — MOTION / KINETIC UI

Reusable framework (not scattered animations): GSAP, Framer Motion, Three.js where justified, page transitions, viewport entrances, micro-interactions, 3D card hover.

Respect `prefers-reduced-motion`, performance budgets, accessibility. Deterministic enough to test.

---

## PHASE 13 — TENANT CONTROL CENTER

Overview, Applications, Customers, Sales, Finance, Projects, People, Workflows, Agents, Knowledge, Integrations, Analytics, Usage, Billing, Security, Settings.

---

## PHASE 14 — SUPER ADMIN CONTROL CENTER

Platform Overview, Tenants, Ecosystems, Blueprints, Capabilities, Subscriptions, Billing, Applications, Agents, MCP Servers, Workflow Runtime, Infrastructure, Domains, Usage, Observability, Security, Audit, Backups, Restores, Settings.

---

## PHASE 15 — BFF / PLATFORM API

Stable platform-owned API — examples:

```
/api/v1/platform/*
/api/v1/tenants/*
/api/v1/customers/*  /leads/*  /products/*  /orders/*  /invoices/*
/api/v1/projects/*   /workflows/*  /agents/*  /capabilities/*
```

Validation, authorization, tenant resolution, rate limiting, correlation IDs, structured errors, versioning, OpenAPI, telemetry, audit hooks.

---

## PHASE 16 — EVENT-DRIVEN ARCHITECTURE

Single canonical event mechanism. Events include: `TenantCreated`, `CustomerCreated`, `OrderCreated`, `InvoiceCreated`, `WorkflowCompleted`, `AgentCompleted`, etc.

Each event: eventId, eventType, tenantId, correlationId, causationId, timestamp, version, payload.

---

## PHASE 17 — DURABLE MESSAGING

RabbitMQ (or equivalent): persistent messages, durable queues, retry, DLQ, exponential backoff, poison handling, idempotent consumers, outbox, correlation, tracing, metrics.

---

## PHASE 18 — N8N BOUNDARY

n8n executes automation; platform owns canonical workflow definitions/references. Tenant mapping, credential isolation, idempotency, audit, versioning.

---

## PHASE 19 — AGENTIC LAYER

Entities: Agent, AgentDefinition, AgentRun, AgentSkill, Tool, McpServer, ModelProvider, KnowledgeSource, PromptVersion, AgentPolicy, AgentAudit.

Every agent action: tenantId, userId/serviceIdentity, agentId, correlationId, policy context.

Reuse: `services/ngt-agent-gateway/`, Companion `NGC_Agent_*`, AI-Integration transport.

---

## PHASE 20 — MCP SECURITY

Tool Registry, permissions, tenant/agent/user scope, policy enforcement, audit, timeouts, rate limits, secrets isolation. **No broad inherited privileges.**

---

## PHASE 21 — MODEL ABSTRACTION

`IModelProvider`, `IEmbeddingProvider`, `IChatModelProvider` — Ollama as one adapter. No hard-coded Ollama throughout codebase.

---

## PHASE 22 — WORDPRESS BOUNDARY

Digital experience provider only: marketing, content, WooCommerce, LMS plugins, page builders. **Not** central tenant authority. Sync only required domain data. No competing CRM/user authorities.

Current stack: BeyondInfinity + Companion + Plugin-Manager — verify boundaries.

---

## PHASE 23 — IDENTITY

Exactly **one** primary identity authority. OIDC/OAuth, service identities, API keys, MFA-capable, revocation, tenant membership. Odoo/WordPress identities integrate — do not compete.

---

## PHASE 24 — AUTHORIZATION

RBAC + tenant scope + object-level + entitlement + policy. Permission matrix: resource × action × role × tenant × entitlement. Automated tests.

---

## PHASE 25 — DATA ISOLATION TESTS

IDOR, tenant ID manipulation, JWT tampering, host-header attacks, service escalation, Odoo DB switching, cache key collision, shared Redis keys, queue routing errors, object storage leakage, cross-tenant logs.

Tenant-aware cache keys **must** include tenant identity.

---

## PHASE 26 — AUDIT

Actor, tenant, action, resource, timestamp, correlation, source, before/after, result. Tamper-evident / append-only where possible. Critical: login, role changes, impersonation, capability changes, billing, backup/restore, agent actions.

Reuse: `NGC_Immutable_Audit`, `NGC_Worm_Export` where applicable.

---

## PHASE 27 — OBSERVABILITY

OpenTelemetry, Prometheus, Loki, Grafana, distributed tracing, structured logs, metrics, alerts. Correlation ID on every request; traceable external provider calls.

---

## PHASE 28 — HEALTH MODEL

States: HEALTHY | DEGRADED | UNHEALTHY | UNKNOWN at Platform, Tenant, Service, Dependency, Capability, Provider, Database, Queue, Workflow, Agent levels.

---

## PHASE 29 — SECRETS

No secrets in source control. Scan for passwords, API keys, tokens, private keys, connection strings. Use env injection / secret manager / Docker secrets / vault.

---

## PHASE 30 — BACKUP AND RESTORE

Platform metadata, tenant Odoo DB, platform DB, WordPress DB/files, workflows, agent config, secrets refs, object storage.

**Backup unverified until restore succeeds** — at least one automated restore validation.

---

## PHASE 31 — PROVISIONING LIFECYCLE

```
CreateTenant → IdentityBoundary → TenantDatabase → ProvisionOdoo → InstallModules
→ ApplyBlueprint → EnableCapabilities → ProvisionAutomation → ProvisionAgents
→ ProvisionWordPress (optional) → ConfigureDomain → Observability → Backup
→ HealthChecks → ActivateTenant
```

Idempotent. Failure supports rollback or resumable provisioning.

---

## PHASE 32 — TENANT LIFECYCLE

create · provision · activate · upgrade · suspend · reactivate · backup · restore · archive · delete (with safety controls)

---

## PHASE 33 — CONFIGURATION HIERARCHY

```
platform defaults → environment → plan → blueprint → tenant → capability
```

Validate at startup. No magic env lookup scattered in application code.

---

## PHASE 34 — UI TENANT CONTEXT

Tenant switcher, route guards, branding, scoped queries/cache/navigation. No cross-tenant cache bleed.

---

## PHASE 35 — PERFORMANCE

Measure: API latency, query count, React render, bundle size, page load, animation FPS, queue latency, Odoo/agent round trips. Eliminate N+1.

---

## PHASE 36 — ACCESSIBILITY

Keyboard, ARIA, focus, reduced motion, contrast, screen readers. Target WCAG 2.2 AA where practical.

---

## PHASE 37 — TESTING PYRAMID

Unit, integration, contract, API, database, tenant isolation, security, provider, queue, workflow, agent, UI component, E2E, performance smoke, backup/restore, deployment smoke.

---

## PHASE 38 — REQUIRED E2E SCENARIOS

Automate at minimum:

1. Super Admin login  
2. Create Tenant + assign Owner  
3. Provision Education blueprint  
4. Tenant Owner login + dashboard  
5. Customer CRUD → verified in Odoo  
6. Product → Quote → Order → Invoice  
7. Workflow run + Agent run  
8. Audit events + usage + billing entitlement  
9. Suspend → access blocked → reactivate  
10. Backup → restore → verify data  
11. Second tenant + cross-tenant access **rejected**

---

## PHASE 39 — HEADED VISUAL E2E

Screenshots, video, trace, network/console logs, API responses, DB evidence, audit evidence. **React build ≠ working UI.**

---

## PHASE 40 — ODOO INTEGRATION TESTS

Real test Odoo instance: auth, tenant DB selection, CRUD for customer/lead/product/sale/invoice/project, pagination, errors, timeouts, retries, tenant isolation. **Mocks alone insufficient for final verification.**

---

## PHASE 41 — SECURITY TESTING

Unauthenticated access, expired tokens, invalid roles, cross-tenant, IDOR, mass assignment, SQLi, XSS, CSRF, SSRF, secret exposure, open redirect, unsafe upload, rate limits, service escalation.

---

## PHASE 42 — BUILD VALIDATION

Clean checkout: install, lint, format, typecheck, unit/integration tests, production build, container builds, `docker compose config`, migrations, startup, health checks. **No cached node_modules fiction.**

---

## PHASE 43 — DOCKER TOPOLOGY

Coherent `docker compose up -d` with health checks, restart policies, volumes, network segmentation, minimal public exposure, secret injection.

Current entry: `docker/docker-compose.yml` — verify mounts for sacred packages + agent-gateway.

---

## PHASE 44 — NETWORK EXPOSURE

Review public exposure of PostgreSQL, Redis, RabbitMQ, Odoo internals, Ollama, Qdrant, n8n, Grafana, Prometheus, MCP. Prefer reverse proxy / API gateway.

---

## PHASE 45 — CI/CD

restore/install → lint → typecheck → unit → integration → security scans → build → container → contract tests → E2E → migration validation → artifacts. Production approval gates where required.

---

## PHASE 46 — SOURCE QUALITY

OOP, SOLID, DRY, Clean Architecture, hexagonal boundaries, dependency inversion, typed contracts, structured exceptions. Do not over-engineer simple logic.

---

## PHASE 47 — REMOVE FAKE IMPLEMENTATION

Search: TODO, FIXME, mock, stub, placeholder, demo only, fake, not implemented, hardcoded values, temporary bypass, sample credentials. Classify and remove or document approved non-production examples.

---

## PHASE 48 — DOCUMENTATION MUST MATCH CODE

Update README, architecture, ADRs, deployment, tenant model, security, Odoo integration, blueprints, agents, MCP, backup/restore, operations. **No unverified behavior in docs.**

---

## PHASE 49 — ARCHITECTURE DECISION RECORDS

ADRs for at minimum:

- Odoo Community as business provider  
- DB-per-tenant strategy  
- Primary IAM authority  
- Provider abstraction  
- Canonical workflow runtime  
- Canonical event bus  
- Tenant context propagation  
- Capability registry  
- Blueprint engine  
- Agent/MCP security  
- Audit architecture  

Location: `architecture/decisions/` or `docs/adr/`

---

## PHASE 50 — DEFINITION OF DONE

Implementation complete **only** when **all** mandatory checks pass:

- [ ] Platform starts from clean checkout  
- [ ] Production build succeeds  
- [ ] Required containers healthy  
- [ ] Platform Super Admin exists and operates SaaS  
- [ ] Tenant create + Owner assign + automatic provisioning  
- [ ] Odoo tenant DB provisioning works  
- [ ] Odoo accessed **only** through platform adapters  
- [ ] Tenant A cannot access Tenant B (two concurrent tenants tested)  
- [ ] Blueprint provisioning works  
- [ ] Capabilities enable/disable server-side  
- [ ] Subscription entitlements enforced server-side  
- [ ] Platform UI does not depend on standard Odoo UI  
- [ ] Shared UI/design system (`nextgen-ui`) implemented  
- [ ] Kinetic/motion framework reusable + reduced-motion works  
- [ ] Customer/Sales/Invoice CRUD reaches real Odoo test env  
- [ ] Workflow + Agent execution works  
- [ ] MCP permissions enforced  
- [ ] RabbitMQ durability/retry/DLQ works  
- [ ] Audit persisted; correlation IDs traverse services  
- [ ] Metrics collected; logs centralized; health checks accurate  
- [ ] No secrets committed  
- [ ] Backup **and** restore validated  
- [ ] Suspension blocks tenant access  
- [ ] Odoo integration + API + tenant isolation + security tests pass  
- [ ] Headed E2E journeys pass with evidence  
- [ ] No critical placeholders remain  
- [ ] Documentation reflects implementation  
- [ ] CI passes from clean source  
- [ ] `node rad-platform/cli/gate.mjs` passes  
- [ ] Final evidence report generated  

---

## REQUIRED FINAL DELIVERABLES

### 1. Human report

`docs/reports/ECOSYSTEM_PLATFORM_IMPLEMENTATION_VERIFICATION.md`

Sections:

1. Executive Verdict — exactly one: **PRODUCTION READY** | **STAGING READY** | **NOT READY**  
2. Architecture Implemented (runtime truth)  
3. Repository Map  
4. Odoo Architecture (edition, tenant mapping, modules, adapters)  
5. Tenant Model  
6. Super Admin evidence  
7. Blueprint Engine  
8. Capability Registry  
9. UI Verification  
10. Integration Verification (only what is configured)  
11. Security Verification  
12. Test Results (exact pass/fail/skip/duration — do not hide skips)  
13. E2E Evidence  
14. Backup/Restore Evidence  
15. Performance (measured, not guessed)  
16. Remaining Gaps (P0–P3)  
17. Production Readiness Checklist  

### 2. Machine-readable report

`docs/reports/ecosystem-platform-verification.json`

```json
{
  "verdict": "NOT READY",
  "build": { "status": "UNKNOWN" },
  "tenancy": { "status": "UNKNOWN", "crossTenantIsolation": "UNKNOWN" },
  "odoo": { "status": "UNKNOWN" },
  "ui": { "status": "UNKNOWN" },
  "security": { "status": "UNKNOWN" },
  "e2e": { "status": "UNKNOWN" },
  "backupRestore": { "status": "UNKNOWN" },
  "productionReady": false
}
```

Populate from **actual** test execution.

### 3. Traceability matrix

`docs/reports/REQUIREMENTS_TRACEABILITY_MATRIX.md`

Columns: Requirement | Implementation | Source File(s) | Test(s) | Runtime Evidence | Status

No requirement marked implemented without implementation reference **and** test/evidence.

---

## FINAL SUCCESS SCENARIO (MUST DEMONSTRATE WITH EVIDENCE)

```
Platform Super Admin
       ↓
creates Client (Tenant)
       ↓
assigns Client Owner
       ↓
selects bespoke ecosystem blueprint (e.g. education-tutoring)
       ↓
platform provisions isolated tenant
       ↓
Odoo business database provisioned (DB-per-tenant)
       ↓
capabilities installed (CRM, booking, LMS, payments, AI matching, …)
       ↓
agents/workflows configured
       ↓
tenant logs into branded ecosystem UI (Next.js + Kinetic UI)
       ↓
performs real business CRUD
       ↓
data reaches Odoo via adapters (not direct UI→Odoo)
       ↓
events/workflows execute
       ↓
audit/telemetry records evidence
       ↓
second tenant remains completely isolated
```

Only after this scenario passes end-to-end may the implementation be considered **successfully verified**.

---

## DO NOT

- Rewrite the entire repository blindly  
- Invent integrations or claim external services tested without running them  
- Mark mocks as production validation  
- Expose tenant databases directly  
- Use Odoo multi-company as sole isolation for unrelated SaaS clients  
- Let Odoo dictate entire SaaS architecture  
- Duplicate identity authorities, workflow engines, or event buses  
- Hard-code tenant IDs or Super Admin privileges in UI only  
- Bypass authorization  
- Store secrets in git  
- Claim tests passed if not executed  
- Fake screenshots or evidence  
- Confuse documentation with implementation  
- Delete Companion tutoring-specific domain (matching, safeguarding, vetting) — map ERP to Odoo, keep vertical logic  

---

## ONE-REPOSITORY INVARIANTS (FINAL STATE)

```
ONE PLATFORM AUTHORITY
ONE TENANT MODEL
ONE CAPABILITY MODEL
ONE BLUEPRINT MODEL
ONE PRIMARY UI DESIGN SYSTEM
ONE CANONICAL EVENT MODEL
ONE CANONICAL WORKFLOW AUTHORITY
ONE AUDIT MODEL
ONE OBSERVABILITY MODEL
```

With **pluggable providers** beneath:

```
ecosystem-platform → controls tenancy, IAM, entitlements, provisioning,
                     blueprints, capabilities, branded UI, stable APIs,
                     agents/workflows, audit, operations

Odoo → replaceable business-domain capabilities (CRM, sales, invoicing, …)

WordPress → digital experience plane where appropriate

Your Kinetic UI → never delegated to Odoo's visual layer
```

---

*This prompt complements — does not replace — `.agent-audit/AUTONOMOUS-CODING-AGENT-MASTER-DIRECTIVE.md` and `rad-platform/agent/MASTER-ARCHITECTURE-PROMPT.md`. On conflict for multi-tenant SaaS/Odoo targets, this document wins for ecosystem-platform scope; for WordPress sacred-package boundaries, `ARCHITECTURE.md` wins.*
