# Testing Documentation

## Test Strategy

- **Static tests**: syntax + required file/table definition checks.
- **Component tests**: service/repository/adapter behavior via controlled requests.
- **Integration tests**: REST routes and admin flows in WordPress runtime.
- **Workflow tests**: event dispatch/orchestration/retry behavior.
- **Security tests**: nonce/capability/sanitization/privacy checks.

## Test Suites

| Suite | Scope | Current Evidence | Status |
|---|---|---|---|
| Static lint/manifest | plugin code validity | `scripts/validate.php` pass | VERIFIED |
| REST route smoke | route registration and response shape | implementation package + verification hooks | PARTIAL |
| Workflow execution | runs/logging/retries | workflow admin + run tables | PARTIAL |
| Dashboard rendering | role-specific dashboards | theme script + REST endpoints | PARTIAL |
| Calendar flow | public slots + slot selection audit + conflict blocking | tutor calendar package | PARTIAL |
| Security/privacy | public data minimization + nonce/caps | code-level review | PARTIAL |

## Recommended UAT Matrix

| Role | Core Test Cases |
|---|---|
| Parent | register, add learner, match, book, pay, review |
| Student | register, view dashboard, view upcoming lessons |
| Tutor | apply, approval path, booking handling, payout visibility |
| Admin | vetting, workflows, verification, repair |
| Finance | payout processing, payment/invoice metrics |
| Support | match/booking assistance, escalation |

## Exit Criteria for Production

1. All `VERIFIED` source-level checks pass in staging runtime.
2. External integration dependencies resolved and tested.
3. Critical journey tests pass for all persona groups.
4. Security/privacy tests pass (especially public APIs/calendars).
5. Backup and rollback drills completed.

