/**
 * Lazy WebGL hosts for NextGen Elementor widgets.
 * Reuses window.THREE when present (NGT3D). Never registers a second copy.
 */
(function (root, document) {
  'use strict';

  var instances = [];

  function prefersReduced() {
    return !!(root.matchMedia && root.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  function qualityToDpr(q) {
    if (q === 'low') return 1;
    if (q === 'high') return Math.min(2, root.devicePixelRatio || 1);
    return Math.min(1.5, root.devicePixelRatio || 1);
  }

  function particleCount(q) {
    if (q === 'low') return 80;
    if (q === 'high') return 420;
    return 180;
  }

  function dispose(entry) {
    if (!entry) return;
    if (entry.raf) root.cancelAnimationFrame(entry.raf);
    if (entry.renderer && entry.renderer.dispose) entry.renderer.dispose();
    if (entry.geometry && entry.geometry.dispose) entry.geometry.dispose();
    if (entry.material && entry.material.dispose) entry.material.dispose();
    if (entry.scene) {
      while (entry.scene.children.length) {
        entry.scene.remove(entry.scene.children[0]);
      }
    }
    entry.alive = false;
  }

  function boot(host) {
    if (host.getAttribute('data-bi-el-webgl-ready') === '1') return;
    if (prefersReduced() || host.getAttribute('data-reduced-motion') === 'respect' && prefersReduced()) {
      return;
    }
    var THREE = root.THREE;
    if (!THREE || !THREE.WebGLRenderer) {
      host.setAttribute('data-bi-el-webgl-fallback', 'no-three');
      return;
    }
    var canvas = host.querySelector('.bi-el__webgl-canvas');
    if (!canvas) return;
    canvas.hidden = false;
    var quality = host.getAttribute('data-bi-el-quality') || 'medium';
    var kind = host.getAttribute('data-bi-el-webgl') || 'particles';
    var renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: quality === 'high', alpha: true, powerPreference: 'low-power' });
    renderer.setPixelRatio(qualityToDpr(quality));
    renderer.setSize(host.clientWidth || 300, host.clientHeight || 240, false);
    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(50, (host.clientWidth || 300) / (host.clientHeight || 240), 0.1, 100);
    camera.position.z = 6;
    var geometry = new THREE.BufferGeometry();
    var count = particleCount(quality);
    var positions = new Float32Array(count * 3);
    for (var i = 0; i < count * 3; i++) positions[i] = (Math.random() - 0.5) * 8;
    geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    var color = kind === 'orb' ? 0x22d3ee : 0x059669;
    var material = new THREE.PointsMaterial({ size: kind === 'orb' ? 0.08 : 0.045, color: color, transparent: true, opacity: 0.85 });
    var points = new THREE.Points(geometry, material);
    scene.add(points);
    if (kind === 'orb' || kind === 'scene' || kind === 'interactive') {
      var sphere = new THREE.Mesh(
        new THREE.SphereGeometry(1.1, quality === 'low' ? 12 : 24, quality === 'low' ? 12 : 24),
        new THREE.MeshBasicMaterial({ color: 0x0ea5e9, wireframe: true, transparent: true, opacity: 0.35 })
      );
      scene.add(sphere);
    }
    var entry = { host: host, renderer: renderer, scene: scene, camera: camera, geometry: geometry, material: material, points: points, alive: true, visible: true, raf: 0 };
    host.setAttribute('data-bi-el-webgl-ready', '1');
    instances.push(entry);

    function frame(t) {
      if (!entry.alive) return;
      if (entry.visible) {
        points.rotation.y = t * 0.00012;
        points.rotation.x = t * 0.00008;
        renderer.render(scene, camera);
      }
      entry.raf = root.requestAnimationFrame(frame);
    }
    entry.raf = root.requestAnimationFrame(frame);
  }

  function observe() {
    var hosts = document.querySelectorAll('[data-bi-el-webgl]');
    if (!root.IntersectionObserver) {
      for (var i = 0; i < hosts.length; i++) boot(hosts[i]);
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          boot(e.target);
          instances.forEach(function (inst) {
            if (inst.host === e.target) inst.visible = true;
          });
        } else {
          instances.forEach(function (inst) {
            if (inst.host === e.target) inst.visible = false;
          });
        }
      });
    }, { rootMargin: '80px' });
    for (var h = 0; h < hosts.length; h++) io.observe(hosts[h]);
  }

  function start() {
    observe();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }

  root.addEventListener('beforeunload', function () {
    instances.forEach(dispose);
    instances = [];
  });
})(window, document);
