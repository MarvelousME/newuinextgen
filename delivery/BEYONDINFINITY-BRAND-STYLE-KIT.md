# BeyondInfinity Brand Style Kit

## Architecture

```text
BeyondInfinity Brand System
        │
        ▼
Semantic Design Tokens (brand-semantic.css + unified.css + skins)
        │
        ├── CSS Custom Properties (:root / html[data-bi-skin])
        ├── theme.json (Gutenberg presets)
        ├── Elementor Global Styles (sync API)
        ├── WPBakery adapters (integrations/wpbakery.css)
        └── Native components (.ngt-btn, .ngt-card, .ngt-form)
                 │
                 ▼
        ALL FRONT-END EXPERIENCES
```

## Token load order

1. `tokens/base.css` — structural (priority 5)
2. `skins/{preset}.css` — semantic colours/fonts
3. `style.css` — component rules (no duplicate tokens)
4. `tokens/unified.css` — alias bridges (inlined, priority 60)
5. `tokens/brand-semantic.css` — directive vocabulary (`--ngt-color-brand-*`)
6. Dynamic scheme CSS — Customizer overrides
7. `integrations/*.css` — builder adapters
8. `accessibility.css` — WCAG helpers + reduced motion

## Brand character

Intelligent · Trusted · Educational · Human · Modern · Energetic · Premium · Safe · Approachable

**Default palette (Beyond Infinity skin):**

| Role | Value |
|------|-------|
| Primary (navy) | `#07172f` |
| Secondary (cyan) | `#28c7f7` |
| Accent (gold) | `#ffb703` |
| Page BG | `#f5f8ff` |
| Text | `#10213f` |

Fonts: **Sora** (display), **Inter** (body).

## Component contract

Use semantic classes — not builder element IDs:

```text
.ngt-button / .ngt-btn--primary
.ngt-card / .ngt-card--tutor
.ngt-badge
.ngt-form-control / .ngt-form-group
.ngt-section / .ngt-container
```

## Admin surfaces

| Surface | Location |
|---------|----------|
| Brand Style Kit preview | **Appearance → Brand Style Kit** |
| Colour schemes | Customizer → Brand & Colors |
| Visual presets / skins | Customizer + floating theme switcher |
| Elementor sync | Brand Style Kit → Sync button |

## Dark mode

Supported via `html[data-bi-scheme="dark"]` token overrides in `unified.css`. **Not enabled by default** — verify plugin/checkout readability before marketing dark mode.
