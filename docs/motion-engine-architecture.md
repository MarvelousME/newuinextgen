# Motion Engine Architecture

**Status:** DESIGN (Phases 1–7 complete for discovery; implementation follows Phase 8 backup)  
**Date:** 2026-09-12  
**Foundation:** `nextgen-3d-scroll-manager` v1.1.0 — extend in place, do not fork a competing plugin.

## 1. Discovered architecture (as-is)

```text
Admin UI / REST (ngt3d/v1)
        ↓
Validator → Rule_Repository → {prefix}ngt_3d_scroll_rules
        ↓
Asset_Loader ← Page_Resolver + Dependency_Resolver + Animation_Registry
        ↓
window.NGT3D { rules, settings }
        ↓
ngt-3d-compat → ngt-3d-runtime (+ lazy showcase presets)
        ↓
GSAP 3.12.5 + ScrollTrigger → DOM
```

Parallel theme stacks (not yet unified): `bi-3d.js`, kinetic-home IO, NGT `chrome.js` Lenis.

## 2. Target architecture (unified Motion Manager)

```text
WORDPRESS
   ├── Motion Manager Admin (extends 3D Scrolling menu)
   │      ├── Page Scanner / Visual Picker
   │      ├── Effect Browser (source-tagged catalogue)
   │      ├── Effect Stack editor
   │      ├── Preview sandbox
   │      └── Diagnostics / conflict detector
   ├── Configuration (schemaVersion ≥ 2, backward-compatible v1 rules)
   ├── MotionEffectRegistry (primitive + preset + composition)
   ├── TargetResolver + durable selectors
   ├── TriggerFactory
   ├── TimelineFactory
   ├── TransformComposer (property ownership / layers)
   ├── PluginManager (detect + lazy-load GSAP plugins)
   └── Runtime → GSAP + ScrollTrigger (+ optional Club plugins when present)
```

## 3. Compatibility contract (zero regression)

- Existing rows in `ngt_3d_scroll_rules` continue to load unchanged.
- `animation_names` comma-list + shared `animation_options` remains supported as **legacy stack mode**.
- New **effect stack** lives in `animation_options.effects[]` when present; runtime prefers stack, else legacy names.
- Showcase presets (`swag-card`, `transforms`, Framer nine, etc.) keep IDs and apply paths.
- Shared GSAP handles: `bi-ngt-gsap`, `bi-ngt-scrolltrigger` (CDN 3.12.5).

## 4. Source classification (mandatory)

| Tag | Meaning |
|-----|---------|
| `GSAPIFY-VERIFIED` | Observed on live GSAPify via browser extraction |
| `GSAP-OFFICIAL` | Documented current GSAP API / plugin |
| `SYSTEM-ENHANCEMENT` | First-party NextGen tutoring / 3D motion |
| `CUSTOM` | Site-saved / imported |
| `UNVERIFIED` | Named in design taxonomy but not observed live |

Never label guessed GSAPify names as verified.

## 5. Primitive → preset normalization

Avoid N engines for directional variants:

```text
primitive: fade-slide
  direction: up|down|left|right
  opacity: 0→1
  + trigger: viewport|scroll-scrub|hover
  + stagger config
→ presets: Fade Up, Scroll Slide Up, Stagger Fade Left, …
```

## 6. Transform composition / property ownership

Layers (wrappers only when needed):

```text
[data-motion-layer="scroll-3d"]   → scroll / section transforms
  [data-motion-layer="element"]   → element entrance / hover
    [data-motion-layer="text"]    → SplitText / scramble children
```

Runtime metadata per element: owners for `transform`, `opacity`, `filter`, `clipPath`, `scroll`, `pointer`. Conflicts → WARN + isolate when safe.

## 7. Plugin manager policy

| Plugin | Load when | If missing |
|--------|-----------|------------|
| GSAP core + ScrollTrigger | Any motion rule | Hard fail with diagnostics |
| SplitText / ScrambleText / TextPlugin | Text effects | Disable those presets; explain in admin |
| DrawSVG / MorphSVG / MotionPath | SVG presets | Disable; no fake MorphSVG |
| Flip / Draggable / Inertia / Observer | Layout / drag / gesture | Disable |
| Physics2D / CustomEase / Bounce / Wiggle | Physics / custom ease | Disable |
| ScrollSmoother | Explicit page setting | Prefer ScrollTrigger parallax when enough |

Detect; never assume Club plugins exist.

## 8. Phased delivery map

| Phase band | Deliverable |
|------------|-------------|
| 1–7 | Archaeology + live catalogues + design (this doc + catalogues) |
| 8 | Timestamped backups + MANIFEST |
| 9–14 | Schema v2, registry, plugin manager, target resolver, scanner/picker |
| 15–21 | Text + general effects + 3D integration + composition + ST + interactions + SVG/Flip |
| 22–28 | Admin browser, stack UI, preview, responsive, reduced-motion, perf, security |
| 29–33 | Tests, E2E, docs, audit report |

## 9. Gaps closed by this program

| Gap (as-is) | Target |
|-------------|--------|
| One shared options blob for multi-animations | Per-effect stack |
| No SplitText | Text engine when plugin available; CSS/DOM fallback marked SYSTEM if no Club license |
| No visual picker | Admin picker + scan |
| No property ownership | TransformComposer |
| Lenis/FPS settings unused | Wire or remove dishonest UI |
| Theme vs plugin dual motion | Compat bridge + conflict warnings |

## 10. Related documents

- `docs/gsapify-animation-catalogue.md`
- `docs/gsapify-text-animation-catalogue.md`
- `docs/gsapify-effect-mapping.md`
- `docs/motion-engine-settings-schema.md`
- `docs/motion-engine-developer-api.md`
- `docs/motion-engine-testing.md`
- `docs/motion-engine-performance.md`
