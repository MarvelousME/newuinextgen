# ADR-002 — Automation Hub defers to Companion

**Status:** Accepted  
**Date:** 2026-07-27

## Context

Automation Hub and Companion both implemented payouts, health crons, dashboards, and REST routes under `ngt/v1`. Companion mirrors all `ngc/v1` routes to `ngt/v1`, causing route shadowing and financial double-processing risk.

## Decision

When Companion is active (`NGC_VERSION` or `NGC_Plugin`):

1. Hub **unschedules** `ngt_monthly_payout_calculation` and `ngt_daily_health_check`.
2. Hub REST moves from `ngt/v1` → **`ngt-hub/v1`** (RTM, notifications, legacy dashboard helpers).
3. Companion `NGC_Observability_Service` syncs delegation on `plugins_loaded` priority 25.
4. Hub events still fire via `ngt_automation_event_fired` → `NGC_Automation_Hub_Bridge`.

Standalone sites without Companion keep Hub behaviour on `ngt/v1`.

## Consequences

- Hub JS must use localized REST base from `NGT_Hub_Companion_Delegate::rest_url()`.
- Mission Control cron panel flags Hub crons as **warn** if still scheduled.
- Deprecation path: merge RTM into Companion or keep Hub as event-only plugin.
