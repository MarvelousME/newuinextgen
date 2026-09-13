# NextGen Tutors PRODUCTION

Release train **2026.09.12**. Build with:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/build-production-release.ps1
```

Clean-room ZIP install (isolated Docker project, port **8891**):

```powershell
cd docker
.\clean-install.ps1
```

Start at [02-DOCUMENTATION/README.md](02-DOCUMENTATION/README.md). Verify checksums in `CHECKSUMS.sha256`. Do not install the monorepo root as a theme.
