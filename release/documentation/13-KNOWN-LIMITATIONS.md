# 13 — Known Limitations

**Release:** BI 1.9.17 / NGC 1.9.5 · **Generated:** 2026-08-02  
**PDF:** PDF export pending operator tooling (pandoc/wkhtmltopdf). Markdown is authoritative.

Summary mirror: [`../KNOWN-LIMITATIONS.md`](../KNOWN-LIMITATIONS.md).

## Status summary

| Topic | Status |
|-------|--------|
| Phase 14 demo | **COMPLETE WITH LIMITATIONS** |
| Production deploy this session | **NOT AUTHORIZED** |
| Production host verification | **UNVERIFIED** |
| PDF manuals | **Pending** pandoc/wkhtmltopdf |
| Live PayFast / SMTP / AI | **OPEN** inputs |

## Limitation register

| ID | Limitation | Impact | Mitigation |
|----|------------|--------|------------|
| LIM-01 | Phase 14 journey/trigger/notification evidence incomplete for some packs | Demo evaluator gaps | Use Demo Control + filed journeys; do not over-claim |
| LIM-02 | Backup step records awareness only — does not create backups | Ops risk | Operator must run host backup + restore proof |
| LIM-03 | Third-party install step may be PARTIAL if Amelia/PayFast zips absent | Booking/payments | Manual install from licensed zips |
| LIM-04 | Products/pricing not invented by provisioner | Commerce empty until IN-008 | Business supplies ZAR prices |
| LIM-05 | Notification email may still be personal mailbox in SSOT | Ops routing | Confirm IN-016 before prod |
| LIM-06 | Accessibility audit OPEN | Compliance | Schedule A11Y-001 |
| LIM-07 | Production HTTPS/DNS/host hardening UNVERIFIED here | Go-live | Operator host checklist |
| LIM-08 | AI runtime historically blocked by env issues (e.g. Kirki) in some Docker runs | AI features | Re-verify `ngtai` health on target |
| LIM-09 | PDF export not produced | Distribution | Export when tooling available |
| LIM-10 | Mission Control version 1.0.0 vs Companion 1.9.5 — independent versioning | Support clarity | Track pair in release notes |

## Explicit non-claims

- Do not claim production is live-configured from this documentation pack alone.  
- Do not claim all 32 provision steps succeed on every host without running them.  
- Do not claim secrets are “included.”  
- Do not claim Phase 14 is unqualified COMPLETE.
