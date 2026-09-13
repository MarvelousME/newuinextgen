# Dependency map

See `../04-VALIDATION/DEPENDENCY-MATRIX.csv`.

```mermaid
flowchart LR
  Hello[Hello Elementor] --> Theme[BeyondInfinity]
  Comp[Companion] --> Theme
  Comp --> PM[Plugin Manager]
  Comp --> MC[Mission Control]
  Comp --> Subj[Subjects Widget]
  Comp --> Film[Filmstrip]
  Theme --> Scroll[3D Scroll Manager]
  Woo[WooCommerce optional] --> Comp
  Amelia[Amelia optional] --> Comp
```

Required runtime: WordPress + Hello Elementor + Companion + BeyondInfinity + visual plugins. Optional: Woo/Amelia/LMS/CRM, AI Integration, BeyondMeasure. Avoid Automation Hub when Companion is active.