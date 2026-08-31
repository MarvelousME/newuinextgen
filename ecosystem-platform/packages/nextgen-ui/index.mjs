/**
 * Design system entry — Tapotik-inspired brand tokens for Control Center.
 * Canonical file: tokens.css (`@ecosystem/nextgen-ui/tokens.css`)
 */
export const TOKEN_SOURCES = [
  './tokens.css',
  '../../ui-library/tokens',
  '../../assets/css/tokens/unified.css',
];

export const BRAND = {
  aesthetic: 'dark-first glass aurora',
  accents: ['indigo', 'violet', 'cyan', 'magenta'],
  surfaces: ['GlassNeutral', 'GlassIndigo', 'GlassCyan', 'GlassCritical'],
};

export const MOTION = {
  reducedMotionQuery: '(prefers-reduced-motion: reduce)',
  pageTransition: 'ng-page-transition',
  kineticSurface: 'ng-kinetic-surface',
  fast: '140ms',
  ui: '220ms',
  standard: '320ms',
  slow: '520ms',
  hero: '800ms',
};
