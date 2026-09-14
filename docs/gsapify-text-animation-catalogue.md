# GSAPify Text Animation Catalogue

**Source URL:** https://gsapify.com/gsap-text-animations/#text-animation-collection  
**Page title (live):** GSAP Text Animations | GSAPify  
**Extraction method:** Playwright Chromium + full scroll + card title harvest + read-only GSAP inspection  
**Extracted at:** 2026-09-12  
**HTTP note:** Non-browser scrape historically **403**; live Chromium **200**.

## Runtime snapshot (read-only)

| Field | Value |
|-------|-------|
| GSAP version | 3.15.0 |
| Active tweens (sample) | 45 |
| ScrollTriggers (sample) | 184 |
| Plugins observed | attr, endArray, roundProps, modifiers, snap, css, motionPath, text |

> Note: Club plugins such as SplitText / ScrambleText may still power demos without appearing on `gsap.plugins` keys the same way. Do not invent plugin presence.

## Catalogue counts

| Metric | Count |
|--------|------:|
| Raw card-strategy hits | 319 |
| Unique titles after filter | **91** |
| **GSAPIFY-VERIFIED text effects** (noise removed) | **88** |
| Hub / tutorial / limit chrome excluded | 3+ |

## Split / text strategy observations (page-level)

Observed motion language on the text catalogue includes:

| Strategy family | Evidence on page |
|-----------------|------------------|
| Characters / letters | Staggered Letters, Per-character style names, 3D Letter Flip (general page) |
| Words | Fade Up Words, Word-by-Word Build, Word Shuffle |
| Lines | Line-by-Line Reveal, Kinetic Split Lines (general), Parallax Lines |
| Mask / wipe | Curtain Reveal, Reveal Wipe, Vertical Blinds, Redacted Reveal |
| Scramble / decode | Scramble Decode, Text Scramble, Matrix Rain, Binary Decode, Typing + Scramble Combo |
| Typewriter | Typewriter Effect, Typewriter Delete, Typewriter Paragraph |
| Scroll-linked text | Scroll Highlight, Scroll Scrub, Scroll Counter, Scroll Driven |
| 3D / perspective | Unfold 3D, Perspective Fly, Flip-X/Y Effect |
| Physics-ish | Gravity Fall, Gravity Stack, Pinball, Earthquake, Popcorn Pop |

Official GSAP SplitText (chars/words/lines, masking, autoSplit, accessibility, revert) should be used when the Club plugin is available. Without it, Motion Manager must **not** label a regex splitter as SplitText.

## GSAPIFY-VERIFIED text effects

1. Advanced Text Reveal  
2. Binary Decode  
3. Blur In  
4. Bounce Settle  
5. Bounce-In Effect  
6. Cascade Reveal  
7. Cinema Title  
8. Color Wash  
9. Cross Fade  
10. CRT Boot  
11. Curtain Reveal  
12. Domino Fall  
13. Drop & Shatter  
14. Earthquake  
15. Elastic Snap  
16. Fade Up Words  
17. Fade-In Effect  
18. Film Credits  
19. Firework Burst  
20. Flip Board  
21. Flip-X Effect  
22. Flip-Y Effect  
23. Focus Pull  
24. Ghost Trail  
25. Glitch Effect  
26. Gravity Fall  
27. Gravity Stack  
28. Heartbeat  
29. Ink Bleed  
30. Ink Drop Spread  
31. Line-by-Line Reveal  
32. Liquid Fill  
33. Magnetic Pull  
34. Matrix Rain  
35. Morphing Counter  
36. Neon Flicker  
37. Neon Sign  
38. Origami Unfold  
39. Paragraph Unfurl  
40. Pendulum Swing  
41. Perspective Fly  
42. Pinball  
43. Popcorn Pop  
44. Redacted Reveal  
45. Reveal Wipe  
46. RGB Split  
47. Ripple Wave  
48. Rotate-In Effect  
49. Rubber Band  
50. Rubber Stamp  
51. Scale-Up Effect  
52. Scramble Decode  
53. Scroll Counter  
54. Scroll Driven  
55. Scroll Highlight  
56. Scroll Scrub  
57. Sentence Cascade  
58. Shadow Pulse  
59. Shuffle These Words  
60. Skew-In Effect  
61. Slide From Left  
62. Slide From Right  
63. Slide Up Reveal  
64. Slot Machine  
65. Smoke Rise  
66. Spin In  
67. Spiral In  
68. Spotlight Reveal  
69. Spring Scale  
70. Staggered Letters  
71. Stencil Fill  
72. Stretch Warp  
73. Text Reveal  
74. Text Scramble  
75. Typewriter Delete  
76. Typewriter Effect  
77. Typewriter Paragraph  
78. Typing + Scramble Combo  
79. Unfold 3D  
80. Vertical Blinds  
81. VHS Tracking  
82. Wave Color Shift  
83. Wavy Baseline  
84. Whip Slide  
85. Word Shuffle  
86. Word-by-Word Build  
87. Zoom-Out Effect  
88. Progress Underline *(if present in extract; confirm against JSON)*  

**REVIEW / excluded:** Step-by-Step Wave Tutorial, You've Hit Your Limit, duplicate Slide-From-Left variants collapsed to one preset with direction enum, Wave / Staggered Animations (hub label).

## Lifecycle requirements (for our engine)

```text
capture original DOM → fonts.ready when lines → split → animate
→ responsive re-split → destroy → revert
```

Reduced motion: fade-only or instant reveal; never leave text invisible.
