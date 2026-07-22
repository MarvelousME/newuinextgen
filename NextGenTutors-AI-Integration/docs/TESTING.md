# Testing

## Running

From the plugin directory:

```bash
php tests/run.php
# or
composer test
```

No WordPress installation, database, or network access is required. Exit code 0 means every assertion passed; any failure exits 1.

## How it works

- `tests/bootstrap.php` defines the constants (`NGTAI_VERSION` = 1.1.0, test API URL/key/secret), the WordPress function/class stubs (`WP_Error`, options, transients, filters, `wp_remote_request` honoring the `pre_http_request` short-circuit filter), and `NGTAI_Test_WPDB` — an in-memory `wpdb` double implementing the unique-key and status-transition semantics of all plugin tables (`callback_nonces`, `idempotency`, `agent_results`, `approvals`, `deliveries`, `audit`).
- `tests/run.php` loads the production classes from `includes/` unmodified and executes the test files in order. Production code is exactly what ships; only the WordPress environment is simulated.
- HTTP is mocked per-test by registering a `pre_http_request` filter, the same mechanism WordPress core uses.

## Test files

| File | Covers |
| --- | --- |
| `test-signature.php` | Canonical string (`timestamp\nnonce\nMETHOD\npath\nsha256(body)`), signing, header building, round-trip verification. |
| `test-signature-tamper.php` | Body/path/method/key/HMAC tampering → `ngtai_body_digest_mismatch`, `ngtai_signature_mismatch`, `ngtai_unknown_key`, `ngtai_missing_header`. |
| `test-signature-skew.php` | Stale, future, and malformed timestamps vs. the configured skew window. |
| `test-nonce-replay.php` | `NGTAI_Nonce_Store::claim()` returns `true` then `'duplicate'`; durable layer blocks replay after cache flush; expiry purge. |
| `test-idempotency.php` | `seen_or_remember()` unique-insert semantics, including key truncation. |
| `test-redaction.php` | Blocked keys, PII minimization, payload allowlists, candidate eligibility filtering. |
| `test-minor-minimization.php` | Minor profile: never-send removal and deterministic learner pseudonymization. |
| `test-callback-auth.php` | Callback auth reject paths plus governed result handling: idempotent replays, ranking guardrails, dotted action names, policy denial. |
| `test-event-mapping.php` | Companion → contract type mapping, envelope validation, redaction integration. |
| `test-outbox-delivery.php` | Outbox insert/claim/deliver/retry/dead-letter, lock recovery, status counts, full bridge path with mocked HTTP. |
| `test-policy-gate.php` | Execution-action denial (incl. `finance.payout.release`), global pause, schema gating, approval escalation. |
| `test-result-versioning.php` | Unique `(agent_run_id, result_version)`, hydration, `mark_applied`. |

## PHPUnit

`phpunit.xml.dist` is provided for IDE/CI integration (bootstrap points at `tests/bootstrap.php`). The canonical runner remains `php tests/run.php`, which needs no dev dependencies.

## Conventions

- Assertions use `ngtai_assert( string $label, bool $ok )`; every label prints `PASS`/`FAIL`.
- Tests must not modify production code in `includes/` and must clean up any options, transients, or filters they set.
- New tables or query shapes used by production code need corresponding support in `NGTAI_Test_WPDB`.
