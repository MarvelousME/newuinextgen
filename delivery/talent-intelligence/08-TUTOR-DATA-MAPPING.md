# 08 — Tutor Data Mapping

## Terminology translation

| TalentMatch | NextGenTutors |
|-------------|---------------|
| Candidate | Tutor Applicant / Tutor |
| Job Description | Tutor Requirement Profile |
| Candidate Match Score | Tutor Suitability Score |
| Recruiter Recommendation | Administrative Decision Support |
| Matched Skills | Matched Criteria |
| Missing Skills | Profile Gaps |

## Data ownership matrix

| Data | Authoritative store | TI may |
|------|---------------------|--------|
| WP user | `wp_users` | read id/role |
| Tutor CPT | `tutors` + taxonomies subject/grade/province/learning_format | read |
| Application | `ngc_tutor_applications` | read; write evaluation FK only |
| Match request | `ngc_matches` | read; optional rank annotation |
| Amelia employee | Amelia | read link meta only |
| MasterStudy instructor | MasterStudy | read link meta only |
| FluentCRM contact | FluentCRM | emit tags/events; not score SSOT |
| Requirement profiles | **new** `ngc_talent_requirement_profiles` | own |
| Evaluations | **new** `ngc_talent_evaluations` (+ components/evidence) | own |
| Weight configs / model versions | **new** options/tables | own |
| Resume blobs | **none today** | do not invent until extractor approved |

## Field mapping (structured)

| Requirement / candidate field | NGT sources |
|-------------------------------|-------------|
| subjects | taxonomy `subject`, application `subjects`, user meta `ngc_subjects` |
| grades | taxonomy `grade`, meta |
| curricula | application/meta if present; else INSUFFICIENT_DATA component |
| qualifications | bio/meta text — **claims only, NOT VERIFIED** |
| skills | derived from subjects + explicit skill meta if added |
| languages | meta if present; else gap |
| availability | Amelia calendar — hard filter or soft component |
| location | province taxonomy / meta |
| delivery mode | `learning_format` taxonomy |
| experience | application bio / experience fields — heuristic years extract optional |

## Resume/CV

**Finding:** Become-tutor / application forms use structured fields (name, email, phone, subjects, experience/bio, province). **No CV upload pipeline** in Companion.

**Decision:** Stage 2 scores **structured profiles**. Free-text bio may use optional NLP similarity. PDF/DOCX = separate approved phase with secure extraction adapter.
