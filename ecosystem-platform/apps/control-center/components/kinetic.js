/**
 * Kinetic interactions — spotlight cards + constrained 3D tilt.
 * Original derived motion language (not proprietary Framer/Lenis code).
 */

const REDUCED = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function setSpot(el, event) {
  const rect = el.getBoundingClientRect();
  const x = ((event.clientX - rect.left) / rect.width) * 100;
  const y = ((event.clientY - rect.top) / rect.height) * 100;
  el.style.setProperty('--spot-x', `${x}%`);
  el.style.setProperty('--spot-y', `${y}%`);
}

function bindSpotlight(el) {
  el.addEventListener('pointerenter', (e) => {
    el.classList.add('is-spotlighted');
    setSpot(el, e);
  });
  el.addEventListener('pointermove', (e) => setSpot(el, e));
  el.addEventListener('pointerleave', () => {
    el.classList.remove('is-spotlighted');
    el.style.removeProperty('--spot-x');
    el.style.removeProperty('--spot-y');
  });
}

function bindTilt(el, { maxX = 4, maxY = 6, scale = 1.02 } = {}) {
  el.addEventListener('pointermove', (e) => {
    if (REDUCED()) return;
    const rect = el.getBoundingClientRect();
    const px = (e.clientX - rect.left) / rect.width;
    const py = (e.clientY - rect.top) / rect.height;
    const rotY = (px - 0.5) * (maxY * 2);
    const rotX = (0.5 - py) * (maxX * 2);
    el.style.transform = `perspective(1200px) rotateX(${rotX.toFixed(2)}deg) rotateY(${rotY.toFixed(2)}deg) scale(${scale})`;
  });
  el.addEventListener('pointerleave', () => {
    el.style.transform = '';
  });
}

/**
 * Enable spotlight on cards and tilt on kinetic targets within root.
 */
export function enableKinetic(root = document) {
  root.querySelectorAll('.cc-card, .cc-subsystem-card').forEach((el) => {
    if (el.dataset.kineticBound === '1') return;
    el.dataset.kineticBound = '1';
    bindSpotlight(el);
  });

  root.querySelectorAll('[data-kinetic="tilt"]').forEach((el) => {
    if (el.dataset.kineticTilt === '1') return;
    el.dataset.kineticTilt = '1';
    bindSpotlight(el);
    bindTilt(el);
  });
}
