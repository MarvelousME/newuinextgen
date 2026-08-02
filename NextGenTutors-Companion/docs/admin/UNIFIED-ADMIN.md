# NEXT GEN TUTORS v1.0 — Enterprise Administration Platform

## Identity

All administration chrome uses **NEXT GEN TUTORS v1.0** from `NGC_Platform_Version`:

- `product_name()` → `NEXT GEN TUTORS`
- `marketing_version()` → `1.0` (filter: `ngt_platform_marketing_version`)
- `display_title()` → `NEXT GEN TUTORS v1.0`
- `bundle()` → companion / theme / mission-control package versions
- REST: `GET /ngc/v1/admin/version`

## Architecture

| Component | Class | Role |
|-----------|-------|------|
| Shell | `NGC_Admin_Shell` | Parent menu, assets, admin bar |
| Version | `NGC_Platform_Version` | Branding + version provider |
| Registry | `NGC_Admin_Registry` | Modules/screens (`nav_parent`, placeholders) |
| Catalog | `NGC_Admin_Catalog` | Capability IA + Education placeholders |
| Nav tree / layout / UI | `NGC_Admin_Nav_*` | Nested sidebar + DnD persistence |
| Theme | `NGC_Admin_Theme` | Design tokens + Theme Designer |
| Layout | `NGC_Admin_Layout` | Page chrome `render_page()` |
| Notifications | `NGC_Admin_Notifications` | Floating Notification Centre |
| Prefs | `NGC_Admin_Prefs` | Density, motion, landing, recent |
| Components | `NGC_Admin_Components` | Live admin component library |
| Entities | `NGC_Admin_Entity_Registry` | Metadata models |
| Grid / CRUD / Export | `NGC_Admin_Grid`, `NGC_Admin_Crud`, `NGC_Admin_Export` | Shared entity kit |
| REST | `NGC_Rest_Admin_Shell` | `/ngc/v1/admin/*` |

Location: `NextGenTutors-Companion/includes/admin/framework/`

## Extension APIs

```php
add_action( 'ngt_admin_register', function () {
    ngt_admin_register_module( [ 'slug' => 'my-module', 'label' => 'My Module', 'category' => 'operations' ] );
    ngt_admin_register_screen( [
        'slug'     => 'ngc-my-screen',
        'title'    => 'My Screen',
        'module'   => 'my-module',
        'category' => 'operations',
        'callback' => [ 'My_Admin', 'render' ],
    ] );
} );

add_action( 'ngt_admin_register_entities', function () {
    ngt_admin_register_entity( [
        'key'           => 'my_entity',
        'label'         => 'My Entity',
        'capability'    => 'manage_options',
        'columns'       => [ [ 'key' => 'id', 'label' => 'ID' ] ],
        'fields'        => [ [ 'key' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true ] ],
        'list_callback' => 'my_list_fn',
        'get_callback'  => 'my_get_fn',
        'update_callback'=> 'my_update_fn',
        'export_key'    => 'my_entity',
    ] );
} );
```

**Do not** call `add_menu_page()` for NextGen features. Prefer `NGC_Admin_Layout::render_page()` + `NGC_Admin_Grid::render( $entity_key )`.

## Capability categories

Platform · Education · Operations · Commerce · CRM · AI Platform · Website · Reporting · Development · Administration

## Phase 2 pilots

| Screen | Entity key |
|--------|------------|
| Tutor Applications | `applications` |
| Matches | `matches` |
| Safeguarding | `safeguarding_cases` |

Grid toolbar: search, columns, export (CSV/JSON/Excel/PDF), detail CRUD panel.

## Phase 3 backlog

- Full Education product CRUD (Attendance, Assessments, Certificates)
- Password-protected branded PDF templates
- True Excel streaming for 100k+ rows
- Role layout UI (storage hooks exist)
- Replace WordPress `#adminmenu` entirely
- Multi-tenant / network-wide admin

## Related

- ADR: `documentation/adr/ADR-004-enterprise-admin-platform.md`
- Automation Studio: still under Operations → Automation Studio
