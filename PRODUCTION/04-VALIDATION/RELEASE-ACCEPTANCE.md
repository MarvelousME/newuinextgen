# Release acceptance

Release train **2026.09.12**. Clean-room URL: http://localhost:8891

| Gate | Status |
|---|---|
| ZIP upload install (WP-CLI equivalent of Plugins/Themes Upload) | **Pass** |
| Activation fatals from our code | **Pass** (0 PHP fatals) |
| Honest header versions + CHECKSUMS.sha256 | **Pass** (theme 1.9.29, Companion 1.9.19, Plugin Manager 1.3.5, …) |
| Companion theme-independent | **Pass** (CPT `tutors` present; Hello Elementor activate succeeded with Companion still loaded) |
| Theme without Companion degrades | **Code pass** (`bi_companion_active()` requires `NGC_Plugin`) — not re-tested by deactivating Companion in this run |
| Secrets excluded from ZIPs | **Pass** (no `.env` / `build-src` / `offline-packages`); review `inventory-meta.json` secret_hits (API-key *fields*, not live secrets) |
| Docs match packages | **Pass** (generator used inventory versions) |
| Required pages + NextGen Primary + headed E2E | **Not signed** — empty WP has Home 200 but no provisioned `/find-a-tutor` page yet |

Recommendation: **CLEAN-INSTALL CANDIDATE**. Do not label PRODUCTION PASS until provisioned pages and headed E2E are signed.

Signed off by: ________________  Date: ________________
