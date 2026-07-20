# IMPLEMENTATION-AND-MOTIVATIONS.md

Significant decisions for **nextgentutors-beyondinfinity** — Phase 0.

---

## 1. Phased gated build (not one-shot)

**Decision:** Execute Phase 0 → Gate 0 → Phase 1 → … with acceptance evidence per gate.

**Why:** Three artifacts (theme, companion, AI suite) with POPIA/minors data and AI egress require deny-by-default policy layers that cannot be safely one-shotted.

**Alternatives rejected:** Single mega-PR — rejected (no honest test coverage, high regression risk).

**Trade-off:** Slower initial delivery; safer production posture.

---

## 2. Data layer lives in companion, not theme

**Decision:** Move `{prefix}ngt_earnings|ratings|payouts|referrals|session_logs` from NGT theme to `nextgencompanion` with repository classes.

**Why:** Themes switch; minors' financial/session data must persist. Matches spec §4.1.

**Alternatives rejected:** Keep tables in theme — rejected (data loss on theme switch).

**Trade-off:** Theme must call companion APIs; graceful degradation when inactive.

---

## 3. `ngc_*` as canonical shortcode namespace

**Decision:** Companion owns `[ngc_*]` forms/dashboards; theme consumes only.

**Why:** Sacred interop contract (§0.7). BeyondInfinity already built to this contract.

**Alternatives rejected:** Adopt Desktop `[nextgen_*]` in theme — rejected (breaks documented contract; couples theme to platform plugin).

**Trade-off:** Must build `nextgencompanion` greenfield (missing on disk) or thin bridge — bridge adds debt.

---

## 4. REST namespace consolidation

**Decision:** Target `ngc/v1` for user-facing; deprecate duplicate `ngt/v1` routes with aliases.

**Why:** Three namespaces (`ngt`, `ngtbi`, `ngc`) cause routing and auth confusion (conflict surface #1).

**Alternatives rejected:** Keep parallel namespaces — rejected (duplicate registration, unclear capability model).

**Trade-off:** Migration period for existing Desktop installs using `ngt/v1`.

---

## 5. CPT registration once in plugin

**Decision:** Register `ngt_tutor`, `ngt_testimonial` (and dedupe `nt_tutor`) in companion with `post_type_exists()` guards.

**Why:** Conflict surface #2; theme presentation-only.

**Alternatives rejected:** Dual registration in theme + plugin — rejected (fatal on activation).

---

## 6. BeyondInfinity builder dual-mode retained

**Decision:** Keep `bi_render_page_template()` — theme default vs Elementor/WPBakery content.

**Why:** Already implemented v1.2.1; operators need visual editing without losing launch defaults.

**Alternatives rejected:** Elementor-only — rejected (offline/default launch pages required).

**Trade-off:** Two rendering paths to test per page.

---

## 7. Honest spec vs disk discrepancy

**Decision:** Phase 0 documents actual file counts; Phase 1 ports from verified disk, not spec fiction.

**Why:** Scanned `nextgen-tutors-theme` has 15 PHP files, not 50; no `inc/` folder.

**Alternatives rejected:** Assume spec inventory is current — rejected (would port non-existent modules).

**Trade-off:** User may need to supply fuller theme upload if spec revision exists elsewhere.

---

## 8. AI suite isolated in separate plugin

**Decision:** `beyondinfinity-ai-suite` depends on companion model registry; site bot has no tool execution.

**Why:** Blast-radius separation (§1 architecture); public bot ≠ admin console (trust tiers).

**Alternatives rejected:** Single plugin for all AI — rejected (public site exposure).

**Trade-off:** Two plugins to deploy and version-lock.

---

## 9. Theme rename pending

**Decision:** Rename folder `beyondinfinity` → `nextgentutors-beyondinfinity` in Phase 1.

**Why:** Spec naming, deployment clarity.

**Alternatives rejected:** Keep `beyondinfinity` slug — rejected (conflicts with product naming).

**Trade-off:** Re-activation may re-run page sync.

---

## 10. Missing companion blocks Gates 1–2

**Decision:** Gate 0 passes inventory; Phase 1 theme work proceeds with degradation stubs; Phase 2 companion is blocking for forms/dashboards.

**Why:** Cannot verify `[ngc_*]` without plugin.

**Test ref:** COMPARE-DIFFERENCES.md §9; grep found zero `NGC_VERSION` on Desktop.

---

## 11. Enterprise architecture dossier (v1.4.6)

**Decision:** Publish a versioned, evidence-tagged enterprise blueprint as markdown in-repo rather than relying on chat-only output.

**Why:** Phase 0–1 decisions (companion boundary, workflow contracts, RBAC) need a single navigable reference for operators, integrators, and UAT planning. The dossier distinguishes **VERIFIED** (executable in theme), **PARTIAL** (contracts/UI/external ops), and **NOT VERIFIED** (no implementation).

**Artifacts:**

| Document | Purpose |
|----------|---------|
| [ENTERPRISE-WORKFLOW-BLUEPRINT-v1.4.6.md](ENTERPRISE-WORKFLOW-BLUEPRINT-v1.4.6.md) | Master dossier — modules, personas, journeys, state machines |
| [docs/enterprise-blueprint/APPENDIX-A-RBAC-MATRIX.md](docs/enterprise-blueprint/APPENDIX-A-RBAC-MATRIX.md) | Roles, dashboard gates, login redirects |
| [docs/enterprise-blueprint/APPENDIX-B-TRIGGER-MATRIX.md](docs/enterprise-blueprint/APPENDIX-B-TRIGGER-MATRIX.md) | Event catalog and dispatch confidence |
| [docs/enterprise-blueprint/APPENDIX-C-COMPANION-BOUNDARY.md](docs/enterprise-blueprint/APPENDIX-C-COMPANION-BOUNDARY.md) | Theme ↔ companion ownership boundary |
| [docs/enterprise-blueprint/workflows/](docs/enterprise-blueprint/workflows/) | 25 BPMN-style workflow specs |

**Key code anchors:** `inc/security.php` (RBAC), `inc/workflows.php` + `content/nextgen-workflow-pack.json` (automation), `inc/shortcodes-fallback.php` (form intake).

**Gap closed (2026-07-02):** `parent_register` / `student_register` now map to workflow dispatch with pack workflows. User-account creation remains companion scope.

**Tutor approval (2026-07-02):** `add_user_role` workflow action implemented in theme; `bi_workflow_emit_tutor_approved()` + **Appearance → NextGen Operations** admin screen. Applicant must already exist as a WP user (matched by email from queue).

**Test ref:** Code grep + `php -l`; live UAT still **NOT PRODUCTION-VERIFIED** per [KNOWN-LIMITATIONS.md](KNOWN-LIMITATIONS.md).
