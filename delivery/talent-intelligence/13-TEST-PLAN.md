# 13 — Test Plan

## Unit

- Text clean / skill extract (tutoring ontology)  
- Component scores + weighting  
- Threshold → recommendation mapping  
- Protected trait rejection  
- Missing data / INSUFFICIENT_DATA  
- Idempotent evaluation key

## Contract

- Provider interface ↔ Bridge-native  
- Optional PHP ↔ Python `/v1/similarity`

## Integration

- Application update → queue → evaluation row  
- Marketplace hard filter then rank  
- Lifecycle approve still human-only

## Security

- Unauthorized REST  
- Oversized payload  
- Forbidden features in input  
- No pickle upload endpoint

## Failure

- Provider timeout → degraded message, registration OK  
- Queue failure → retry/DLQ  
- Sidecar down → Bridge-native continues

## Regression

Registration, approval, profile, Find-a-Tutor, booking, lesson, payment, payout unchanged when TI disabled.

## Headed E2E (post-implementation)

Per master prompt steps 1–20 with DB before/after evidence under `delivery/talent-intelligence/evidence/`.

## Representative fixtures

high match, medium, low, missing CV/bio, multi-subject, multi-grade, curriculum gap, online-only, in-person-only, insufficient data.
