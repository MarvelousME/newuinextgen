# 14 — Rollback Plan

## Triggers

- Incorrect rankings harming ops trust  
- Security incident (PII/pickle)  
- Performance regression on Find-a-Tutor  
- Policy violation (auto-decision leakage)

## Steps

1. Set `talent.enabled=false` (and child flags).  
2. Confirm noop provider via `talent.health`.  
3. Stop `talent` compose profile if running.  
4. Verify marketplace/matching use pre-TI paths.  
5. Freeze weight config edits.  
6. Retain DB evaluations for audit (do not drop).  
7. Communicate admin banner: matching decision-support unavailable.

## Data

- No destructive wipe of `ngc_matches` / applications / tutors.  
- Optional: mark evaluations `superseded` if model yanked.

## Verification

- Smoke: become-tutor submit, find-tutor search, booking create.  
- Admin: applications approve still works without TI.
