# Installation guide

## Prerequisites

- WordPress 6.2+ recommended (6.0 minimum for Companion)
- PHP 8.0+ (8.2 recommended)
- MySQL 8 / MariaDB 10.6+
- Parent theme **Hello Elementor** (bundled as `Hello-Elementor-v*.zip`)
- `upload_max_filesize` / `post_max_size` ≥ 64M for theme ZIP

## WordPress Upload path (customer)

1. **Appearance → Themes → Add New → Upload** `Hello-Elementor-v*.zip` → Activate.
2. **Plugins → Add New → Upload** `NextGenTutors-Companion-v*.zip` → Activate first.
3. Upload remaining required plugins (Plugin Manager, Mission Control, 3D Scroll Manager, 3D Filmstrip, Subjects Widget). Activate Companion **before** the theme.
4. Upload Html Importer only if you are migrating static HTML; deactivate after import.
5. **Appearance → Themes → Upload** `NextGenTutors-BeyondInfinity-v1.9.29.zip` → Activate.
6. If Magic UI catalog is missing, extract `drop-ins/ngt-ui-library.zip` to `wp-content/ngt-ui-library`.

Activation must produce no fatals. Companion creates `wp_ngc_*` tables and registers CPT `tutors`. The theme must not crash if Companion is later deactivated — forms fall back to theme shortcode shells.

## Clean-room Docker (engineering)

From `docker/`:

```powershell
.\clean-install.ps1
```

This compose file bind-mounts **only the PRODUCTION ZIPs**, not the monorepo. That is the acceptance path. The developer stack (`start.ps1`) mounts source and is **not** a production proof.

## After activation

- Mission Control / Companion provisioning creates required pages from [`inc/pages-registry.php`](../../inc/pages-registry.php).
- Menu **NextGen Primary Grouped** is synced by [`inc/nav-menu.php`](../../inc/nav-menu.php).
- Roles `parent`, `parent_guardian`, `tutor`, `student` are installed by Companion `NGC_Roles` (theme also registers read-only stubs if Companion is absent).

Do not install **nextgen-automation-hub** alongside Companion unless you have a documented reason. Hub registers overlapping CPTs and REST.