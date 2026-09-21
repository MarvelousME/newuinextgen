# Motion Engine — Progress Report (2026-09-12)

## 1. Executive summary

The **3D Scroll Manager** is being evolved in-place into a **Unified Motion Engine** (plugin v1.2.0).  
Live GSAPify catalogues were extracted with **real Chromium** (HTTP 200) after non-browser requests returned 403.  
Foundation layers shipped: plugin detection, transform ownership, motion primitives, effect-stack runtime, source-tagged PHP catalogue, and required docs.

**Definition of Done (full admin visual composer + 100% GSAPify recipes) is NOT complete.** Remaining work is explicit below — no fake “complete” claim.

## 2. Architecture discovered

See `docs/motion-engine-architecture.md` and archaeology from `nextgen-3d-scroll-manager` (rules table → REST/admin → `window.NGT3D` → runtime + showcase presets). Shared GSAP handles: `bi-ngt-gsap` / `bi-ngt-scrolltrigger` @ 3.12.5.

## 3–4. GSAPify catalogues

| Catalogue | Live URL | Harvested names | Deep click forensics |
|-----------|----------|----------------:|----------------------|
| General | https://gsapify.com/gsap-animations/ | **112** (filtered card titles) | Mostly unable (nav/paywall); page GSAP 3.15.0 + plugins observed |
| Text | https://gsapify.com/gsap-text-animations/#text-animation-collection | **91** | Same limitation |

Artifacts: `docs/gsapify-animation-catalogue.md`, `docs/gsapify-text-animation-catalogue.md`, `docs/gsapify-live-extract.json`, `docs/gsapify-verified-names.json`, `docs/gsapify-forensic-pass.json`.

## 5. GSAP official capabilities used

Core + ScrollTrigger (loaded). Club plugins **detected only** (SplitText, MorphSVG, DrawSVG, etc.) — demos on GSAPify used them; our site disables those presets until files exist (no fake MorphSVG).

## 6–13. Unified architecture progress

| Layer | Status |
|-------|--------|
| Effect stack (`options.effects[]`) | **Shipped** in runtime |
| TransformComposer / ownership | **Shipped** |
| PluginManager | **Shipped** |
| Motion primitives + 34 GSAPify-mapped presets | **Shipped** |
| PHP `NGT3D_Motion_Catalogue` source tags | **Shipped** |
| Visual picker / timeline editor / Flip builder | **Not started** |
| Full admin effect browser UX | **Not started** |
| Page scanner upgrade | Existing inspector only |

## 14–17. A11y / responsive / perf / security

Reduced-motion + coarse-pointer magnetic disable in primitives. Legacy security (capability, nonce, selector sanitize) unchanged. Performance doc: `docs/motion-engine-performance.md`.

## 18. Migration

Legacy `animation_names` path preserved (zero-regression intent). New stack is additive schema v2.

## 19–21. Files

**Backup:** `backups/20260912-022148-motion-engine/` + `MANIFEST.md`

**Created (key):**  
- `assets/js/ngt-3d-plugin-manager.js`  
- `assets/js/ngt-3d-transform-composer.js`  
- `assets/js/ngt-3d-motion-primitives.js`  
- `includes/class-ngt3d-motion-catalogue.php`  
- `docs/motion-engine-*.md`, `docs/gsapify-*.md`  
- `scripts/gsapify-extract/*`

**Modified (key):** runtime effect stack, asset loader enqueue chain, plugin bootstrap v1.2.0.

## 22–24. Tests

| Test | Result |
|------|--------|
| Playwright GSAPify extract | Pass (200) |
| PHP lint catalogue + bootstrap | Pass |
| Asset HTTP 200 (primitives/composer/plugins) | Pass |
| Lab page 200 | Pass |
| Full E2E scenario A–E matrix | **Not run** |

## 25. Remaining issues (blocking full DoD)

1. Map remaining **~169** harvested GSAPify names to recipes  
2. Improve forensic inspector (avoid nav clicks; paywall handling)  
3. Admin: effect browser, stack UI, preview sandbox, visual picker  
4. Official SplitText/ScrambleText when licensed assets available  
5. Wire combination Scenario A on `/home-3d/`  
6. Automated PHP/JS/E2E suites for stack + composer  

## 26. Catalogue counts (honest)

| Metric | Count |
|--------|------:|
| GSAPify general effects discovered (live names) | **112** |
| GSAPify text effects discovered (live names) | **91** |
| GSAPify verified presets **implemented** (mapped) | **34** |
| GSAP-official entrance/stagger presets implemented | **15** |
| System-enhancement presets implemented (new + existing 3D) | see registry `NGT3D_Motion_Catalogue::counts()` at runtime |
| Total presets available after merge | legacy registry ∪ new catalogue (runtime) |

## Next recommended step

Continue Phase 22–24: admin Effect Browser + Effect Stack editor bound to `options.effects[]`, then batch-map remaining GSAPify names via primitive composition.
