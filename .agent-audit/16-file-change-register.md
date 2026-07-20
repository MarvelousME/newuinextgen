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
