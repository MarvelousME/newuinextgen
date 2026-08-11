# 06 — Target Architecture

## North star

```text
                  NEXTGENTUTORS PLATFORM
                           |
                 Capability / Policy Layer
                           |
                  Talent Intelligence API
                           |
              +------------+-------------+
              |                          |
   Bridge TalentMatch Adapter      Future Provider
              |
      Talent Intelligence Service
      (Bridge-native scorer v1;
       optional Python NLP sidecar)
              |
      Domain features + explainable components
```

## Non-negotiables

1. No Streamlit in production UX.  
2. WordPress depends on `talent.*` capabilities, not Python URLs/DTOs.  
3. Hard filters / safeguarding / eligibility **before** AI suitability.  
4. Humans approve/reject tutors.  
5. TI failure ⇒ registration/search/booking continue.  
6. Flags default **OFF**.

## Proposed subsystem id

`bridge-talent-intelligence` (hyphenated per RAD schema; display name “NextGenTutors Talent Intelligence”).

Upstream attribution: Kumkumrathor7078/TalentMatch-AI (algorithm inspiration).

## Provider interface (conceptual)

```text
NGC_Talent_Intelligence_Provider_Interface
  health
  evaluate_match
  rank
  explain
  extract_skills   (structured fields first; free-text optional)
```

Implementations:

| Provider | When |
|----------|------|
| `noop` | DISABLED / DEGRADED |
| `bridge_rules_v1` | Default Stage 2 — multi-factor tutoring scorer in Companion |
| `talentmatch_nlp_v1` | Optional — internal Python service for text similarity only |

Rationale: upstream live algorithm is simple enough that **Bridge-native v1** delivers correct tutoring domain scoring without pickle/Streamlit. Python sidecar is additive for free-text bio/JD similarity, not the sole path.

## Request priority pipeline

```text
SECURITY / SAFEGUARDING
        ↓
ELIGIBILITY (approved/active tutor, subject hard filter, …)
        ↓
BUSINESS RULES
        ↓
AVAILABILITY (when used as hard filter)
        ↓
TALENT SUITABILITY RANKING (optional)
```

## Async path

```text
tutor.application.updated / talent.profile.changed
        → NGC_Durable_Queue (type talent.evaluate)
        → Worker → Provider.evaluate_match
        → Persist TalentEvaluation (+ components/evidence)
        → Audit + metrics
```

## Admin placement

```text
NEXT GEN TUTORS
  └── Tutors
        └── Talent Intelligence
```

## Find-a-Tutor

Hard-filtered marketplace pool → optional `talent.rank` for ordering only.
