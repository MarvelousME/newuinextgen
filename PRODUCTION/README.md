# NextGen Tutors â€” PRODUCTION Delivery

**Release train:** 2026.09.14  
**Packages:** `01-INSTALLABLE-PACKAGES/`  
**Checksums:** `CHECKSUMS.sha256`  
**Manifest:** `RELEASE-MANIFEST.json`

## What this is

A client-ready production distribution of the NextGen Tutors WordPress theme and first-party plugins, plus architecture and operations documentation. **TutorFabulous** is the active brand / edit root; **BeyondInfinity** is the legacy package name still used in some ZIP / train labels.

## Quick start

1. Read `02-DOCUMENTATION/INSTALLATION-GUIDE.md`
2. Verify `CHECKSUMS.sha256`
3. Install packages in the documented order on a **fresh** WordPress site
4. Configure external credentials (PayFast, SMTP, CRM/LMS as needed)
5. Sign `04-VALIDATION/RELEASE-ACCEPTANCE.md` only after clean-install evidence

## Build / test (maintainers)

```bash
python3 scripts/build-production-release.py
# Windows: powershell -ExecutionPolicy Bypass -File scripts/build-production-release.ps1

cd docker
./clean-install.sh          # Linux/macOS — http://127.0.0.1:8891
# or: .\clean-install.ps1   # Windows PowerShell
```

`clean-install.sh` uses `docker-compose.clean-install.host.yml` when Docker bridge networking is unavailable (common in nested/cloud VMs). Packages under `01-INSTALLABLE-PACKAGES/` are gitignored (`*.zip`); rebuild before install.

## Documentation map

| Folder | Purpose |
|--------|---------|
| 01-INSTALLABLE-PACKAGES | Theme/plugin ZIPs + Hello Elementor + drop-ins |
| 02-DOCUMENTATION | Install, admin, persona, security, upgrade guides |
| 03-ARCHITECTURE | System/theme/companion/data/REST/RBAC/motion docs |
| 04-VALIDATION | Inventory, parity, security, install tests, logs |
| 05-MIGRATION | Legacy mapping + migration guides |
| 06-CLIENT-HANDOVER | Non-developer start pack |
| 07-RELEASE-MANIFEST | Per-package hashes and release metadata |

## Support

See `02-DOCUMENTATION/TROUBLESHOOTING.md`. Do **not** install the monorepo root as a theme.
