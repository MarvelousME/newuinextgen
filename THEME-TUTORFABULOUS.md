# NextgenTutors-TutorFabulous

WordPress child theme brand for NextGen Tutors (Hello Elementor parent).

## Canonical edit root (ONE tree)

| Rule | Value |
|---|---|
| **Edit / package root** | `NextGenTutors-BeyondInfinity/` |
| **Do not treat as edit root** | Monorepo workspace root (`newuinextgen/`) theme-shaped folders |
| **Brand (Theme Name)** | TutorFabulous — stays |
| **Text Domain** | `beyondinfinity` — stays (do not rename) |

**Ship and develop from `NextGenTutors-BeyondInfinity/` only.** Root-level `inc/`, `assets/`, `template-parts/`, etc. exist for Docker bind-mount history; they are not a second product tree. Prefer collapsing work into BeyondInfinity and keeping the legacy stylesheet slug mount (below). Do not invent a parallel “TutorFabulous package” edit root.

Identity reference: this file. Docker mounts: `docker/docker-compose.yml`.

## Identity

| Field | Value |
|---|---|
| **Theme Name** | `NextgenTutors-TutorFabulous` |
| **Stylesheet slug (Docker primary)** | `nextgentutors-tutorfabulous` |
| **Legacy slug (alias mount)** | `nextgentutors-beyondinfinity` (same package) |
| **Text Domain** | `beyondinfinity` (**do not change** — Companion / bi_el_* strings depend on it) |
| **PHP APIs** | `bi_*`, `ngt_*`, Companion `ngc_*` shortcodes |
| **Version** | 2.0.0 |

## Why Text Domain stayed `beyondinfinity`

Companion, Elementor design-system widgets, and hundreds of `__()` calls use `beyondinfinity`. Renaming the text domain would break translations and any plugin checks. Branding is the **Theme Name** + body classes + `BI_THEME_BRAND`.

## Required for Companion + fleet to work

Activated by `docker/init/install-plugins.sh` + `fast-activate.sh`:

1. **Hello Elementor** (parent — `Template: hello-elementor`)
2. **Elementor** (+ Pro if licensed)
3. **NextGenTutors-Companion** (`ngc_*` shortcodes, tutors CPT, forms, dashboards)
4. **nextgen-3d-scroll-manager** (NGT3D motion — do not dual-load GSAP from CDN in production)

Recommended: AI Integration, Plugin Manager, HTML Importer, 3D Filmstrip, Subjects Widget, Automation Hub, Mission Control, BeyondMeasure.

Health check: `inc/tutorfabulous-compat.php` → admin notice if required plugins missing.

## Docker

Package is mounted twice (same files on disk):

```yaml
# Primary stylesheet (activate this)
../NextGenTutors-BeyondInfinity → themes/nextgentutors-tutorfabulous
# Legacy alias (old activations / docs that still say beyondinfinity)
../NextGenTutors-BeyondInfinity → themes/nextgentutors-beyondinfinity
```

### Live overlays (workspace root → Tutofabulous tree)

Compose also bind-mounts monorepo-root dirs **onto** the primary theme:

`assets`, `inc`, `templates`, `template-parts`, `page-templates`, `prototypes`, `content`, `automations`, `tests`

| Fact | Detail |
|------|--------|
| **Why** | Linux containers do not follow Windows junctions inside `NextGenTutors-BeyondInfinity/` |
| **Who wins at runtime** | **Root overlays win** for those paths on `nextgentutors-tutorfabulous` (later volume mounts override the package tree) |
| **Legacy alias** | `nextgentutors-beyondinfinity` gets the package mount only — **no** root overlay binds |
| **Policy** | Treat BeyondInfinity as source of truth for packaging; keep overlays only until collapsed. Prefer sync/collapse into BeyondInfinity + keep legacy slug mount — avoid large moves unless trivial/safe |

Activate:

```bash
wp theme activate nextgentutors-tutorfabulous
```

Local URL: **http://localhost:8890** (default `WP_PORT`).

## Shortcodes (pages-registry)

| Page | Shortcodes |
|---|---|
| find-a-tutor | `ngc_find_tutor_form`, `ngc_tutor_marketplace` |
| become-a-tutor | `ngc_become_tutor_form` |
| contact / support | `ngc_contact_support_form` |
| login | `ngc_login_form`, `ngc_forgot_password_form` |
| register | `ngc_parent_register_child_form`, `ngc_student_register_form` |
| parent-checkout | `ngc_parent_checkout` |
| dashboards | `ngc_*_dashboard` |

Fallbacks: `inc/shortcodes-fallback.php` only when Companion is inactive.

## Content

Canonical page bodies remain in `template-parts/pages/{slug}.php` under the theme tree (edit via BeyondInfinity / overlay policy above). Finished HTML previews merge wireframe section order + these bodies + AI Studio UI — **additive**.
