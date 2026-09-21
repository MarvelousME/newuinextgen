# Tapotik-inspired brand kit (Control Center)

Original derived visual system applied to Ecosystem Control Center. Not a verbatim clone of commercial template CSS/JS.

## Token source

`ecosystem-platform/packages/nextgen-ui/tokens.css`

## Surfaces

| Variant | Class / usage |
| --- | --- |
| GlassNeutral | `.brand-glass`, `.glass-neutral`, default `.cc-panel` |
| GlassIndigo | `.glass-indigo`, purple/indigo panels |
| GlassCyan | `.glass-cyan`, blue panels / rail |
| GlassCritical | `.glass-critical` |

## Kinetic behaviors

- Spotlight pointer tracking on `.cc-card` / `.cc-subsystem-card`
- Constrained 3D tilt (`±4°` / `±6°`) on `[data-kinetic="tilt"]` subsystem cards
- Aurora background layers + slow drift (respects `prefers-reduced-motion`)
- Command palette: `Ctrl/Cmd + K`

## Buttons

- Primary: violet → indigo gradient (`.cc-btn-primary`)
- Secondary: glass border (`.cc-btn-secondary`)
- Ghost: muted (`.cc-btn-ghost`)
- Danger: red translucent (`.cc-btn-danger`)
