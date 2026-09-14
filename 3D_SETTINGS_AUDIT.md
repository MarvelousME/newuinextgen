# 3D Settings Audit

**Plugin:** `nextgen-3d-scroll-manager` v1.1.0  
**Option:** `ngt_3d_settings`  
**Status:** COMPLETE WITH LIMITATIONS

## Global settings (Runtime_Config)

| Key | Default | Runtime effect | Admin UI | Trace |
|-----|---------|----------------|----------|-------|
| `engine_enabled` | `true` | Kill switch: `Page_Resolver::can_load_engine()`; empty rules in `Runtime_Config::build()` | **Not exposed** on Settings form (save defaults to true if absent) | settings → resolver + localize |
| `debug_mode` | `false` | `NGT3D.debug` → runtime diagnostics console | Settings checkbox | settings → localize → runtime |
| `lenis_enabled` | `false` | `NGT3D.lenis` + `lenisOptions` | Settings checkbox | settings → localize |
| `lenis_duration` | `1.2` | Lenis options when enabled | Settings number | settings → localize |
| `lenis_easing` | `ease` | Lenis options when enabled | Settings text | settings → localize |
| `default_perspective` | `1200` | `NGT3D.perspective` (clamped 400–3000) | Settings number | settings → localize → presets using perspective |
| `fps_safeguard` | `true` | Stored; intended FPS auto-reduce | Settings checkbox | settings (runtime use partial) |
| `fps_threshold` | `30` | Stored with safeguard | Settings number | settings (runtime use partial) |

Persistence: `NGT3D_Runtime_Config::save_settings()` via `admin_post_ngt3d_save_settings` and REST `POST …/settings`. Cache flush on save.

## Per-rule settings

| Field | Storage | Runtime key | Admin UI | Trace |
|-------|---------|-------------|----------|-------|
| `page_id` / `page_slug` | DB | page filter | Edit form | DB → get_for_page → serialize |
| `target_selector` | DB | `selector` | Edit form | DB → runtime querySelector |
| `target_type` | DB | `targetType` | Edit form | DB → runtime |
| `animation_names` | DB (CSV) | `animations[]` | Text + browse list | DB → loader deps/CSS + runtime apply |
| `style_classes` | DB | `styleClasses[]` | Edit form | DB → runtime classList |
| `animation_options` | DB JSON | `options` | **Shared JSON textarea** (not schema-driven fields) | DB → preset `opt()` |
| `sort_order` | DB | order of apply | Edit form | DB |
| `enabled` | DB | only enabled rows loaded | Checkbox | DB → get_for_page |
| `desktop_mode` / `tablet_mode` / `mobile_mode` | DB | deviceMode() | Selects | DB → runtime mode gate |

## Registry options (preset defaults)

Each READY showcase exposes typed `options` in `NGT3D_Animation_Registry` (e.g. `scrub`, `pin`, `scaleFrom`, `direction`, `stagger`). Admin edit does **not** dynamically show/hide fields from that schema — operators edit JSON. Registry options are available to admin JS via `NGT3D_Admin.registry` localize.

## Settings traceability matrix

| Intent | Control surface | Code path | Visible outcome | Proven this pass? |
|--------|-----------------|-----------|-----------------|-------------------|
| Global off | `engine_enabled` | Page_Resolver + Runtime_Config | No NGT3D assets / empty rules | Partial (code path present; admin UI gap) |
| Debug markers | `debug_mode` | localize → runtime.diagnostics | Console `[NGT3D]` | Partial (payload present) |
| Smooth scroll | `lenis_*` | localize lenis flags | Lenis when available | Partial |
| Default FOV | `default_perspective` | `NGT3D.perspective` | Preset perspective fallback | Partial |
| Section effect | rule `animation_names` + options | serialize_rules → preset.apply | Motion on section | Config + runtime present |
| Device degrade | rule `*_mode` | `deviceMode(rule)` | full/reduced/disabled by width | Code present |
| Homepage map | migration option `ngt_3d_showcase_home_map_v1` | `migrate_showcase_home_map()` | Seeded home rules | Yes (flag `2026-09-11`) |
| Kill stub presets | registry status | stub JS markUnavailable/disabled | No invented motion | Yes |

## Gaps / limitations

- Admin schema-driven dynamic field show/hide is **partial** (registry `options` exist; edit form still shared JSON).
- Settings validation chain partially proven (config payload + runtime present); full admin→visual change matrix needs live admin edits.
- `engine_enabled` kill switch exists in code but Settings screen does not currently render a checkbox for it.
