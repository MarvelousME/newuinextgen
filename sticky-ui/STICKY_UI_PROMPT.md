# Sticky UI — Specification

Canonical spec for the **sticky header + collapsible FAB** pattern.  
Reference implementation: [`sticky-ui.html`](./sticky-ui.html) (vanilla JS + Tailwind CDN).

Use this document to rebuild an identical React (or other) version, or to customize the standalone HTML.

---

## 1. Deliverables

| File | Purpose |
|------|---------|
| `sticky-ui.html` | Standalone, copy-paste, open in browser. No build step. Tailwind via CDN only. |
| `STICKY_UI_PROMPT.md` | This spec — layout, state, timing, z-index, customization, production checklist. |

**Constraints**

- Pure vanilla JavaScript (no bundler)
- Tailwind CSS via CDN (`https://cdn.tailwindcss.com`)
- Self-contained except that CDN
- Identical visual/behavioral contract to any React port of this spec

---

## 2. Exact layout / positioning

### 2.1 Sticky header

| Property | Value |
|----------|-------|
| Position | `fixed` |
| Placement | `top: 0; left: 0; right: 0` |
| Height (bar) | `64px` (`h-16`) |
| Z-index | `40` (`z-header`) |
| Background | Linear gradient: `from-brand-800 via-brand-600 to-sky-500` (`#1e40af → #2563eb → #0ea5e9`) |
| Text | White / white at 90% opacity for nav links |
| Shadow (rest) | None |
| Shadow (scrolled) | `shadow-lg shadow-brand-900/25` when `scrollY > 8` |
| Max content width | `max-w-6xl` centered with `px-4 sm:px-6` |
| Desktop nav | Visible `md:` and up |
| Mobile nav | Hidden on `md:+`; hamburger button `md:hidden` |
| Mobile panel | Full-width under bar; expands `max-h-0 → max-h-80`, opacity `0 → 1` over **300ms** |

### 2.2 FAB cluster

| Property | Value |
|----------|-------|
| Position | `fixed` |
| Placement | `bottom: 1.5rem; right: 1.5rem` (`bottom-6 right-6`) |
| Z-index | `30` (`z-fab`) |
| Layout | `flex flex-col-reverse items-center gap-3` (actions stack **above** toggle) |
| Toggle size | `56×56px` (`h-14 w-14`), min touch `48×48` |
| Action size | `48×48px` (`h-12 w-12`) |
| Toggle style | Gradient `from-brand-600 to-sky-500`, white icon, `ring-4 ring-white`, drop shadow |
| Action styles | Four distinct gradients (see §5) |

### 2.3 Page content

| Property | Value |
|----------|-------|
| Z-index | `0` (`z-content`) |
| Top padding | `pt-16` (clears fixed header) |
| Scroll | Normal document flow; tall filler sections for persistence testing |

---

## 3. Z-index layering diagram

```
                    ▲ higher
 ┌──────────────────────────────────────┐
 │  z-40  STICKY HEADER                 │  ← always on top of FAB
 │        (nav, mobile drawer)          │
 ├──────────────────────────────────────┤
 │  z-30  FAB CLUSTER                   │  ← toggle + 4 action buttons
 │        (bottom-right)                │
 ├──────────────────────────────────────┤
 │  z-0   PAGE CONTENT                  │  ← scrolls underneath both
 │        (main, sections, footer)      │
 └──────────────────────────────────────┘
                    ▼ lower
```

**Rules**

- Header must never be covered by the FAB.
- FAB must never be covered by scrolling content.
- No competing fixed UI above `z-40` in this demo.

---

## 4. State management architecture

Vanilla equivalent of React local state:

```js
state = {
  scrolled: boolean,   // scrollY > 8 → header shadow
  fabOpen: boolean,    // FAB actions visible
  mobileOpen: boolean, // mobile nav expanded
  timers: number[],    // stagger timeout IDs (cleared on close/reopen)
}
```

### 4.1 Transitions (reducers)

| Action | Effect |
|--------|--------|
| `SCROLL` | `scrolled = scrollY > 8`; toggle shadow classes on `#sticky-header` |
| `TOGGLE_MOBILE` | Flip `mobileOpen`; sync `aria-expanded`, icons, panel `max-h` / `opacity` / `hidden` |
| `TOGGLE_FAB` | Flip `fabOpen`; clear timers; open → staggered show; close → **instant** hide |
| `FAB_ACTION_CLICK` | Navigate (hash) + `fabOpen = false` (instant collapse) |
| `ESCAPE` | Close FAB and/or mobile nav if open |
| `OUTSIDE_CLICK` | If target outside `#fab-root` and `fabOpen`, close FAB |

### 4.2 React mapping (for ports)

```tsx
const [scrolled, setScrolled] = useState(false);
const [fabOpen, setFabOpen] = useState(false);
const [mobileOpen, setMobileOpen] = useState(false);

useEffect(() => {
  const onScroll = () => setScrolled(window.scrollY > 8);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
  return () => window.removeEventListener('scroll', onScroll);
}, []);
```

Stagger open with `useEffect` + `setTimeout` when `fabOpen` becomes `true`; on `false`, clear timeouts and apply collapsed classes immediately (no transition).

---

## 5. Animation timing values

