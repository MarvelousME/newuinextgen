# 07 — Capability Map

## Capabilities to register (Stage 2 core)

| Capability ID | Purpose | Stage 2 |
|---------------|---------|---------|
| `talent.health` | Provider/mode health | Yes |
| `talent.match.evaluate` | Suitability evaluation | Yes |
| `talent.match.explain` | Explanation for evaluation id / payload | Yes |
| `talent.rank` | Rank eligible candidates under a requirement profile | Yes |
| `talent.skills.extract` | Extract skills from structured + optional text | Yes (structured-first) |
| `talent.profile.extract` | Normalize applicant/tutor profile for scoring | Yes |
| `talent.skill_gap.analyze` | Gaps vs requirement profile | Yes |
| `talent.requirement.evaluate` | Validate requirement profile completeness | Yes |

## Tutoring component capabilities (implemented as score components, not separate microservices)

| Component key | Maps to |
|---------------|---------|
| `tutor.subject.match` | Subject overlap |
| `tutor.grade.match` | Grade coverage |
| `tutor.curriculum.match` | Curriculum tags when present |
| `tutor.language.match` | Languages |
| `tutor.availability.match` | Soft score only unless hard-filtered upstream |
| `tutor.location.match` | Province/location |
| `tutor.delivery_mode.match` | Online/in-person/hybrid |

## Explicitly not exposed until real

| Capability | Reason |
|------------|--------|
| `talent.resume.parse.pdf` | No production PDF extractor today |
| `talent.auto.approve` | Forbidden — human authority |
| `talent.safeguarding.infer` | Forbidden — deterministic verification only |

## Consumers

| Consumer | Caps | Notes |
|----------|------|-------|
| Companion admin | evaluate/explain/rank/health | Decision support |
| Find-a-Tutor / marketplace | rank (optional) | After hard filters |
| Agentic hub / MCP | evaluate/explain/rank/gaps | Permissioned; PII minimized |
| AI-Integration | may consume explanations | Does not bypass policy |

## Policy defaults

- Default DENY for agents without explicit grant  
- Human admins: `ngc_manage_matches` / `ngc_admin_operations` / `manage_options`  
- Never allow TI to call lifecycle `approve`/`reject`
