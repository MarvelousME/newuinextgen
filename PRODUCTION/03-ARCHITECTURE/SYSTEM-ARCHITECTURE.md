# System architecture

Release train 2026.09.12. Live code roots: BeyondInfinity theme 1.9.29, Companion 1.9.19.

```mermaid
flowchart TB
  WP[WordPress]
  Hello[Hello Elementor]
  Theme[BeyondInfinity presentation]
  Comp[Companion domain]
  Support[PluginManager MissionControl Subjects Filmstrip Scroll3D]
  WP --> Hello --> Theme
  WP --> Comp
  WP --> Support
  Theme -->|"shortcodes REST providers"| Comp
  Comp -->|"CPTs wp_ngc tables ngc/v1"| Domain[(Tutors Bookings Payments CRM LMS AI)]
```

## Ownership

| Concern | Owner |
|---|---|
| Templates, header/footer, kinetic, GSAP chrome | Theme |
| CPT `tutors`, `testimonials`, `resources` | Companion |
| `wp_ngc_*` tables | Companion |
| REST `ngc/v1` (+ alias `ngt/v1`) | Companion |
| REST `ngt3d/v1` | 3D Scroll Manager |
| REST `bi/v1` OpenWA | Theme (this release) |
| Roles/caps | Companion `NGC_Roles` (theme stubs if Companion absent) |

Narrow contracts: shortcodes `ngc_*`, REST `ngc/v1`, UI providers, `bi_companion_active()`.