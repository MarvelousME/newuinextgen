# Motion Engine Testing

## Commands executed (this program)

| Check | Command / method | Result |
|-------|------------------|--------|
| Live GSAPify general | Playwright Chromium → gsapify.com/gsap-animations/ | HTTP 200; titles harvested |
| Live GSAPify text | Playwright → gsap-text-animations | HTTP 200; titles harvested |
| Forensic click pass | Playwright click-per-card | Mostly unable (nav/paywall); page-level GSAP plugins recorded |
| Backup | `backups/20260912-022148-motion-engine/` | Created before edits |
| PHP syntax | pending docker `php -l` | see report update |
| Lab regression | `/3d-scroll-test/` asset 200 checks | run after deploy |

## Required scenarios (definition of done progress)

| Scenario | Status |
|----------|--------|
| A Hero 3D + text + parallax + magnetic | Architecture ready; stack format supported — wire on home-3d as follow-up |
| B Tutor cards stagger + tilt | Existing presets + new stagger/magnetic |
| C Pin scrub story | Existing showcase presets |
| D SVG draw/morph | Disabled until Club plugins present (honest) |
| E Existing 3D rules unchanged | Legacy `animation_names` path preserved |

## JS unit targets (next)

- TransformComposer claim/conflict
- PluginManager missing plugins
- Effect stack ordering
- Reduced-motion short-circuit
