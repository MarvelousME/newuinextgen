# HOME 3D Implementation Plan

**Plugin:** `nextgen-3d-scroll-manager` v1.1.0  
**Status:** COMPLETE WITH LIMITATIONS (plan executed 2026-09-11)

## Goals

1. Ship missing frontend runtime for existing PHP/admin/REST surface.
2. Register Framer showcase presets from live evidence only.
3. Map a coherent homepage subset to real DOM section IDs.
4. Preserve theme motion stack; reuse GSAP handles.
5. Stub unavailable/changed showcases without inventing behavior.

## Phases (done)

| Phase | Work | Outcome |
|-------|------|---------|
| A — Discover | Probe showcases; inventory home section IDs; confirm runtime gap | Live status matrix; DOM ID list |
| B — Design | Coherent home map (not all nine); stub policy for 404/changed | Map in `HOME_3D_MAP.md` |
| C — Implement | Runtime + compat + engine CSS + preset assets; registry entries; asset loader preset CSS; migration | Plugin 1.1.0 |
| D — Gate | Kill switch; reduced-motion / device modes; no swag-card/transforms on home | Migration flag set |
| E — Evidence | Docs pack; settings audit; limitations recorded | This doc set |

## Homepage assignment rules used

- One primary showcase language per mapped section.
- Prefer sections with media/story/path content for pin/mask/wipe presets.
- Keep tutors/CTA on proven legacy presets.
- Leave trust/stats/pricing/faq/etc. to theme motion to avoid effect density.

## Non-goals (enforced)

- Do not clone DARK VELES content — motion grammar only.
- Do not scrub `video.currentTime` for 4kvideo.
- Do not invent transforms/swag-card behavior.
- Do not disable bi-3d / kinetic-home.

## Residual work

- Live admin edit → visual change matrix.
- Full viewport visual regression capture.
- Admin UI for `engine_enabled` + schema-driven option fields.
