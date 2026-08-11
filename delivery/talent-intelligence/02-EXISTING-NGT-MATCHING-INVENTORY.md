# 02 — Existing NextGenTutors Matching Inventory

**Evidence date:** 2026-08-11  
**Scope:** Companion, BeyondInfinity, AI-Integration, architecture/, services/

## Production SSOT (Companion)

| Capability | Owner | Path | Notes |
|------------|-------|------|-------|
| Match lifecycle | `NGC_Matching` | `includes/class-ngc-matching.php` | `wp_ngc_matches`; create/score/assign/accept/reject |
| CPT smart scoring | `NGC_Smart_Matching` | `includes/matching/class-ngc-smart-matching.php` | Weighted subject/grade/province/format/rate; REST `/ngc/v1/match/smart` |
| Tutor CPT source | `NGC_Tutor_Cpt_Source` | `includes/matching/class-ngc-tutor-cpt-source.php` | Canonical tutors for scoring |
| Marketplace browse | `NGC_Marketplace` | `includes/class-ngc-marketplace.php` | Directory filters; REST `/marketplace/*` |
| Tutor applications | `NGC_Tutor_Lifecycle` | `includes/class-ngc-tutor-lifecycle.php` | `wp_ngc_tutor_applications`; approve/reject human-driven |
| Registration routing | `NGC_Registration` | `includes/class-ngc-registration.php` | find_tutor → matching; become_tutor → lifecycle |
| Availability | Amelia adapters + calendar REST | `adapters/class-ngc-amelia-*`, `rest/class-ngc-rest-tutor-calendar.php` | Calendar busy — **not** in match score |
| Ethical lead scoring | `NGC_Tutor_Leads` / `NGC_Lead_Criteria` | `includes/agentic/leads/` | Recruitment leads; blocks protected traits |
| AI recommendation gate | AI-Integration | `class-ngtai-callback-controller.php` | Action `match.recommendation`; policy-gated |

## Theme (UI shells)

| Piece | Path | Role |
|-------|------|------|
| Find-a-tutor production page | `defaults-production/find-a-tutor.php` | Embeds `[ngc_tutor_marketplace]` + intake |
| Prototype template | `page-templates/find-a-tutor.php` | Client-side CPT filter — **parallel UI risk** |
| Tutor display helpers | `inc/tutor-data.php` | Cosmetic `matchScore` from rating — **not** match SSOT |

## Smart Matching weights (verified)

From `NGC_Smart_Matching::score_post`:

| Factor | Approx points |
|--------|---------------|
| Subject exact / partial | 40 / 20 |
| Grade exact / partial | 25 / 12 |
| Province | 15 |
| Format | 10 |
| Rate under max | 10 |
| Baseline | +5 |

Legacy `NGC_Matching::score_tutors` similar subject/grade/province rules; delegates to CPT when available.

## REST surface (matching-related)

| Route | Purpose |
|-------|---------|
| `/ngc/v1/matches` | CRUD-ish match records |
| `/ngc/v1/matches/{id}/accept\|reject\|assign` | Human/support actions |
| `/ngc/v1/match/smart` | Public throttled scoring |
| `/ngc/v1/marketplace/tutors` | Hard-filtered directory |
| `/ngc/v1/admin/tutors/{id}/approve\|reject` | Application decisions |

## Architecture capabilities

- **No** registered `matching.*` or `talent.*` capability JSON yet
- Docs propose `matching.score` consolidation (`DUPLICATION-REPORT.md`)
- Hub duplicate: `nextgen-automation-hub/.../class-ngt-hub-matching.php` — prefer Companion

## Explicit gaps (relevant to TalentMatch)

1. No resume/CV upload or document extraction pipeline  
2. No multi-component explainable suitability for **applications**  
3. Availability not in suitability scoring  
4. No curriculum / language / certification components  
5. No versioned weight configuration / evaluation history tables  
6. No Python matching microservice under `services/`  
7. Safeguarding/verification not structured as VERIFIED/PENDING states on match UI  

## Data ownership (SSOT)

| Domain | Owner | Talent Intelligence role |
|--------|-------|--------------------------|
| Tutor identity | WP users + roles | Read |
| Tutor profile | CPT `tutors` + taxonomies | Read |
| Applications | `ngc_tutor_applications` | Read + attach evaluation |
| Student→tutor matches | `ngc_matches` | Optional re-rank after eligibility |
| Amelia employee | Amelia | Link only |
| MasterStudy instructor | MasterStudy | Link only |
| FluentCRM contact | FluentCRM | Tags/events only |
| Suitability evaluation | **None yet** | **Own** evaluation records only |
