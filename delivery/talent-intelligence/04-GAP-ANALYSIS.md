# 04 — Gap Analysis

| Need | Upstream TalentMatch | NextGenTutors today | Gap action |
|------|----------------------|---------------------|------------|
| Tutor suitability vs requirements | Resume↔JD text score | Rule score subject/grade/province/format | **ADD** multi-factor tutoring suitability |
| Explainability | Matched/missing skills + evidence | Mostly numeric score | **ENHANCE** component + evidence objects |
| Ranking | CSV + Streamlit tables | Smart match top-N in meta | **ENHANCE** after hard filters |
| Admin UX | Streamlit app | Companion admin matches/applications | **ADD** Talent Intelligence screens under Tutors |
| Persistence | CSV files | MySQL matches/applications | **ADD** evaluation tables (not CSV SSOT) |
| Async | None | Durable queue exists | **REUSE** queue for batch evaluate |
| Capabilities/policy | None | Policy Bridge + registries | **ADD** `talent.*` caps |
| Resume/CV | `.txt` upload | Form fields only (no CV) | **DEFER** PDF/DOCX until extractor approved |
| Skills ontology | Tech ML skills | Subjects/grades taxonomies | **REPLACE** ontology |
| Safeguarding | None | Separate lifecycle / agents | **KEEP separate**; never NLP-verify |
| AuthZ | None | WP caps + authz matrix | **REQUIRED** |
| Human approval | Implicit | Lifecycle approve/reject | **KEEP** sole authority |
| Find-a-Tutor | N/A | Marketplace + matching | **ENHANCE ranking only** |
| Python service | Streamlit monolith | Agent gateway only | **ADD** optional internal service **or** Bridge-native scorer |
| Model versioning | None | None for matching | **ADD** |
| Fairness controls | None | Lead criteria denylist | **EXTEND** to talent scoring |
| Tests | None | Partial matching demos | **ADD** unit/contract/E2E |
| Observability | None | Metrics/audit platform | **WIRE** |

## Priority gaps for Stage 2 (post-approval)

1. Domain scoring model + FEATURE inventory  
2. Provider interface + capability registration  
3. Evaluation persistence + async worker  
4. Admin decision-support UI (no auto-approve)  
5. Find-a-Tutor re-rank hook behind hard filters  
6. PDF/DOCX deferred unless existing extractor discovered (none found)
