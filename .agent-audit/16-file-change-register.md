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
