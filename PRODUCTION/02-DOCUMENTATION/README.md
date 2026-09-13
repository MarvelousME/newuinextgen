# NextGen Tutors — Production distribution

Release train **2026.09.12**. Package versions are **WordPress headers**, not ZIP filename fiction.

This folder is the client-facing production kit. Install from `../01-INSTALLABLE-PACKAGES/` onto a **brand-new WordPress** instance. Do not unzip the monorepo root as a theme.

## What ships

| Package | Version | Role | Required |
|---|---|---|---|
| Hello Elementor | 3.5.1 | Parent theme | Yes |
| NextGenTutors-BeyondInfinity | 1.9.29 | Presentation | Yes |
| NextGenTutors-Companion | 1.9.19 | Domain / persistence / REST | Yes |
| NextGenTutors-Plugin-Manager | 1.3.5 | Install order / health | Yes |
| NextGenTutors-Mission-Control | 1.0.0 | Onboarding / ops | Yes |
| nextgen-3d-scroll-manager | 1.2.0 | GSAP/3D motion | Yes |
| nextgen-3d-filmstrip | 1.0.0 | Filmstrip UI | Yes |
| nextgen-subjects-widget | 1.1.0 | Subjects grid | Yes |
| NextGenTutors-Html-Importer | 1.0.0 | One-time HTML import | Optional |
| NextGenTutors-AI-Integration | 1.1.0 | Agents-api bridge | Optional |
| NextGenTutors-BeyondMeasure | 1.0.0 | Control-plane SPA | Optional |
| nextgen-automation-hub | 2.0.0 | Legacy overlap — leave off | Optional |

`drop-ins/ngt-ui-library.zip` is **not** a plugin. Copy it to `wp-content/ngt-ui-library` **or** rely on the copy bundled inside the BeyondInfinity theme (`ui-library/`). Companion resolves both.

## Install order

1. WordPress 6.0+ / PHP 8.0+
2. Upload and activate **Hello Elementor**
3. Upload and activate **Companion**
4. Upload and activate Plugin Manager, Mission Control, 3D Scroll Manager, 3D Filmstrip, Subjects Widget
5. Optionally Html Importer (deactivate after import)
6. Upload and activate **BeyondInfinity**
7. Appearance → Menus: confirm **NextGen Primary Grouped** is assigned to Primary
8. Settings → Reading: Home page
9. Create Parent / Tutor / Student users as needed

See [INSTALLATION-GUIDE.md](INSTALLATION-GUIDE.md). Do not call this production-ready until [../04-VALIDATION/RELEASE-ACCEPTANCE.md](../04-VALIDATION/RELEASE-ACCEPTANCE.md) is signed from a ZIP-upload clean-room run.