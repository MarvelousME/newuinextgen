/**
 * Connector paths in workspace viewBox coordinates (1200 × 800).
 */
export const CONNECTOR_PATHS = [
  {
    d: 'M 80 90 C 220 70 280 150 480 130',
    stroke: 'var(--cc-connector-cyan)',
    dash: '',
    marker: 'arrow-cyan',
  },
  {
    d: 'M 480 200 C 580 230 640 260 760 240',
    stroke: 'var(--cc-connector-purple)',
    dash: '',
    marker: 'arrow-purple',
  },
  {
    d: 'M 420 320 C 520 360 600 400 720 380',
    stroke: 'var(--cc-connector-cyan)',
    dash: '',
    marker: 'arrow-cyan',
  },
  {
    d: 'M 100 500 L 1050 500',
    stroke: 'var(--cc-connector-green)',
    dash: '6 5',
    marker: '',
  },
  {
    d: 'M 620 560 L 820 560',
    stroke: 'var(--cc-connector-gray)',
    dash: '',
    marker: '',
  },
];

export function mountConnectors(svgRoot) {
  if (!svgRoot) return;
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const ns = 'http://www.w3.org/2000/svg';

  CONNECTOR_PATHS.forEach((path, i) => {
    const el = document.createElementNS(ns, 'path');
    el.setAttribute('d', path.d);
    el.setAttribute('fill', 'none');
    el.setAttribute('stroke', path.stroke);
    el.setAttribute('stroke-width', '1');
    el.setAttribute('class', 'cc-connector-path');
    if (path.dash) el.setAttribute('stroke-dasharray', path.dash);
    if (path.marker) el.setAttribute('marker-end', `url(#${path.marker})`);
    if (!reduced) {
      el.style.animationDelay = `${i * 140}ms`;
    }
    svgRoot.appendChild(el);
  });
}
