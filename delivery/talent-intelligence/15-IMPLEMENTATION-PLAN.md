# 15 — Implementation Plan

**Stage:** Analysis complete — **awaiting explicit approval** before code.

## Recommendation

See closing section of this pack / user-facing summary: **PROCEED WITH CONDITIONS**.

## Phased work (post-approval)

| Phase | Work | Primary packages | Risk |
|-------|------|------------------|------|
| A | RAD manifest `bridge-talent-intelligence`, capabilities, contracts, edges; flags default off | `architecture/` | Low |
| B | Provider interface + Noop + `bridge_rules_v1` scorer + settings | Companion `includes/talent/` | Med |
| C | DB tables evaluations/components/evidence/requirement profiles/versions | `NGC_Database` | Med |
| D | Queue worker `talent.evaluate` + application hooks | Companion platform queue | Med |
| E | REST + Admin Talent Intelligence screens | Companion admin/rest | Med |
| F | Find-a-Tutor optional re-rank after hard filters | Marketplace / smart match | Med |
| G | Fairness denylist + audit/metrics | Companion | Med |
| H | Optional Python similarity sidecar + adapter | `services/ngt-talent-intelligence/` | High (ops) |
| I | Agent/MCP tools permissioned | Agentic | Med |
| J | Tests + headed E2E + evidence report | delivery/ | — |

## Exact Stage 2 MVP (recommended first ship)

1. Bridge-native explainable scorer (no Streamlit, no pickle required).  
2. Capabilities + policy.  
3. Evaluation persistence + async.  
4. Admin decision-support UI.  
5. Optional Find-a-Tutor re-rank flag.  
6. **Defer** PDF/DOCX, Python sidecar, FluentCRM tag fan-out (unless trivial), MCP until flags/tests ready.

## Existing to preserve

- `NGC_Matching`, `NGC_Smart_Matching`, marketplace hard filters  
- `NGC_Tutor_Lifecycle` human approve/reject  
- CPT tutor SSOT  
- Lead fairness patterns  

## Existing to enhance

- Application review with suitability explanation  
- Find-a-Tutor ordering (optional)  
- Architecture capability coverage for matching/talent  

## Upstream to reuse (concepts only)

- Overlap + optional text similarity structure  
- Matched/missing + evidence style explanations  
- Thresholded decision-support labels  

## Upstream to replace

- Streamlit UI, CSV SSOT, tech skill list, unused label encoder, broken requirements, pickle-as-default

## Blocking issues before production

1. Domain ontology + weight sign-off  
2. Explicit ban on auto-approve wired in policy tests  
3. No PDF claim without extractor  
4. Runtime E2E evidence  

## Approval gate

**STOP here until user approves** with any conditions.
