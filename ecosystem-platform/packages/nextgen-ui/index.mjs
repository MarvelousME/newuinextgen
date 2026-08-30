/**
 * Design system entry — canonical tokens live in repo ui-library + theme assets.
 * Control-center and Next.js apps import @ecosystem/nextgen-ui/tokens.css
 */
export const TOKEN_SOURCES = [
  '../../ui-library/tokens',
  '../../assets/css/tokens/unified.css',
];

export const MOTION = {
  reducedMotionQuery: '(prefers-reduced-motion: reduce)',
  pageTransition: 'ng-page-transition',
  kineticSurface: 'ng-kinetic-surface',
};
