# 08 — Test and Verification Report

**Release:** BI 1.9.17 / NGC 1.9.5 · **Generated:** 2026-08-02  
**PDF:** PDF export pending operator tooling (pandoc/wkhtmltopdf). Markdown is authoritative.

## 1. Verdict

| Area | Status |
|------|--------|
| Package versions synchronized (BI 1.9.17 / NGC 1.9.5) | VERIFIED in source + SHA256 evidence files |
| Provisioning engine (32 steps) + Setup Wizard + CLI | VERIFIED in code |
| Phase 14 demo environment | **COMPLETE WITH LIMITATIONS** |
| Production deploy / live host checks | NOT RUN — not authorized |
| PayFast live / SMTP live / AI live | OPEN secrets; sandbox paths PARTIAL per prior audits |
| Full a11y audit | OPEN (A11Y-001) |

## 2. Evidence pointers

| Evidence | Location |
|----------|----------|
| Release hashes 2026-08-02 | `.agent-audit/evidence/release/SHA256-BI-1.9.17-NGC-1.9.5-2026-08-02.txt` |
| Prior release hashes | Same folder dated 2026-07-26 / 2026-07-22 |
| Demo journeys | `.agent-audit/demo/journeys/` |
| Demo runbook | `.agent-audit/demo/LIVE-DEMONSTRATION-RUNBOOK.md` |
| Remediation backlog | `.agent-audit/15-remediation-backlog.md` |
| Production readiness notes | `.agent-audit/20-production-readiness.md` |
| System verification JSON runs | `.agent-audit/evidence/runtime/system-verification-*.json` |

Individual JSON run outcomes are **environment-specific**; treat latest files as PARTIAL evidence unless an operator re-validates on the target host.

## 3. Recommended verification commands

```text
wp ngt system inspect
wp ngt system preflight
wp ngt provision catalogue
wp ngt provision run --dry-run
wp ngt system verify
wp ngt system export-report
```

Staging demo:

```text
wp ngt system seed --allow-demo
wp ngc demo_verify
```

## 4. Known test gaps (honest)

| Gap | Severity | Notes |
|-----|----------|-------|
| Production ITN against live PayFast | HIGH for go-live | Requires secrets + authorization |
| Every journey trigger/notification pack | MEDIUM | Phase 14 limitations |
| Kirki / Docker fatals historically blocking AI plugin | MEDIUM | Env-dependent; re-check on current stack |
| Headed e2e on production URL | HIGH | Not authorized this session |

## 5. Sign-off template

| Role | Name | Date | Result |
|------|------|------|--------|
| Operator | | | PASS / FAIL |
| Compliance | | | PASS / FAIL |
| Finance | | | PASS / FAIL |
