# 05 — Duplication Analysis

## Duplication matrix

| Existing Capability | Existing Owner | TalentMatch Equivalent | Decision |
| ------------------- | -------------- | ------------------------ | ---------------------------- |
| Tutor marketplace search / filters | `NGC_Marketplace` + theme Find-a-Tutor | Candidate search | **KEEP** — hard filters stay authoritative |
| Smart / legacy tutor scoring | `NGC_Smart_Matching`, `NGC_Matching` | Final score / ranking | **ENHANCE** — optional Talent Intelligence re-rank **after** eligibility |
| Tutor CPT profile | CPT `tutors` + taxonomies | Candidate profile | **KEEP** — SSOT; TI reads only |
| Subjects / grades / formats | Taxonomies + meta | Skills list | **REUSE** as tutoring features; **do not** import tech skill list |
| Match records | `wp_ngc_matches` | Candidate-job pair | **KEEP** for student↔tutor; **ADD** separate evaluation entity for applications/requirements |
| Tutor applications | `NGC_Tutor_Lifecycle` | Resume intake | **KEEP**; TI attaches decision support only |
| Application approve/reject | Admin + REST | Recommendation labels | **KEEP human authority**; map AI labels to review statuses only |
| Agentic tutor lead scoring | `NGC_Tutor_Leads` | Recruiter quality score | **CONSOLIDATE patterns** (fairness); do not merge stores blindly |
| AI `match.recommendation` | AI-Integration | Recommendation | **ADAPT** — TI can feed explanations; policy remains |
| Hub `NGT_Hub_Matching` | Automation Hub | Parallel scorer | **DO NOT EXTEND** — prefer Companion |
| Theme prototype find-a-tutor | Theme template | Search UI | **DO NOT DUPLICATE** — prefer marketplace shortcode |
| Gamification scores | `NGC_Scoring_Engine` | Unrelated | **IGNORE** |
| Memory search | Tencent memory | Unrelated semantic store | **IGNORE** for tutor matching |
| Streamlit TalentMatch UI | Upstream | Admin screens | **DO NOT EMBED** |
| CSV ranking files | Upstream | Analytics | **EXPORT format only** |

## Anti-duplication rules (mandatory)

1. One Find-a-Tutor production path: marketplace + hard filters → optional TI rank.  
2. One tutor profile SSOT: CPT/users — TI never forks tutors.  
3. One application approval authority: `NGC_Tutor_Lifecycle` humans.  
4. New code owns **evaluations**, not tutors/matches/bookings.  
5. No second queue runtime; use `NGC_Durable_Queue`.
