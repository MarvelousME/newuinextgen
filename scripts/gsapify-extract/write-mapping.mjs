import fs from 'fs';

const names = JSON.parse(fs.readFileSync('docs/gsapify-verified-names.json', 'utf8'));
const idFor = {
  'Fade Up on Scroll': 'gsapify-fade-up-on-scroll',
  'Card Hover Lift': 'gsapify-card-hover-lift',
  'Card Slide-In Stagger': 'gsapify-card-slide-in-stagger',
  'Clip-Path Image Reveal': 'gsapify-clip-path-image-reveal',
  'Horizontal Scroll Section': 'gsapify-horizontal-scroll-section',
  'Image Parallax Zoom': 'gsapify-image-parallax-zoom',
  'Image Tilt on Hover': 'gsapify-image-tilt-on-hover',
  'Ken Burns Slideshow': 'gsapify-ken-burns',
  'Kinetic Split Lines': 'gsapify-kinetic-split-lines',
  'Layered Zoom Scroll': 'gsapify-layered-zoom-scroll',
  'Magnetic Button': 'gsapify-magnetic-button',
  'Stacked Card Fan': 'gsapify-stacked-card-fan',
  'Stagger Letter Reveal': 'gsapify-stagger-letter-reveal',
  'Staggered Grid Reveal': 'gsapify-staggered-grid-reveal',
  'Text Scramble': 'gsapify-text-scramble',
  Typewriter: 'gsapify-typewriter',
  'Tilt Parallax Card': 'gsapify-tilt-parallax-card',
  'Vertical Card Stack': 'gsapify-vertical-card-stack',
  'Wobble Card Enter': 'gsapify-wobble-card-enter',
  'Word-by-Word Slide': 'gsapify-word-by-word-slide',
  'Curtain Reveal': 'gsapify-curtain-reveal',
  'Grayscale to Color': 'gsapify-grayscale-to-color',
  'Underline Slide': 'gsapify-underline-slide',
  'Count-Up Numbers': 'gsapify-count-up',
  'Scroll-Scrubbed Progress': 'gsapify-scroll-scrubbed-progress',
  'Fade Up Words': 'gsapify-text-fade-up-words',
  'Line-by-Line Reveal': 'gsapify-text-line-by-line',
  'Blur In': 'gsapify-text-blur-in',
  'Slide From Left': 'gsapify-text-slide-left',
  'Slide From Right': 'gsapify-text-slide-right',
  'Staggered Letters': 'gsapify-text-staggered-letters',
  'Word-by-Word Build': 'gsapify-text-word-build',
  'Scramble Decode': 'gsapify-text-scramble-decode',
  'Typewriter Effect': 'gsapify-text-typewriter-effect',
};
const mapped = Object.keys(idFor);
const lines = [
  '# GSAPify Effect Mapping',
  '',
  'Live harvest → Motion Manager preset IDs. Recipes are first-party reimplementations (not copied GSAPify bundles).',
  '',
  '## Summary',
  '',
  '| Metric | Count |',
  '|--------|------:|',
  `| GSAPify general names harvested | ${names.generalCount} |`,
  `| GSAPify text names harvested | ${names.textCount} |`,
  `| Mapped into manager (this pass) | ${mapped.length} |`,
  `| Remaining discovered-but-unmapped | ${names.generalCount + names.textCount - mapped.length} |`,
  '',
  'Unmapped rows stay **GSAPIFY-VERIFIED (name only)** — not falsely marked implemented.',
  '',
  '## Mapped',
  '',
  '| GSAPify Name | Our Effect ID | Strategy | Verification |',
  '|-------------|---------------|----------|--------------|',
];
mapped.forEach((n) => {
  lines.push(`| ${n} | \`${idFor[n]}\` | primitive/composition | GSAPIFY-VERIFIED |`);
});
lines.push('', '## Unmapped verified names', '');
[...names.general, ...names.text]
  .filter((n) => !mapped.includes(n))
  .forEach((n) => lines.push(`- ${n} — **pending**`));
fs.writeFileSync('docs/gsapify-effect-mapping.md', lines.join('\n'));
console.log('mapped', mapped.length, 'unmapped', names.generalCount + names.textCount - mapped.length);
