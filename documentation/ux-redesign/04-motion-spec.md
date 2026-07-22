# Deliverables 9, 10, 16 — Motion Specification, Page Transitions, Animation Architecture

---

## 1. Motion principles ("Motion means meaning")

1. **One hero motion per viewport.** Multiple simultaneous entrances are noise. The stagger system (motion/07) enforces sequence, not chorus.
2. **Interruptible & fast.** UI feedback ≤200 ms; entrances ≤600 ms; ambient loops only in patterns/marquee components where they are the content.
3. **GPU-only properties.** `transform` + `opacity`. No animated `width/height/top/left` (existing pack already complies; new code must too).
4. **Reduced motion is a first-class theme.** Every animated surface has a defined static end-state. Existing coverage (motion.js early-exit, token zeroing, catalog runtime checks) is the pattern for all new work.

## 2. Timing tokens (in `tokens/unified.css`)

| Token | Value | Use |
|---|---|---|
| `--ngt-duration-fast` | 150ms | hovers, toggles, focus |
| `--ngt-duration-base` | 300ms | reveals, drawers, tabs |
| `--ngt-duration-slow` | 600ms | hero entrances, page transitions |
| `--ngt-ease` | cubic-bezier(.175,.885,.32,1.275) | playful spring (existing) |
| `--ngt-ease-out` | cubic-bezier(0,0,.2,1) | entrances |
| `--ngt-ease-spring` | cubic-bezier(.34,1.56,.64,1) | micro-interactions |

## 3. Motion vocabulary (mapped to existing systems)

| Pattern | System | Spec |
|---|---|---|
| Scroll entrance | `motion.js` `[data-bi-motion]` + motion/02 | fade+8px rise, `--ngt-duration-base`, stagger 60ms |
| Section reveal | motion/03 + view-timeline CSS | mask reveal for heroes only |
| Hover lift | motion/04 | translateY(-2px) + shadow-md→lg, `--ngt-duration-fast` |
| Parallax | motion/05 | ≤24px displacement, disabled on touch + reduced-motion |
| Number counters | ui-library `number-ticker` | eased, 1.2s, once per view |
| Button feedback | ngc-button-processing ripple | keep; ripple ≤450ms |
| Form error | ngc-validation shake | 3 oscillations, 300ms, + `--ngt-error` border |
| Toast | ngt-toast | slide-in-up 300ms, auto-dismiss 5s, hover pauses |
| Skeleton | new (Phase 3) | shimmer 1.4s linear loop; static gray under reduced-motion |
| AI thinking | AI Suite chat | three-dot pulse, 900ms loop (replaces "…thinking" text) |
| Workflow completion | Studio / thank-you | check-draw stroke animation 600ms, once |

## 4. Interaction states (all components)

Hover → `--ngt-duration-fast`; Active → scale(.98); Focus → `--ngt-focus-ring` (no transition on the ring itself — instant); Disabled → opacity .5, no motion; Loading → button-processing lock (exists).

## 5. Page transition specification

### 5.1 First-load loader (implemented Phase 1 — `inc/loader.php`, `bi-loader.css`, `bi-loader.js`)
- Fixed full-viewport overlay painted before content (no CLS — content layout is untouched beneath it).
- **Rotating branded experiences**, chosen per navigation (random, avoiding immediate repeats via sessionStorage):
  1. **Constellation** — knowledge nodes connecting (canvas, ≤60 points)
  2. **Glass orb** — glassmorphic orb with orbiting ring
  3. **Gradient wave** — brand navy→emerald→amber sweep
  4. **Knowledge nodes** — pulsing node grid (CSS only)
  5. **Logo pulse** — wordmark scale/opacity breathe (CSS only)
- Exit: overlay fades 400ms `--ngt-ease-out` on `window.load` (or 2.5s failsafe), then removed from DOM; `aria-hidden`, `role="status"`, "Loading" for AT.
- **Reduced motion:** static brand mark + fade only.
- Never flashes: shows only if page not already loaded from bfcache; skips on subsequent same-session views (sessionStorage flag) to keep repeat navigation instant.
- Asset preloading: loader waits on `document.fonts.ready` + `load` so content appears settled.

### 5.2 In-site navigation progress
Slim 2px top progress bar (`.bi-loader-bar`) driven by `beforeunload` on same-origin link clicks — perceived responsiveness without SPA rewrite.

### 5.3 Content entrance after loader
`body.bi-loaded` gate: hero motion (mask reveal) begins only after loader exit, so entrances are never hidden under the overlay.

### 5.4 Modal/drawer transitions (Phase 2)
Drawer: translateX 300ms; Modal: scale .96→1 + fade 200ms; both with focus trap (spec: trap on open, return focus on close, Esc closes, backdrop click closes non-destructive dialogs only).

## 6. Animation architecture

```text
Layer 0  tokens/unified.css        durations/easings (single source)
Layer 1  motion/01–09 CSS pack     declarative entrance/hover/parallax
Layer 2  motion.js                 IntersectionObserver orchestration
Layer 3  component JS              catalog-core/interactive, ticker, ripple
Layer 4  bi-loader.js              page-level transitions
Vendors  GSAP/Three (lazy loader)  ONLY when a component actually tweens —
                                   audit found zero GSAP usage in interactive
                                   bundle → vendor fetch removed in Phase 5
```

Budget: ≤2 concurrent RAF loops per page; canvas components pause when off-viewport (IntersectionObserver) and under reduced-motion (already implemented in catalog-interactive.js — this is the mandated pattern).
