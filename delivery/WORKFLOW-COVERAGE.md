# Workflow Coverage

**Status:** PENDING — formula defined; live percentages not computed (no snapshot job, no runtime traces).

**Date:** 2026-08-14

## Formula (to be applied after Phase 8)

Let `N` = nodes in a workflow graph, `E` = edges.

- `proven` = confidence `PROVEN` (callable registered in source **or** runtime trace exists for that node/edge)  
- `configured` = JSON/option/catalog without a traced callable  
- `unverified` = expected by product narrative, no source  

```
topology_coverage = (count(proven) + count(configured)) / (count(N) + count(E))
```

`CHAIN-PROVEN` requires every hop on the expected path to be `PROVEN` **and** at least one runtime execution covering the full path.

Unavailable telemetry must be labelled `unavailable`, not `0%`.

## Scores

Not calculated. Inventing percentages would violate evidence rules.
