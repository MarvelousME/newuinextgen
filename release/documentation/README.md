# NextGen Tutors — Production Delivery Documentation

**Release:** BeyondInfinity **1.9.17** / Companion **1.9.5**  
**Site (SSOT):** https://www.nextgentutors.co.za  
**Local Docker WP:** http://localhost:8900  
**Generated:** 2026-08-02  

**PDF:** PDF export is pending operator tooling (pandoc/wkhtmltopdf). Markdown is the authoritative deliverable in this release pack. Do not claim PDFs exist until exported.

**Production deploy:** NOT authorized in this session. See [../INPUTS-REQUIRED.md](../INPUTS-REQUIRED.md).

## Document index

| # | Document | Purpose |
|---|----------|---------|
| 01 | [FUNCTIONAL-SPECIFICATION](01-FUNCTIONAL-SPECIFICATION.md) | What the platform does for each role |
| 02 | [TECHNICAL-SPECIFICATION](02-TECHNICAL-SPECIFICATION.md) | Packages, APIs, tables, CLI surfaces |
| 03 | [SYSTEM-DOCUMENTATION](03-SYSTEM-DOCUMENTATION.md) | Runtime topology and component map |
| 04 | [USER-MANUAL](04-USER-MANUAL.md) | End-user and operator how-to |
| 05 | [TUTORIALS](05-TUTORIALS.md) | Guided setup and journey walkthroughs |
| 06 | [DEPLOYMENT-GUIDE](06-DEPLOYMENT-GUIDE.md) | Local, staging, and production deploy path |
| 07 | [FULL-SYSTEM-ARCHITECTURE](07-FULL-SYSTEM-ARCHITECTURE.md) | Architecture narrative + diagram index |
| 08 | [TEST-AND-VERIFICATION-REPORT](08-TEST-AND-VERIFICATION-REPORT.md) | Evidence status and gates |
| 09 | [CONFIGURATION-REGISTER](09-CONFIGURATION-REGISTER.md) | Options, SSOT, secrets boundary |
| 10 | [SECURITY-PRIVACY-SAFEGUARDING](10-SECURITY-PRIVACY-SAFEGUARDING.md) | Trust boundaries and child safety |
| 11 | [OPERATIONS-RUNBOOK](11-OPERATIONS-RUNBOOK.md) | Day-2 ops, recovery, CLI |
| 12 | [RELEASE-NOTES](12-RELEASE-NOTES.md) | This release changelog |
| 13 | [KNOWN-LIMITATIONS](13-KNOWN-LIMITATIONS.md) | Honest gaps and PARTIAL items |
| 14 | [TRACEABILITY-MATRIX](14-TRACEABILITY-MATRIX.md) | Requirement → code → evidence |

## Diagrams

SVG architecture diagrams live in [`../diagrams/`](../diagrams/). Each file includes `<title>` and `<desc>`.

## Related release files

| File | Purpose |
|------|---------|
| [../INPUTS-REQUIRED.md](../INPUTS-REQUIRED.md) | Secrets and business decisions (never packaged) |
| [../RELEASE-NOTES.md](../RELEASE-NOTES.md) | Short pointer/summary of doc 12 |
| [../KNOWN-LIMITATIONS.md](../KNOWN-LIMITATIONS.md) | Short pointer/summary of doc 13 |
| [../09-CONFIGURATION-REGISTER.md](../09-CONFIGURATION-REGISTER.md) | Pointer to documentation/09 |

## Status legend

| Mark | Meaning |
|------|---------|
| VERIFIED | Confirmed in code and/or filed evidence |
| PARTIAL | Implemented with incomplete evidence or caveats |
| UNVERIFIED | Not proven in this session (e.g. production host) |
| OPEN | Operator/business input still required |
