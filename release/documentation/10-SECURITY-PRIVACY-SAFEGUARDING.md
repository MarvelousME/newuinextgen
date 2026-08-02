# 10 — Security, Privacy, and Safeguarding

**Release:** BI 1.9.17 / NGC 1.9.5 · **Generated:** 2026-08-02  
**PDF:** PDF export pending operator tooling (pandoc/wkhtmltopdf). Markdown is authoritative.

## 1. Principles

- Least privilege via role map (`ngt_*` / `ngc_*`).
- Object-level access via `NGC_Access` on booking/matching REST (IDOR remediation VERIFIED in backlog).
- No secrets in git or release packages.
- Minors: guardian-linked accounts; retention/export/erasure tools present (PRIV-001 marked FIXED in backlog — re-verify on target).
- Demo mode sandboxes email and forces PayFast sandbox filters.

## 2. Trust boundaries

| Zone | Controls |
|------|----------|
| Public web | Theme + public REST with capability checks |
| Authenticated portals | Role-gated dashboards |
| WP Admin | Caps + Setup Wizard / Platform admin |
| Payment | Signed ITN / idempotent webhook handling (unit + sandbox evidence historically) |
| AI | BYOK keys server-side; HMAC callbacks when enabled |
| Host | SSH/host panel — out of app scope |

Diagram: `12-security-trust-boundaries.svg`.

## 3. Safeguarding

| Control | Status |
|---------|--------|
| Safeguarding officer role | In business `role_map` |
| Moderator queue / SLA / escalate | SFG-001 FIXED in backlog |
| Escalation contacts | **OPEN** IN-018 — must be supplied by compliance |
| Fraud rules | FRD-001 FIXED (15 rules) per backlog |

## 4. Privacy (POPIA-oriented — operator must confirm legal copy)

| Capability | Notes |
|------------|-------|
| WP personal data exporters/erasers | Companion privacy modules |
| Retention cron | Present per backlog |
| Platform privacy UI | Present per backlog |
| Public privacy/terms content | Legal entity/VAT/address **OPEN** — do not invent |

## 5. Operational security checklist

- [ ] Strong admin passwords + MFA at host/IdP if available  
- [ ] phpMyAdmin not publicly exposed (SEC-001 fixed in compose pattern)  
- [ ] HTTPS on production (UNVERIFIED this session)  
- [ ] Demo mode off; demo credentials rotated/cleared  
- [ ] PayFast passphrase only in Woo settings  
- [ ] Backup encryption / access control (host-level)  

## 6. Incident pointers

See Operations Runbook (doc 11) for lock clear, rollback, and restore. Escalate safeguarding incidents to designated officer contacts once IN-018 is filled.
