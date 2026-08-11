# 03 — SWOT

## Strengths (upstream)

- Simple, inspectable scoring formula (skill overlap + TF-IDF cosine)
- Returns matched/missing skills and coarse evidence levels
- README intent keeps humans as final decision makers
- Lightweight runtime if Streamlit discarded

## Weaknesses (upstream)

- Tech-recruiter skill ontology — **wrong domain** for tutoring
- Streamlit + CSV “database” — not multi-tenant / not auditable
- Broken `requirements.txt`; unused `label_encoder.pkl`
- Silent failure modes; no tests; no auth
- `.txt` only; no PDF/DOCX despite marketing roadmap
- Hard-coded weights/thresholds without configuration versioning
- Pickle trust boundary

## Opportunities (NextGenTutors)

- Plug **explainable multi-factor suitability** into existing admin application review
- Re-rank **already eligible** Find-a-Tutor / marketplace pools
- Register first-class `talent.*` / `matching.*` capabilities (fill architecture gap)
- Reuse ethical lead criteria patterns (`NGC_Lead_Criteria`) for fairness
- Persist evaluations in Companion DB + durable queue (existing platform kernel)

## Threats / risks

- Parallel matching engines (Hub + Companion + new service) confuse operators
- AI score mistaken for approval / safeguarding clearance
- Domain-inappropriate skills produce nonsense tutor rankings
- Pickle deserialization compromise
- Coupling WP to Streamlit ops model
- Auto-approve via score thresholds (must forbid by policy)

## Strategic implication

Extract **structure** (overlap + similarity + explain + thresholds as decision support).  
Replace **domain model, storage, UI, auth, and ontology** with NextGenTutors Bridge patterns.
