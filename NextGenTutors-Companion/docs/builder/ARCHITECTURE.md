# NGT Visual Builder — Architecture

Companion module that edits BeyondInfinity without replacing it.

## Boundary

| Product | Role |
|---------|------|
| Automation Studio | Workflow orchestration |
| **Visual Builder** | Page / template design documents |
| Section CMS | Section **content** blobs (`contentRef`) |
| UI Library | Component catalog |

## Storage

- `wp_ngc_builder_documents` — draft/published document JSON
- `wp_ngc_builder_revisions` — publish snapshots
- Option `ngc_builder_tokens` — design token overlay
- Schema: `includes/builder/schema/document.schema.json`

## REST (`ngc/v1/builder`)

Documents CRUD, publish, revisions, tokens, catalogs (sections/components/node-types/interactions/dynamics), assets, compile, migrate, chrome, host.

## Theme contract

Filter `ngc_builder_host` → object implementing `NGC_Builder_Host`.  
BeyondInfinity: `inc/builder-host.php` (`BI_Builder_Host_Adapter`).

## Admin

NEXT GEN TUTORS → **Visual Builder** (`ngc-visual-builder`).  
Mount `#ngc-builder-root` + `assets/builder/builder-fallback.js` (no-build SPA).

## Phases landed in code

0. Schema, tables, host, REST, migrator, shell  
1. Section canvas, tokens, publish/renderer  
2. Layout props, breakpoints, undo/redo, duplicate  
3. Effects / typography style fields + compiler CSS map  
4. Interactions catalog + chrome document kinds  
5. Dynamics (ACF/Meta Box/Woo/query) + asset manager API/UI  
