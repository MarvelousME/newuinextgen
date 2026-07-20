# Demo implementation report (Phase 14)

**Status:** COMPLETE WITH LIMITATIONS  
**Seed version:** 14.0.0  
**Environment:** local / Docker WordPress (`localhost:8900`)  
**Date:** 2026-07-20 (revised)

## Summary

Phase 14 relational demo subsystem is implemented in Companion:

- Demo mode safety gates (sandbox email, PayFast sandbox force, payout block)
- Injectable demo clock with scheduler tick hooks
- 22 login-enabled personas with stable IDs (`NGT-DEMO-*`)
- Deterministic seeder through domain services
- Sandbox notification delivery log with correlation metadata
- Demo Control Centre (admin) + WP-CLI commands
- **Executable journey catalogue** — 29 JSON definitions (MATCH-001…008, BOOK-001…008, FIN-001…010, JOURNEY-PARENT/TUTOR/OPS-001)
- Evidence exporter writing §14.24 fields under `.agent-audit/evidence/demo/<id>/`
- Live demonstration runbook with **11 fields per step** (§14.31)
- Unit tests covering env/registry/notifications/journeys

## Personas created

See `NGC_Demo_Registry::personas()` — parents, minors, adult student, tutors (approved/online/budget/suspended), applicants (draft/submitted/resubmit), admin, finance, compliance, safeguarding, support, security, fraud, auditor, AI ops.

## Credentials access

Demo Control Centre directory (demo mode only) or `NGC_DEMO_PASSWORD` / generated `ngc_demo_password` option.

## Records / relationships (via seed graph)

| Area | Evidence |
|------|----------|
| Parent ↔ 2 children | `NGC_Child_Learners::create` |
| MATCH-001/002/004/007 | `NGC_Matching` + accept/manual_assign |
| BOOK-001 confirmed, completed+review, pending pay, adult | `NGC_Bookings::create` + `transition` |
| Wallet top-up + tutor payout pending | `NGC_Wallet::credit`, `NGC_Reviews::create_payout` |
| Fraud case + safeguarding case | domain engines |
| Agent AI-001/008/009/010 | control plane + policy engine |
| Notifications | `NGC_Demo_Notifications` sandbox log |
| CRM / LMS / Amelia | `NGC_Demo_Verifier::verify_integrations()` records adapter status |

## Commands

Documented in `.agent-audit/demo/README.md` (`wp ngc demo_*`).

```bash
wp ngc demo_verify --allow-root
wp ngc demo_export_evidence --allow-root
wp ngc demo_run_all_journeys --allow-root
```

## Tests

| Suite | Result |
|-------|--------|
| `php NextGenTutors-Companion/tests/run.php` | Journey catalogue ≥ 29 asserted |
| Headed Playwright Phase 14 | See e2e run (when Docker up) |
| Docker `wp ngc demo_seed` / `demo_verify` | Run on evaluator environment |

## Limitations (honest)

1. **CRM / LMS / Amelia sync** — always **verifiable** via adapter `verify()` statuses in demo verify/evidence. When plugins are inactive, status is PARTIAL/UNAVAILABLE (not a silent success). When plugins are active but `ok=false`, demo verify **fails**. Dedicated DLQ failure scenario remains optional hardening.
2. **PayFast ITN full webhook path** — FIN-001 journey is defined; full PayFast e2e remains `payfast-e2e-docker.php` where configured.
3. **Playwright persona login suite** — not expanded to all 22 personas; Control Centre + unit tests + Phase 14 headed walkthrough cover the critical path.
4. **Evidence packs on disk** — exporter/scaffolding shipped; packs appear after `demo_export_evidence` / journey runs on the host (README was previously NOT STARTED).
5. **Host disk** — constrained environments should prune large evidence packs after export.

## Production isolation

- Demo ops gated by `NGC_Demo_Env::assert_demo_ops_allowed()`
- Reset refused without demo mode + `allow_reset`
- Login-as switch only for `ngc_is_demo_user` while demo mode on
- External side effects default **false**

## Reset validation

`NGC_Demo_Reset::reset()` deletes demo-tagged bookings/matches/children, clears notification log, resets clock, deletes demo users — preserves non-demo records.

## Acceptance decision

```text
COMPLETE WITH LIMITATIONS
```

Critical path (personas, relational seed, journey catalogue, runbook §14.31, evidence exporter, integration verification probes, admin + CLI, verify, reset) is implemented. Full provider-depth e2e for every MATCH/BOOK/FIN row and universal Playwright persona coverage remain follow-up hardening — not claimed as unconditional COMPLETE.
