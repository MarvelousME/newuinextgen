# /assets/fonts

The original React app used **no custom web fonts** — it relied on Tailwind v4's
default `font-sans` (system UI stack) and `font-mono` (system mono stack).

The compiled `assets/css/theme.css` reproduces those exact stacks via the
`--ngt-sans` and `--ngt-mono` CSS variables, so the theme renders identically
with zero external font requests (fast + privacy-friendly, no Google Fonts).

If you later add a brand webfont, drop the files here and register them with an
`@font-face` rule inside `assets/css/theme.css`, then point `--ngt-sans` at it.

> UNVERIFIED: no font files existed in the uploaded React project (system fonts only).
