# NextGenTutors-AI-Integration — SWOT / Gap Analysis and Code Review

Date: 2026-07-21
Scope: `NextGenTutors-AI-Integration/` plugin, its Companion/agents-api integration surface, test matrix, and demo-evidence readiness per `.agent-audit/AUTONOMOUS-CODING-AGENT-MASTER-DIRECTIVE.md`.
Mode: review only — no code changes applied in this pass.

## 1. Findings (ordered by severity)

### BLOCKER

**B-1. No runtime evidence chain — plugin has never been observed running.**
The Docker WordPress container fatals during boot via the Kirki plugin (`Undefined constant "Kirki\Framework\Filesystem\FS_CHMOD_FILE"`), which blocks WP-CLI activation of `nextgentutors-ai-integration`. Consequently: activation, `dbDelta` migrations, `/wp-json/ngtai/v1/health`, cron registration, admin screens, WP-CLI commands, and the MATCH-001 vertical slice are all unverified at runtime. The 162 passing assertions are stub-based unit tests only. Per directive §30 and Phase 14, production/demo readiness **cannot be claimed**. Root cause is environmental (Kirki), not this plugin, but the evidence gap is real.

**B-2. `agents-api` endpoint and shared secret are unconfigured.** No live outbound delivery, callback round-trip, or circuit-breaker behavior has been exercised against a real peer. All delivery-path claims rest on mocked `wp_remote_request`.

### HIGH

**H-1. Outbound approval rows can never be inserted (schema mismatch).**
`NGTAI_Outbox_Bridge::create_approval()` inserts columns `event_id`, `risk`, `correlation_id` into `{prefix}ngtai_approvals` — none of which exist in the migrator's table — and omits `agent_run_id`, which is `NOT NULL` with no default. On real MySQL the insert fails every time, so any outbound event whose policy decision is `REQUIRE_APPROVAL` is silently dropped (only an audit log line survives; nothing appears in the Approvals admin queue). The unit-test `wpdb` stub accepts arbitrary columns, so the suite green-lights the bug. Fix: align insert with schema (`approval_id`, `agent_run_id`, `action_name`, `status`, `requested_by`, `payload_json`, `created_at`, `updated_at`) and add a stub-side column-schema assertion so mismatches fail tests.

**H-2. Nonce store converts all DB insert failures into "duplicate" → false idempotent ACK.**
`NGTAI_Nonce_Store::claim()` returns `'duplicate'` whenever `$wpdb->insert()` returns `false`. A missing table, DB outage, or column error is indistinguishable from a genuine replay. `NGTAI_Rest::machine_guard()` then answers HTTP 200 `{success:true, idempotent:true}` — agents-api believes the callback was processed, but the handler never ran. Silent, unrecoverable data loss under infrastructure failure. Fix: inspect `$wpdb->last_error` for duplicate-key (error 1062) and return `WP_Error` (HTTP 5xx, retryable by the caller) for anything else.

**H-3. Canonical signing path is hardcoded to `/wp-json/...`.**
Both callback verification (`NGTAI_Rest_Callbacks::begin()`) and outbound signing assume the REST prefix is `wp-json` and the site lives at the domain root. Any site with a custom `rest_url_prefix`, subdirectory install, or reverse-proxy path rewrite will fail all signature verification with no diagnostic pointing at the cause. At minimum this is an undocumented deployment constraint; better, derive the path from `rest_url()`/`$request->get_route()` and document the canonicalization rule in the contract.

### MEDIUM

**M-1. Self-approval guard is ineffective for its stated purpose.** `decide_approval()` compares the deciding WP user ID against `requested_by`, but machine-created approval rows store an agent name string there, so the comparison never matches. The "human decided_by > 0" invariant holds, but a request initiated by an admin's own action is not actually blocked from self-approval.

**M-2. Circuit-breaker counters are non-atomic.** `record_retryable_failure()` does get-increment-set on a transient; concurrent cron/dispatch workers can lose increments, delaying circuit open. Also, with an object cache the two transients (`ngtai_circuit_failures`, `ngtai_circuit_open`) may not be shared consistently across PHP workers. Acceptable for current scale; document the limitation.

**M-3. `integration-result` callback coerces payloads through the strict `NGTAI_Agent_Result` contract.** Legitimate integration callbacks lacking `event_id`/`agent_run_id`-style fields will be rejected 422. The contract for this route was never pinned against a real agents-api payload — contract-test it before go-live.

**M-4. `NGTAI_Logger` ring buffer writes an option row per log call.** Under bursty callback traffic this is one extra DB write (plus autoload considerations) per request. Consider sampling or batching before production load.

**M-5. Every log/audit path assumes `update_option`/`wpdb` availability mid-request failure.** If the DB is the thing that failed (H-2 scenario), audit writes fail too — there is no secondary sink (error_log) for security-relevant failures like `ngtai_signature_failed`.

### LOW

**L-1.** `NGTAI_Rest_Callbacks::register()` uses `permission_callback => '__return_true'` with the guard inside handlers. Deliberate and commented, but WP plugin-review tooling flags it; a thin permission callback that re-checks header presence (not signature) would silence scanners without weakening the raw-body guard.
**L-2.** `retry_after_seconds()` accepts HTTP-date `Retry-After`, but `strtotime` on attacker-influenced header could produce large values; delivery repository should clamp the backoff ceiling (verify it does).
**L-3.** `on_match_requested()` synthesizes `event_id` with `md5(subject.correlation)` — deterministic (good for idempotency) but undocumented; a second request with same match_id+correlation is silently deduped by the unique key. Confirm that is the intended semantics for re-requested matches.
**L-4.** Duplicate `headers()` normalization runs twice per callback (in `machine_guard` and again in handlers) — trivial waste, refactor candidate.

