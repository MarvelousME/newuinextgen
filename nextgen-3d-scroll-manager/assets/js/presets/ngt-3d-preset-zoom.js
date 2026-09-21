/**
 * Showcase preset: zoom
 * Evidence: "Zoom Entrance" — scale entrance scrubbed/triggered.
 */
(function (root) {
	'use strict';

	function register(id, def) {
		if (typeof root.NGT3DRegisterPreset === 'function') root.NGT3DRegisterPreset(id, def);
		else if (root.NGT3D && typeof root.NGT3D.registerPreset === 'function') root.NGT3D.registerPreset(id, def);
		else (root.NGT3DPresets || (root.NGT3DPresets = {}))[id] = def;
	}

	register('zoom', {
		id: 'zoom',
		title: 'Zoom Entrance',
		apply: function (el, options, ctx) {
			el.classList.add('ngt-3d-preset--zoom');
			el.classList.add('ngt-3d-ready');
			if (!ctx.gsap) return function () {};

			var target = el.querySelector('.ngt-3d-zoom__target, [data-ngt-zoom], img, video') || el;
			var from = options.scaleFrom != null ? options.scaleFrom : 0.72;
			var to = options.scaleTo != null ? options.scaleTo : 1;
			var scrub = options.scrub !== undefined ? options.scrub : 1;

			if (ctx.reducedMotion) {
				ctx.gsap.set(target, { scale: 1 });
				return function () {};
			}

			var tween = ctx.gsap.fromTo(target,
				{ scale: from, transformOrigin: 'center center' },
				{
					scale: to,
					ease: scrub ? 'none' : 'power2.out',
					duration: options.duration || 1,
					scrollTrigger: scrub
						? {
							trigger: el,
							start: options.start || 'top 85%',
							end: options.end || 'top 30%',
							scrub: scrub
						}
						: {
							trigger: el,
							start: options.start || 'top 80%',
							toggleActions: 'play none none none'
						}
				}
			);

			return function () {
				if (tween.scrollTrigger) tween.scrollTrigger.kill();
				tween.kill();
			};
		}
	});
})(typeof window !== 'undefined' ? window : this);
