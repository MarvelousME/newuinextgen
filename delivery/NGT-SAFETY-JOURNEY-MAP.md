# NGT Safety & Compliance Journey Map

**Date:** 2026-08-16  
**Authority:** Companion `NGC_Safeguarding` (+ fraud bridge) — marketing plugins are not authorities

---

## Target chain

```text
VERIFICATION → PUBLICATION POLICY → SESSION SAFETY
  → MONITORING → INCIDENT → ESCALATION → RESOLUTION → AUDIT
```

---

## Step map

### 1. Safety verification facts (authoritative)

Evidence categories: ID Verified, Background Cleared, Reference Checked, Training Complete, Curriculum Trained.

| Rule | |
|---|---|
| SoR | NGT safeguarding / people verification records |
| GamiPress | Visual projection of **approved** badges only — never proves verification |

### 2. Listing eligibility

Intent: “≥3 badges” — **POLICY REQUIRES CONFIRMATION**.

Preferred design: `TutorListingEligibilityPolicy` with mandatory checks + optional count, e.g.:

- ID Verified — mandatory  
- Background Cleared — mandatory  
- Training Complete — mandatory  

Marketplace consumes ALLOW / DENY / REQUIRE_REVIEW. GamiPress must not decide visibility.

### 3. Session recording

| Source | Statement |
|---|---|
| Theme safety-guide defaults | “Product session recording is **not** offered”; parents may join as silent observer |
| POPIA consent checkbox copy | Same — recording not offered |
| Blueprint | Parent opt-in recording consent |

**Conflict:** product currently denies recording; blueprint asks for consent state machine. Do **not** silently enable recording. Flag **POLICY REQUIRES CONFIRMATION**.

If recording ever enabled: `RecordingConsentGranted` / `Withdrawn`; authorize guardian→student→session→consent→policy; audit version/timestamp. Never infer from booking alone.

### 4. AI moderation

| Allowed | Forbidden |
|---|---|
| detect, score, flag, summarize | convict, permanent suspend, close case, punitive autonomy |

Canonical: `SafetyFlagRaised` (not `SafetyViolationConfirmed` without human/policy).

Fields: flag_id, session_id, subject_id, signal_type, severity, confidence, provider, model, timestamp, evidence_reference. Do not dump conversation content into workflow logs.

Current: `NGC_Safeguarding` documents “AI classifications are signals only”; fraud engine can escalate to safeguarding — **ALIGNED** with intent.

### 5. Escalation

```text
SafetyFlagRaised → Safety Rules → SafetyCaseCreated → Escalation Workflow
  → Fluent Support (adapter) / notify safety team / SMS manager
  → session restriction policy / immutable audit
```

AutomatorWP must not be final authority. Current SoR: `wp_ngc_safeguarding_cases`.

### 6. SLA

| Intent | Current |
|---|---|
| 2-hour high-priority response | `NGC_Safeguarding::SLA_HOURS['critical'] = 2` hours; cron `ngc_safeguarding_sla_tick` |
| Business rule key | `ngt.safety.high_priority_response_sla` (extract from const → configurable) |
| Breach event | escalate path exists; name as `SafetySlaBreached` |

Note: critical SLA is **2 hours**, high is **4 hours** — map “2-hour” to critical priority explicitly.

### 7. Parent observer mode

Business copy claims silent observer. Jitsi adapter **UNVERIFIED** for observer/listen-only/hidden role. Label **UNVERIFIED PROVIDER CAPABILITY** — do not fake UI.

Auth: Guardian → GuardianStudentRelationship → Session → ObserverPolicy.

### 8. Report button

Session UI → `SafetyIncidentReported` → `SafetyCaseCreated` (not email-only).

### 9. POPIA / PCI / DPIA / vendors

| Concern | Current | Target |
|---|---|---|
| POPIA checkout consent | `NGC_Popia_Consent` | Versioned, queryable consent records |
| PCI | PayFast gateway — card data must not persist | Audit paths; keep PayFast boundary |
| Vendor DPA | Not a code feature | `VendorComplianceRecord` config/evidence |
| DPIA | Governance | Optional `DpiaReview*` events — does not replace legal assessment |

---

## State machine (cases)

Current statuses include `open` + resolution fields. Proposed alignment:

```text
FLAGGED → TRIAGED → UNDER_REVIEW → ACTION_REQUIRED
  → RESTRICTED → RESOLVED → CLOSED
```

Map carefully onto existing `status` column; migrate with adapter if needed.

---

## Graph

```text
Verification Evidence → Listing Eligibility → Session
  → Safety Signal / Report → Case → Triage
  → Restriction / Escalation → Resolution → Audit
```
