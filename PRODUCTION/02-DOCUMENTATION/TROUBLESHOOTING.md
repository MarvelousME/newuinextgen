# Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| “The theme is missing the style.css stylesheet” | ZIP used Compress-Archive / backslash names / data-descriptor flag | Use packages from this PRODUCTION tree (Python zip writer) |
| White screen after theme activate | Parent Hello Elementor missing | Install Hello Elementor first |
| Empty dashboards | Companion inactive; theme fallbacks are shells | Activate Companion; confirm `NGC_Plugin` exists |
| Duplicate GSAP in Network | Old vendor loader hitting jsDelivr while theme loads cdnjs | This release registers `bi-ngt-gsap` once; vendor loader reuses `window.gsap` |
| Prototype SQL mentioning `ngt_earnings` | Stale labels in `prototypes/*-body.php` | Live tables are `wp_ngc_*`. Do not flatten prototypes |
| Magic UI missing | `wp-content/ngt-ui-library` absent | Theme-bundled `ui-library/` or drop-in zip |
| Hub + Companion both active | Overlapping CPTs/REST | Deactivate Automation Hub |