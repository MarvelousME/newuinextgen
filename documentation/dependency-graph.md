# Dependency Graph — Stage 1

**Source:** `audit-reports/dependency-graph.json` (+ `.dot`)  
**Method:** static regex over PHP/JS/CSS in the four core projects + agntix-child  
**Status:** PARTIAL

## Stats (VERIFIED)

| Metric | Value |
|--------|------:|
| Nodes | 475 |
| Edges | 582 |
| PHP files analysed | see `php-analysis.json` |
| JS with GSAP markers | 15 |
| JS with Three.js markers | 0 in scoped scan |
| PHP dynamic-load risk | 39 |

## Limitations (VERIFIED)

- Concatenated / variable includes are captured as expressions, not resolved absolute paths.
- Composer and npm dependency trees are not expanded.
- Elementor / WPBakery **post meta** asset references are **not** scanned yet → those assets remain **UNSAFE TO REMOVE**.
- Runtime-only enqueues without string handles may be missed.

## How to regenerate

```powershell
powershell -ExecutionPolicy Bypass -File scripts/inventory-solution.ps1
powershell -ExecutionPolicy Bypass -File scripts/dependency-graph.ps1
```

## SVG

`dependency-graph.svg` is **NOT VERIFIED** until Graphviz (or equivalent) is run against `dependency-graph.dot` in CI.
