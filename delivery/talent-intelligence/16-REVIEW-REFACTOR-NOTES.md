# Review + refactor notes (2026-08-11)

## Blocking fix applied

`sanitize_key( 'talent.evaluate' )` → `talentevaluate` (dots stripped). Filters were registered as `talent_evaluate` / `memory_ingest` and **never fired**.

- Filters now: `ngc_queue_handle_talentevaluate`, `ngc_queue_handle_memoryingest`
- Also added `dispatch_message` cases for `talent.evaluate` / `memory.ingest`

## Other fixes

- Admin caps aligned (view/save/evaluate)
- Structured explanation UI (not raw JSON only)
- Marketplace re-rank skipped when no ranking signal
- Profile helpers extracted
- Agent tools wired behind `agent_tools_enabled` (evaluate/explain/rank only — no approve)

## Acceptance status

See FINAL evidence + unit/gate results in chat summary.
