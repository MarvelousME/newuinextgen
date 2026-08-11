# 09 — Scoring Model

## Model id

`ngt-talent-suitability-v1`  
Inspired by TalentMatch structure (overlap + optional text similarity + thresholds), **rebuilt for tutoring**.

## Component outputs (not a single opaque %)

| Component | Default weight | Matching logic (v1) |
|-----------|----------------|---------------------|
| subject | 0.25 | Jaccard / required coverage of requirement subjects |
| grade | 0.15 | Coverage of required grades |
| curriculum | 0.10 | Tag overlap; 0 + warning if data missing |
| qualification_claim | 0.10 | Keyword/heuristic vs required — status CLAIMED not VERIFIED |
| teaching_experience | 0.10 | Years/heuristic from structured fields |
| skill | 0.10 | Explicit skills + subject-derived skills |
| language | 0.05 | Required languages coverage |
| availability | 0.05 | Soft unless hard-filtered out upstream |
| location_delivery | 0.05 | Province + delivery mode |
| profile_completeness | 0.05 | Fraction of required fields present |

Weights must be stored as versioned configuration (`weight_config_version`). Operators can change weights in admin; defaults documented here.

## Overall score

```text
overall = Σ (weight_i × component_i) / Σ weights_of_present_components
```

Missing data → component marked `INSUFFICIENT_DATA`, excluded from denominator or scored 0 with warning (configurable; default: exclude + warning).

## Optional text similarity (TalentMatch-inspired)

If free-text bio + requirement narrative present and NLP provider enabled:

```text
text_similarity ∈ [0,100]
blend: overall = 0.85 × structured_overall + 0.15 × text_similarity
```

(TalentMatch used 0.60/0.40 skill/text — too text-heavy for tutoring structured SSOT; v1 prefers structured dominance.)

## Decision-support statuses (never auto-approve)

| Status | Default rule |
|--------|----------------|
| `RECOMMENDED_FOR_REVIEW` | overall ≥ 75 AND no blocking gaps |
| `PARTIAL_MATCH` | 50 ≤ overall < 75 |
| `LOW_MATCH` | 25 ≤ overall < 50 |
| `INSUFFICIENT_DATA` | completeness < threshold OR critical required fields missing |
| `NOT_ELIGIBLE` | failed hard filters (should not reach TI rank) |

## Explanation payload

```json
{
  "score": 82.4,
  "recommendation": "RECOMMENDED_FOR_REVIEW",
  "components": [{"key":"subject","score":95,"weight":0.25,"status":"MATCH"}],
  "matchedCriteria": [],
  "gaps": [],
  "evidence": [],
  "warnings": [],
  "safeguarding": {"identity":"PENDING","background":"NOT_APPLICABLE"},
  "modelVersion": "ngt-talent-suitability-v1",
  "weightConfigVersion": "wc-1",
  "inputSnapshotHash": "sha256:..."
}
```

## Explicit exclusions from suitability

Race, ethnicity, religion, gender, sexual orientation, disability, and other protected traits — see FEATURE-INVENTORY.md.
