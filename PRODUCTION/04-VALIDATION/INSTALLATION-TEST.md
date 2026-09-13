# Installation test

Procedure: `docker/clean-install.ps1` using ZIPs in `01-INSTALLABLE-PACKAGES`. Isolated Compose project **`ngt-clean-install`** on http://localhost:8891 (does not use `docker/.env` `COMPOSE_PROJECT_NAME`).

## Clean-room result (2026-09-12)

WP-CLI exit **0**. Smoke:

| Check | Result |
|---|---|
| Hello Elementor ZIP install | Pass (3.5.1) |
| Companion activate | Pass (`NGC_Plugin=yes`) |
| Plugin Manager, Mission Control, 3D Scroll, Filmstrip, Subjects | Pass (activated) |
| Html Importer | Installed, left inactive |
| BeyondInfinity ZIP install + activate | Pass (`theme=NextGenTutors-BeyondInfinity`, `parent=hello-elementor`) |
| PHP fatals in debug.log | **0** |
| CPT `tutors` | yes (Companion-owned) |
| Shortcode `ngc_find_tutor_form` | yes (Companion, not theme fallback) |
| Home HTTP | **200**, ~163 KB HTML |
| Theme switch to Hello Elementor | Success; Companion remains loaded |
| `/find-a-tutor/` | 404 until pages are provisioned (no page objects yet on empty WP) |

First WP-CLI attempt failed on `wp-content/upgrade` permissions; `clean-install.sh` now chowns `wp-content` as root before installs.

`NGC_Publish_Worker` was listed in Companion bootstrap but the class file does not exist. Bootstrap already skipped it without fatal. The class was removed from the module list and Companion ZIP rebuilt (`028440f61d0d447d26c2717b7f2f43c52ba1a1e52b595ea6319363c91a2856fe`).

Remaining before a signed PRODUCTION PASS: create required pages / NextGen Primary menu via Mission Control or `wp ngt provision run --force-safe`, then headed E2E (Home, Find Tutor, login, dashboards, reduced-motion, JS off).

## Run log

- UTC: 2026-09-12T09:18:50Z — WP-CLI exit 1 (upgrade dir not writable)
- UTC: 2026-09-12T09:23:02Z — WP-CLI exit 0 — URL: http://localhost:8891
