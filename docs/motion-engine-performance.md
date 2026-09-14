# Motion Engine Performance

## Cost classes

| Cost | Examples | Guidance |
|------|----------|----------|
| LOW | opacity, x/y, scale, rotation | Default |
| MEDIUM | blur, magnetic, modest SplitText | Prefer viewport-once |
| HIGH | heavy blur+chars, many ST scrubbers | Warn in diagnostics |
| VERY HIGH | canvas sequences, particles unbounded | Not shipped without pools |

## Rules

- Prefer `x/y/scale/rotation/opacity` over layout properties.
- Do not set permanent `will-change` site-wide.
- Use `gsap.context()` for cleanup (runtime already).
- Batch similar viewport cards when possible (`stagger-children`).
- Disable pointer/magnetic on `(pointer: coarse)`.
- Honor `prefers-reduced-motion`.
- Lazy-load showcase presets; Club plugins only when required and present.

## Motion density

Admin diagnostics should warn when many above-the-fold animated targets accumulate (heuristic count — not a fake FPS meter).
