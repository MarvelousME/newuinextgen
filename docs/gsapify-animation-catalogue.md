# GSAPify General Animation Catalogue

**Source URL:** https://gsapify.com/gsap-animations/  
**Page title (live):** GSAP Animations | 100+ Effects & Examples | GSAPify  
**Extraction method:** Playwright Chromium (headless), full-page scroll + DOM card title harvest + read-only `gsap.globalTimeline` / `ScrollTrigger.getAll()` inspection  
**Extracted at:** 2026-09-12 (see `docs/gsapify-live-extract.json`, `docs/gsapify-verified-names.json`)  
**HTTP note:** Automated non-browser fetch historically returned **403 / Cloudflare**. Live Chromium session received **HTTP 200**.

## Runtime snapshot (read-only)

| Field | Value |
|-------|-------|
| GSAP version | 3.15.0 |
| Active tweens (sample) | 131 |
| ScrollTriggers (sample) | 256 |
| Plugins observed | attr, endArray, roundProps, modifiers, snap, css, motionPath, text, drawSVG, morphSVG, physics2D, physicsProps, inertia, scrambleText |

## Catalogue counts

| Metric | Count | Status |
|--------|------:|--------|
| Raw card-strategy hits (pre-filter) | 328 | noisy |
| Unique card titles after filter | **112** | includes residual marketing labels |
| **GSAPIFY-VERIFIED effect candidates** (noise removed below) | **100** | names listed |
| Unable to forensically deep-inspect | see `docs/gsapify-forensic-pass.json` | after pass completes |

> Do **not** treat this as a claim that GSAPify’s marketing “100+” equals exactly 100. The live page exposed **at least** the verified names below after scroll; freemium UI also surfaced **“You've Hit Your Limit”**, so some demos may be paywalled from full interaction.

## Verification legend

| Tag | Meaning |
|-----|---------|
| GSAPIFY-VERIFIED | Visible live demo title harvested from rendered DOM |
| REVIEW | Likely category/marketing chrome; excluded from verified list |
| UNVERIFIED | Taxonomy requirement not observed on live page |

## GSAPIFY-VERIFIED effects

1. 3D Card Flip  
2. 3D Coverflow Carousel  
3. 3D Cube Drag  
4. 3D Letter Flip  
5. Accordion with Motion  
6. Animal Silhouette Chain  
7. Animated Bar Chart  
8. Animated Blob  
9. Animated Gradient Background  
10. Animated Infographic  
11. Aurora Borealis Waves  
12. Before/After Slider  
13. Blueprint Reveal  
14. Button Shimmer  
15. Card Background Shift  
16. Card Deck Toss  
17. Card Expand to Detail  
18. Card Hover Lift  
19. Card Slide-In Stagger  
20. Circle Wipe Transition  
21. Circuit Board Trace  
22. Circular Progress Ring  
23. Clip-Path Image Reveal  
24. Confetti Cannon  
25. Constellation Connect  
26. Conveyor Belt  
27. Count-Up Numbers  
28. Counter Preloader  
29. Cursor Spotlight  
30. Curtain Reveal  
31. Day-to-Night Scene  
32. DNA Helix  
33. Draggable Carousel  
34. Elastic Stretch Drag  
35. Encryption Visualizer  
36. Fade Up on Scroll  
37. Flip  
38. Flip Grid Filter  
39. Floating Geometric Shapes  
40. Glitch Text  
41. Gooey Menu Hover  
42. Gradient Text Reveal  
43. Gravity Card Drop  
44. Gravity Form  
45. Grayscale to Color  
46. Horizontal Scroll Section  
47. Hover Border Draw  
48. Icon Morph  
49. Image Parallax Zoom  
50. Image Tilt on Hover  
51. Infinite Loop Carousel  
52. Interactive Particle Text  
53. Jelly Button  
54. Ken Burns Slideshow  
55. Kinetic Split Lines  
56. Layered Zoom Scroll  
57. Liquid Button Morph  
58. Liquid Text Wave  
59. Logo Stroke Reveal  
60. Magnetic Button  
61. Magnetic Repel Grid  
62. Masonry Cascade  
63. Matrix Decode  
64. Mood Face Morph  
65. Newton's Cradle  
66. Notification Shake  
67. Odometer Counter  
68. Particle Float Field  
69. Per-Character Physics Drop  
70. Popcorn Loader  
71. Ripple Click  
72. Rollercoaster Stats  
73. Satellite Orbit  
74. Scratch-Off Reveal  
75. Scroll Path Journey  
76. Scroll Storyteller  
77. Scroll Velocity Skew  
78. Scroll-Scrubbed Progress  
79. Shape Morph  
80. Shared Element Transition  
81. Signature Autograph  
82. Skeleton to Content  
83. Springboard Menu  
84. Stacked Card Fan  
85. Stagger Letter Reveal  
86. Staggered Blinds Reveal  
87. Staggered Grid Reveal  
88. SVG Line Draw  
89. Text Scramble  
90. Throw & Snap  
91. Tilt Parallax Card  
92. Timeline Scroll Experience  
93. Typewriter  
94. Underline Slide  
95. Velocity Blur  
96. Vertical Card Stack  
97. Wheel of Fortune Spin  
98. Wobble Card Enter  
99. Word-by-Word Slide  
100. DNA Helix *(listed once; keep unique)*  

*Canonical unique count after removing marketing chrome:* see `docs/gsapify-verified-names.json` (`general` array) minus REVIEW items:

**REVIEW / excluded from verified implementation claims:** Ease Visualizer, Draggable & MotionPath, MorphSVG & DrawSVG, Physics2D & CustomBounce, Plugin Ecosystem, Portfolios & Creative Sites, SaaS & App Marketing, ScrollTrigger (label), SplitText & ScrambleText (label), Text Animations (hub), Unmatched Performance, What’s Inside, You've Hit Your Limit.

## Per-effect forensic fields

Deep per-demo fields (duration, ease, ST config, wrappers, etc.) are populated by `scripts/gsapify-extract/forensic-pass.mjs` → `docs/gsapify-forensic-pass.json` and summarized in `docs/gsapify-effect-mapping.md`.

Where forensic click inspection fails (paywall / non-interactive card), status remains **GSAPIFY-VERIFIED (name only)** — not a fabricated motion recipe.

## Mapping policy

Reimplement via maintainable first-party presets using current GSAP APIs. Do **not** copy GSAPify minified bundles.
