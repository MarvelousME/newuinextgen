# NextgenTutors-TutorFabulous

WordPress child theme for NextGen Tutors (Hello Elementor parent).

## Canonical edit root (traditional monolith default)

| Rule | Value |
|---|---|
| **Edit / package root** | `NextgenTutors-TutorFabulous/` |
| **Legacy alias package** | `NextGenTutors-BeyondInfinity/` (same product family; keep for old activations) |
| **Brand (Theme Name)** | `NextgenTutors-TutorFabulous` |
| **Text Domain** | `beyondinfinity` — stays (do not rename; Companion / `bi_*` i18n depend on it) |
| **Page templates** | Traditional WordPress `page-{slug}.php` + `Template Name:` (About, Pricing, …) — **not** BeyondInfinity-named |

**Ship and develop from `NextgenTutors-TutorFabulous/`.** Do not present pages, admin labels, or `@package` headers as BeyondInfinity. BeyondInfinity remains a legacy stylesheet slug / package alias only.

Identity reference: this file. Docker mounts: `docker/docker-compose.yml`.

## Identity

| Field | Value |
|---|---|
| **Theme Name** | `NextgenTutors-TutorFabulous` |
| **Stylesheet slug (primary)** | `nextgentutors-tutorfabulous` |
| **Legacy slug (alias)** | `nextgentutors-beyondinfinity` |
| **Text Domain** | `beyondinfinity` (**do not change**) |
| **PHP APIs** | `bi_*`, `ngt_*`, Companion `ngc_*` shortcodes |
| **Version** | 2.0.0 |

## Why Text Domain stayed `beyondinfinity`

Companion, Elementor design-system widgets, and hundreds of `__()` calls use `beyondinfinity`. Renaming the text domain would break translations and plugin checks. Branding is the **Theme Name** + body classes + `BI_THEME_BRAND` + traditional page template names.

## Required for Companion + fleet to work

Activated by `docker/init/install-plugins.sh` + `fast-activate.sh`:

1. **Hello Elementor** (parent — `Template: hello-elementor`)
2. **Elementor** (+ Pro if licensed)
3. **NextGenTutors-Companion** (`ngc_*` shortcodes, tutors CPT, forms, dashboards)
4. **nextgen-3d-scroll-manager** (NGT3D motion — do not dual-load GSAP from CDN in production)

Recommended: AI Integration, Plugin Manager, HTML Importer, 3D Filmstrip, Subjects Widget, Automation Hub, Mission Control, BeyondMeasure.

Health check: `inc/tutorfabulous-compat.php` → admin notice if required plugins missing. Companion accepts TutorFabulous via `ngc_is_supported_theme()`.

## Docker

```yaml
# Primary stylesheet (activate this) — traditional default
../NextgenTutors-TutorFabulous → themes/nextgentutors-tutorfabulous
# Legacy alias
../NextGenTutors-BeyondInfinity → themes/nextgentutors-beyondinfinity
```

Root overlays (`assets`, `inc`, …) may still bind onto the primary slug for Docker/Windows path history; prefer keeping TutorFabulous package complete so overlays are optional.

Activate:

```bash
wp theme activate nextgentutors-tutorfabulous
```

Local URL: **http://localhost:8890** (default `WP_PORT`).

## Shortcodes (pages-registry)

| Page | Shortcodes |
|---|---|
| Find a Tutor | `[ngc_tutor_marketplace]` |
| Parent Checkout | `[ngc_parent_checkout]` |
| Contact / Become a Tutor / Onboarding | form shortcodes per registry |
| Dashboards | Companion dashboard shortcodes |

Fallback stubs live in `inc/shortcodes-fallback.php` when Companion is inactive.
