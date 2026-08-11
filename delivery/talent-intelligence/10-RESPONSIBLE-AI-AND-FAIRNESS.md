# 10 — Responsible AI and Fairness

## Principles

1. Decision support only — humans approve tutors.  
2. No protected-characteristic features in suitability.  
3. Claims ≠ verified credentials.  
4. Safeguarding clearance is independent and deterministic.  
5. Explainability required for every score.  
6. Auditable model + weight versions.

## FEATURE-INVENTORY.md

See sibling file `FEATURE-INVENTORY.md` (mandatory inventory).

## Fairness controls (implementation plan)

- Reuse denylist patterns from `NGC_Lead_Criteria`  
- Strip forbidden fields from payloads before scoring  
- Reject explanations that cite protected traits  
- Unit tests for denylist  
- Sample evaluation set: high/medium/low/missing data/multi-subject — **no demographic proxies**

## Safeguarding separation

| Check | Suitability score? | State source |
|-------|--------------------|--------------|
| Identity verified | No | Verification subsystem / admin |
| Background/criminal | No | External / admin |
| References | No | Admin |
| Child-safety | No | Safeguarding module |
| Qualification documents | No | Admin verification — TI may show CLAIMED |

TI returns `VERIFIED | NOT_VERIFIED | PENDING | NOT_APPLICABLE` only from authoritative flags — never NLP inference.
