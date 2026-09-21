# ADR-0008: Odoo Community as replaceable business provider

**Status:** Accepted  
**Date:** 2026-08-28

## Context

SaaS tenants need CRM, sales, invoicing, and inventory without building commodity ERP from scratch. Odoo must not become the architectural center of gravity.

## Decision

- Use **Odoo Community** behind `ICustomerProvider` / `ICrmProvider` / etc.
- **DB-per-tenant** for unrelated SaaS clients (`tenant_slug → odoo_database`).
- All UI flows through **ecosystem-platform API** and branded **Kinetic UI** — not Odoo backend screens for tenant users.
- Companion retains **vertical tutoring domain** (matching, safeguarding, vetting).

## Consequences

- Platform API owns tenant context and authorization.
- Odoo JSON-RPC adapter with in-memory fallback for local dev without Odoo.
- Real Odoo integration tests required before PRODUCTION READY verdict.
