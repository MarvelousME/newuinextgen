# 12 — Release Notes

**Release:** BeyondInfinity **1.9.17** / Companion **1.9.5** · **Generated:** 2026-08-02  
**PDF:** PDF export pending operator tooling (pandoc/wkhtmltopdf). Markdown is authoritative.

Short summary also at [`../RELEASE-NOTES.md`](../RELEASE-NOTES.md).

## Highlights

| Area | Change |
|------|--------|
| Versions | Theme 1.9.17 + Companion 1.9.5 release pairing |
| Provisioning | Versioned **32-step** `NGC_Provisioning_Engine` |
| Admin | Setup Wizard page `ngc-setup-wizard` |
| CLI | `wp ngt provision run\|catalogue\|status\|rollback\|clear-lock` |
| CLI | `wp ngt system provision` plus existing inspect/preflight/configure/seed/verify/run-all |
| Docs | This `release/documentation/` pack + `release/diagrams/` SVGs |
| Business SSOT | `config/nextgentutors-business-profile.json` (ZAR, Johannesburg, contacts) |
| Secrets policy | PayFast/SMTP/AI never packaged; tracked in `INPUTS-REQUIRED.md` |
| Phase 14 | Demo status **COMPLETE WITH LIMITATIONS** |

## Included first-party packages

BeyondInfinity · Companion · AI-Integration · Mission-Control · Plugin-Manager · Html-Importer · ngt-ui-library (bridge/deploy note).

## Not in this release authorization

- Production deployment to https://www.nextgentutors.co.za  
- Live secret material  
- PDF exports of these manuals  

## Upgrade notes

1. Backup DB/files.  
2. Deploy theme + Companion pair together.  
3. Run `wp ngt provision catalogue` then dry-run / apply on staging first.  
4. Re-enter secrets only via Woo / FluentSMTP / AI settings if environment is new.  
5. Keep demo mode off on production.

## Evidence

SHA-256: `.agent-audit/evidence/release/SHA256-BI-1.9.17-NGC-1.9.5-2026-08-02.txt`
