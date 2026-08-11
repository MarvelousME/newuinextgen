# DEPENDENCY-MATRIX

| From | To | Allowed mode | Forbidden |
|------|-----|--------------|-----------|
| beyondinfinity | companion | shortcodes, ngc/v1 REST | require Companion includes; write ngc_* |
| companion | ai-integration | ai.transport.dispatch | own AI model runtime in AI-Integration |
| ai-integration | companion | read projections via REST | mutate Companion domain tables |
| plugin-manager | companion | none (WP-CLI/fs) | import NGC_* classes |
| html-importer | companion | none | write ngc_* |
| companion | wordpress/woocommerce | platform APIs | — |

Rules file: `architecture/dependency-rules/edges.json`.  
Generated graph: `architecture/reports/dependency-graph.json` (via `rad-platform/cli/graph.mjs`).
