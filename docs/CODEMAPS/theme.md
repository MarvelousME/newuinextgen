# Theme / Presentation Codemap

**Last Updated:** 2026-09-16  
**Entry Points:**
- `NextgenTutors-TutorFabulous/functions.php` (`BI_VERSION` 2.1.1)
- Docker theme slug: `nextgentutors-tutorfabulous` (legacy alias `nextgentutors-beyondinfinity`)
- Policy: `THEME-TUTORFABULOUS.md`

## Architecture

```
Browser
   │
   ▼
 TutorFabulous theme (edit / package root)
   ├─ page templates / kinetic home
   ├─ UI Library partials (template-parts/ui-library/)
   ├─ Elementor / NextGen widgets (3D scroll, subjects, …)
   └─ [ngc_*] / [bi_*] shortcodes  ──►  Companion (data)
```

**Rule:** Theme renders; Companion owns data. No hardcoded tutor prices/ratings in UI Library partials.

**Identity:** Edit/package root = `NextgenTutors-TutorFabulous/`. `NextGenTutors-BeyondInfinity/` is a **legacy alias** only (same product family; old activations). Text domain stays `beyondinfinity`.

## Key Modules

| Module | Path | Purpose |
|--------|------|---------|
| Bootstrap | `functions.php` + `inc/*` | Theme supports, enqueue, companion bridge |
| Pages registry | `inc/pages-registry.php`, `content/page-map.json` | Launch page set |
| UI Library | `inc/ui-library/`, `template-parts/ui-library/` | Presentation components |
| Kinetic home | `inc/defaults/home.php` + Section CMS | CMS-driven homepage |
| Motion / 3D | NextGen widgets + motion engine docs | Scroll / GSAPify / filmstrip |
| Design tokens | `assets/css/ng-ui-*.css`, `assets/ngt/css/tokens.css` | Brand tokens |

## Data Flow

1. Template calls `ng_ui_component('tutor-card')` or shortcode  
2. Companion provider supplies payload  
3. Theme partial renders escaped markup  
4. Domain mutate never happens in theme PHP tables

## Overlays (Docker)

Repo-root `inc/`, `assets/`, `template-parts/` may still mount onto the primary theme slug at runtime (Docker/Windows path history). Prefer editing **`NextgenTutors-TutorFabulous/`** so the package stays complete and overlays remain optional. Do not treat `NextGenTutors-BeyondInfinity/` as the edit root (legacy alias only).

## External Dependencies

- WordPress 6.x + Hello Elementor child patterns as needed  
- Companion active for live data  
- Optional: Elementor, WooCommerce (via Plugin-Manager)

## Related Areas

- [platform.md](platform.md)  
- [companion-domain.md](companion-domain.md)  
- `ARCHITECTURE.md` (repo root SOLID contract)  
- `docs/ELEMENTOR-*.md` (widget / design system)  
