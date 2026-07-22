Change ID: SEC-PF-001
Phase: 3
File: NextGenTutors-Companion/includes/integrations/class-ngc-payfast-itn.php + class-ngc-payfast-gateway.php
Current problem: ITN signature-only; no amount/merchant/replay/passphrase enforcement
Evidence: Source review 2026-07-20
Planned change: Extract NGC_PayFast_Itn; harden gateway handler
Security impact: Prevents forged/replayed payments
Financial impact: Positive — amount binding + idempotency
Tests required: run.php + agent-evaluation.php
Rollback method: Revert gateway to signature-only (not recommended)
Status: COMPLETE
---
Change ID: DOC-DEMO-014
Phase: 14
File: NextGenTutors-Companion/includes/demo/* + admin/class-ngc-demo-admin.php + cli demo_* + .agent-audit/demo/*
Current problem: No relational demo seed / login verification / trigger evidence
Evidence: Phase 14 master directive §27
Planned change: Full demo stack through domain services
Security impact: Demo mode sandbox blocks real payouts/mail side effects
Financial impact: Wallet/payout demo only; PayFast forced sandbox
Tests required: run.php demo assertions (58 total)
Rollback method: Disable demo mode; wp ngc demo_reset --yes
Status: COMPLETE WITH LIMITATIONS
---
Change ID: DOC-MASTER-014
Phase: 14 (directive ingest)
File: .agent-audit/AUTONOMOUS-CODING-AGENT-MASTER-DIRECTIVE.md + .cursor/rules/autonomous-coding-agent-master.mdc + .agent-audit/demo/*
Current problem: Master directive lived only in chat; Phase 14 demo requirements not persisted
Evidence: User directive 2026-07-20
Planned change: Persist full master prompt; insert §27 Phase 14; always-apply Cursor rule; demo stub tree
Security impact: Demo mode / production isolation requirements now mandatory policy
Financial impact: Sandbox-only demo payments mandated
Tests required: N/A (documentation / policy ingest)
Rollback method: Remove Phase 14 section + rule
Status: COMPLETE
---
Change ID: SEC-REST-001
Phase: 3
File: class-ngc-rest-section-cms.php
Current problem: Public section reads unthrottled
Planned change: public_throttled permission
Status: COMPLETE
---
Change ID: AGT-EVAL-001
Phase: 3 / 9
File: tests/agent-evaluation.php + ci.yml
Planned change: Deterministic evaluation harness
Status: COMPLETE
---
Change ID: UX-CRO-006
Phase: UX redesign Phase 6 / master Phase 11
File: NextGenTutors-BeyondInfinity/inc/template-tags.php; NextGenTutors-BeyondInfinity/inc/defaults-production/find-a-tutor.php; pricing.php; register.php; become-a-tutor.php; parent-checkout.php; NextGenTutors-BeyondInfinity/template-parts/sections/impact.php; NextGenTutors-BeyondInfinity/assets/css/components.css; assets/js/main.js; style.css; functions.php; NextGenTutors-Companion/assets/js/ngc-marketplace.js; includes/class-ngc-marketplace.php; nextgencompanion.php; documentation/ux-redesign/README.md; 05-implementation-plan.md; .agent-audit/17-test-evidence.md
Current responsibility: Public conversion surfaces, shared trust/stats/sticky UI, REST-backed marketplace filtering, package versions, and verification records.
Current problem: IA section 5 trust is not consistently injected at decision points; the mobile sticky CTA exposes only Find; find-a-tutor lacks province/subject coverage shortcuts; social-proof counters are static or use an unsupported data-counter contract.
Evidence: documentation/ux-redesign/02-information-architecture.md section 5; 05-implementation-plan.md Phase 6; source review 2026-07-21.
Planned change: Add escaped reusable trust/coverage helpers, contextual trust chips, a three-action mobile nav, URL-driven marketplace coverage filters, reduced-motion-safe counters with suffix/decimal preservation, and live impact metrics.
Dependencies: WordPress taxonomy/query APIs, bi_real_platform_metrics(), bi_real_marketing_kpis(), Companion marketplace REST routes, existing design tokens and booking drawer.
Security impact: Positive/neutral — output remains escaped; URL filters are allowlisted and REST sanitization remains authoritative.
Financial impact: None — presentation only; no payment or payout mutation.
Data impact: Read-only taxonomy and aggregate metric reads; no migration.
Migration required: No.
Tests required: PHP lint on touched PHP; Node syntax checks on touched JS; Companion validate/version verification/unit suite; ui-library catalog snapshot and integration smoke; release package build where disk permits; manual keyboard/mobile/reduced-motion check on deployed WordPress.
Rollback method: Revert UX-CRO-006 files and restore theme 1.9.15 / Companion 1.9.4 version constants and headers.
Status: COMPLETE WITH LIMITATIONS — automated syntax, validation, version, unit, snapshot and integration gates pass; live browser checks and release ZIP build remain NOT VERIFIED because the local Docker bind overlays packaged theme subtrees and C: has only 0.07 GB free.