## 2. SWOT

**Strengths**
- Security architecture is genuinely defense-in-depth: HMAC v1.1 canonical string, ±300s skew, dual-layer nonce claim (transient + durable unique key), business idempotency separate from transport idempotency, deny-by-default policy gate, schema-driven redaction with minor-specific minimization, encrypted-at-rest secrets with refusal to store plaintext.
- Clean layering: contracts / repositories / transport / surface (REST, admin, CLI, cron) with no theme or Companion boundary violations; Companion is consumed only via public hooks (`ngc_domain_event`, `ngc_match_requested`).
- Outbox pattern with bounded retry, dead-letter, and lock recovery is the correct distributed-systems shape for this bridge.
- 12-file standalone unit suite (162 assertions) runs in seconds without WordPress, covering signature tamper/skew, replay, idempotency, redaction, policy, delivery state machine.

**Weaknesses**
- Unit-test stub fidelity gap: the in-memory `wpdb` accepts any column set, which masked H-1 — the highest-value bug in this review. No schema-checked integration layer exists between "stub tests" and "full Docker E2E".
- Zero verified runtime behavior (B-1/B-2): migrations, REST registration, cron scheduling, admin capability wiring — all unproven outside stubs.
- Hardcoded path/prefix assumptions (H-3) and an unpinned `integration-result` contract (M-3) make first contact with the real agents-api likely to fail.
- Observability is DB-bound (M-4/M-5); no failure path survives a DB outage.

**Opportunities**
- Fixing the WP stub to enforce migrator column schemas would convert the whole existing suite into a cheap schema-contract harness (would have caught H-1 automatically).
- The Kirki fatal is a one-line class of fix (define constant / pin version / disable in Docker) that unblocks the entire runtime evidence chain: activation → health 200 → MATCH-001 slice → Phase 14 evidence pack.
- The existing Playwright spec (`e2e/workflows/ai-integration-bridge.spec.ts`) is ready to become the contract gate once a secret is provisioned in the Docker env.
- CLI (`wp ngtai …`) is the natural driver for the demo runbook and idempotent seeding required by Phase 14.

**Threats**
- Silent-drop failure modes (H-1, H-2) are the most dangerous kind for an approval/compliance bridge: the system looks healthy while REQUIRE_APPROVAL events and failed callbacks vanish.
- Divergence risk between the assumed and actual agents-api contract — every inbound field-shape decision here was made from the spec, not from live traffic.
- Third-party plugin instability (Kirki) breaking WP-CLI means operational tooling for this plugin can be held hostage by unrelated plugins; `--skip-plugins` workflows should be documented.
- Test-suite overconfidence: green unit runs will keep masking schema/runtime issues until the integration layer exists.

## 3. Gap matrix vs directive

| Requirement | Status |
| --- | --- |
| Plugin code complete (crypto, signature, nonce, delivery, policy, redaction, REST, admin, cron, CLI) | DONE (unit-verified) |
| Standalone unit suite passing | DONE — 162 assertions |
| Plugin activates cleanly; migrations run | **BLOCKED** (Kirki fatal in Docker) |
| Health endpoint 200 in live environment | **BLOCKED** (same) |
| Live outbound delivery to agents-api | **BLOCKED** (no endpoint/secret configured) |
| MATCH-001 vertical slice + evidence | **NOT DONE** |
| Phase 14 demo evidence pack (`.agent-audit/demo/`, `.agent-audit/evidence/demo/`) | **NOT DONE** |
| Approval queue functional end-to-end | **FAILED** for outbound path (H-1) until fixed |
| Build ZIP / verify-solution re-run after final file set | NOT RE-RUN this pass |
| Production-readiness decision | **NOT READY** — honest status: code-complete, runtime-unverified, two high-severity defects open |

## 4. Test coverage assessment

Covered (unit, stubbed): signature build/verify/tamper/skew, nonce replay, business idempotency, result versioning, redaction + minor minimization, policy gate, delivery state machine, event mapping/envelope validation, callback auth failures.

Missing:
1. Schema-contract tests — stub `wpdb` must reject unknown columns / NOT-NULL violations (would catch H-1).
2. Nonce-store failure-mode test — DB error must not read as duplicate (locks in the H-2 fix).
3. `NGTAI_Api_Client` tests — circuit breaker open/close, HTTPS/allowlist enforcement, Retry-After parsing.
4. Outbox bridge tests — `on_domain_event` policy branching incl. the REQUIRE_APPROVAL insert path.
5. WP-integration smoke (Docker): activate → migrate → health 200 → signed callback 200/duplicate → admin pages render. Blocked on Kirki; run with `--skip-plugins=kirki` or pinned fix.
6. E2E signed-callback spec currently skips without a provisioned secret — wire `NGTAI_TEST_SECRET` into the Docker env so it asserts instead of skipping.

## 5. Proposed remediation order (no changes applied)

1. Fix H-1 (approval insert ↔ schema) + add stub schema enforcement so the suite proves it.
2. Fix H-2 (nonce store error discrimination) + failure-mode test.
3. Unblock Docker runtime (Kirki pin/disable), activate plugin, capture activation/migration/health evidence.
4. Configure agents-api sandbox secret; run Playwright bridge spec un-skipped.
5. Execute MATCH-001 vertical slice; export Phase 14 evidence pack.
6. Address H-3/M-1..M-5 as hardening follow-ups; re-run verify-solution + build.

## 6. Skill applicability note

`ab-test-setup` was requested but is not applicable: no experiment hypothesis, primary metric, or traffic baseline exists for this bridge. Per that skill's refusal conditions, no A/B design was produced.