| Concern | Value | Notes |
|---------|-------|-------|
| General UI transition | **300ms** | Header shadow, mobile panel, FAB item open |
| Easing (FAB open) | `cubic-bezier(0.16, 1, 0.3, 1)` | Snappy ease-out |
| FAB stagger delays | **0 / 50 / 100 / 150 ms** | Index `0` = closest to toggle |
| FAB open motion | `opacity: 0→1` + `translateY: 12px→0` (`translate-y-3 → translate-y-0`) | |
| FAB collapse | **0ms** (instant) | Class `is-collapsing` forces `transition-duration: 0ms` |
| Scroll shadow threshold | `scrollY > 8` | |
| `prefers-reduced-motion` | Disable transitions | CSS media query |

### FAB action gradients (index → style)

| Index | Delay | Gradient | Label (demo) |
|------:|------:|----------|--------------|
| 0 | 0ms | emerald → teal | Contact |
| 1 | 50ms | violet → fuchsia | Pricing |
| 2 | 100ms | amber → orange | Docs |
| 3 | 150ms | rose → pink | Features |

---

## 6. Key behavioral contract

| Aspect | Behavior |
|--------|----------|
| Scroll persistence | Header & FAB `position: fixed` — visible at all scroll positions |
| Z-layering | Header `z-40` / FAB `z-30` / Content `z-0` |
| Header | Gradient blue, white text, shadow on scroll, responsive nav |
| FAB menu | Collapsible (click home icon), 4 gradient buttons, staggered reveal (0/50/100/150ms) |
| Animation | 300ms transitions, translateY + opacity, **instant** collapse |
| Mobile | Nav links hidden (`md:hidden` inverse), menu icon visible, touch-friendly (**48px+** buttons) |

---

## 7. Accessibility

- Header: `role="banner"`; navs have `aria-label`
- Mobile button: `aria-expanded`, `aria-controls="mobile-nav"`, dynamic `aria-label`
- FAB toggle: `aria-expanded`, `aria-controls="fab-menu"`, dynamic `aria-label`
- FAB actions: each has `aria-label` + `title`
- `Escape` closes mobile nav and FAB
- Outside click closes FAB
- Touch targets ≥ 48×48px on interactive controls
- Icons decorative: `aria-hidden="true"` where label is on the control

---

## 8. Customization points

| Token / hook | Where | Change to… |
|--------------|-------|------------|
| Brand colors | `tailwind.config.theme.extend.colors.brand` | Site palette |
| Header gradient | Header inner `bg-gradient-to-r …` | Brand gradient |
| Scroll threshold | `scrollY > 8` in JS | Stricter/looser shadow |
| FAB position | `bottom-6 right-6` | `left-6`, safe-area insets |
| Stagger array | `STAGGER_MS = [0, 50, 100, 150]` | Faster/slower cascade |
| Transition duration | `TRANSITION_MS = 300` + Tailwind `duration-sticky` | Match design system |
| Action count / hrefs | `.fab-item` anchors + `data-fab-index` | Product actions (WA, chat, etc.) |
| Nav links | Desktop + mobile `<a>` lists | Real routes |
| Z tokens | `zIndex.header / fab / content` | Fit existing stacking context |
| Shadow classes | `shadow-lg shadow-brand-900/25` | Softer/harder elevation |

---

## 9. Production checklist

- [ ] Open `sticky-ui.html` directly in Chrome / Edge / Safari / Firefox (file:// or static host)
- [ ] Scroll full page: header stays fixed; shadow appears after ~8px
- [ ] Desktop (≥768px): nav links visible; hamburger hidden
- [ ] Mobile (&lt;768px): links hidden; hamburger opens/closes panel in 300ms
- [ ] FAB toggle opens 4 buttons with visible stagger (0/50/100/150ms)
- [ ] FAB close is **instant** (no fade-out lag)
- [ ] Action click navigates and collapses FAB
- [ ] `Escape` and outside-click close FAB
- [ ] All interactive controls ≥ 48px hit area
- [ ] Header visually above FAB (z-40 &gt; z-30)
- [ ] Content never paints over FAB/header
- [ ] `prefers-reduced-motion: reduce` disables motion
- [ ] No console errors; only network request is Tailwind CDN
- [ ] If porting to React: match state fields, timing table, and z-index diagram exactly
- [ ] Replace demo anchors with production URLs / handlers before shipping
- [ ] Add `env(safe-area-inset-*)` padding for notched phones if embedding in a native shell

---

## 10. Theme integration (BeyondInfinity)

Applied to production chrome in theme **1.9.28+**:

| Surface | Theme hook |
|---------|------------|
| Header | `.ngt-nav` — `position: fixed`, `z-index: var(--bi-z-header, 40)`, scroll shadow at `scrollY > 8` (sole owner: `bi-sticky-ui.js`) |
| FAB | `#float-dock` — collapsible via `#fab-toggle`; expandable actions in `#fab-menu`; WhatsApp always visible as `.float-dock__persist` outside the menu |
| CSS | `assets/css/bi-sticky-ui.css` (enqueued once from `inc/ngt-assets.php`) |
| JS | `assets/js/bi-sticky-ui.js` — header scroll + FAB open/close + focus restore |
| Markup | `bi_float_dock()` in `inc/template-tags.php` only (`floating.js` binds events; does not rebuild HTML) |

## 11. File usage

```bash
# From repo root — open in browser
start sticky-ui/sticky-ui.html   # Windows
open sticky-ui/sticky-ui.html    # macOS
```

No install. No build. Edit HTML/JS in place; refresh to verify.
