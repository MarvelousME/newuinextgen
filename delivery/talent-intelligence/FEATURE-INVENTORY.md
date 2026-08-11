# FEATURE-INVENTORY.md

Feature inventory for `ngt-talent-suitability-v1`.

| feature | source | purpose | included/excluded | reason |
|---------|--------|---------|-------------------|--------|
| subjects | CPT taxonomy / application | subject match | **included** | Core tutoring fit |
| grades | CPT taxonomy / meta | grade match | **included** | Core tutoring fit |
| curriculum tags | meta/application | curriculum match | **included** when present | Domain requirement |
| learning_format / delivery | taxonomy | delivery match | **included** | Online/in-person |
| province / location | taxonomy / meta | location match | **included** | Local delivery |
| languages | meta | language match | **included** when present | Teaching language |
| listed skills | meta / derived subjects | skill match | **included** | Capability fit |
| years experience (heuristic) | bio/fields | experience component | **included** (heuristic, low weight) | Relative signal |
| qualification **claims** (text) | bio/application | claim match | **included** as CLAIMED only | Never VERIFIED via NLP |
| profile completeness | derived | data quality | **included** | Confidence/warnings |
| availability slots | Amelia calendar | soft or hard filter | **included** soft / hard upstream | Prefer hard filter first |
| ratings / reviews | `ngc_reviews` | optional soft | **excluded v1** | Popularity bias risk; revisit later |
| hourly rate | meta | affordability | **excluded from suitability** | Business filter, not talent |
| race / ethnicity | any | — | **excluded** | Protected |
| gender | any | — | **excluded** | Protected |
| religion | any | — | **excluded** | Protected |
| sexual orientation | any | — | **excluded** | Protected |
| disability | any | — | **excluded** | Protected (accommodation separate) |
| age / DOB | any | — | **excluded** | Protected / unnecessary |
| photo appearance | media | — | **excluded** | Bias |
| name-based ethnicity inference | name | — | **excluded** | Proxy discrimination |
| upstream tech skill list (python, tensorflow, …) | TalentMatch | — | **excluded** | Wrong domain |
| pickle label_encoder classes | upstream | — | **excluded** | Unused + unsafe trust |
| safeguarding clearance flags | verification | eligibility gate | **excluded from score**; shown separately | Deterministic |
| criminal record NLP from CV | text | — | **excluded** | Dangerous inference |

## Upstream TalentMatch features (for comparison)

| feature | disposition |
|---------|-------------|
| Hard-coded DS/ML skill vocabulary | Replace with tutoring ontology |
| TF-IDF cosine on raw text | Optional low-weight blend only |
| 60/40 skill/text weights | Replace with documented tutoring weights |
| Highly Recommended labels | Map to RECOMMENDED_FOR_REVIEW etc. |
| CSV candidate corpora | Do not import as production tutors |
