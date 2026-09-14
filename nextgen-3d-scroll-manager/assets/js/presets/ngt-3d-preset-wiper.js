/**
 * Showcase preset: wiper
 * Evidence: "Wiper Effect" — clip-path wipe reveal.
 */
(function (root) {
	'use strict';

	function register(id, def) {
		if (typeof root.NGT3DRegisterPreset === 'function') root.NGT3DRegisterPreset(id, def);
		else if (root.NGT3D && typeof root.NGT3D.registerPreset === 'function') root.NGT3D.registerPreset(id, def);
		else (root.NGT3DPresets || (root.NGT3DPresets = {}))[id] = def;
	}

	register('wiper', {
		id: 'wiper',
		title: 'Wiper Effect',
		apply: function (el, options, ctx) {
			el.classList.add('ngt-3d-preset--wiper');
			el.classList.add('ngt-3d-ready');
			if (!ctx.gsap) return function () {};

			var panels = el.querySelectorAll('.ngt-3d-wiper__panel, [data-ngt-wipe], img, picture, video');
			var targets = panels.length ? Array.prototype.slice.call(panels) : [el];
			var dir = options.direction || 'left';
			var fromClip =
				dir === 'right' ? 'inset(0 0 0 100%)' :
				dir === 'up' ? 'inset(100% 0 0 0)' :
				dir === 'down' ? 'inset(0 0 100% 0)' :
				'inset(0 100% 0 0)';

			if (ctx.reducedMotion) {
				ctx.gsap.set(targets, { clipPath: 'inset(0 0% 0 0)' });
				return function () {};
			}

			var tween = ctx.gsap.fromTo(targets,
				{ clipPath: fromClip },
				{
					clipPath: 'inset(0 0% 0 0)',
					ease: 'none',
					stagger: options.stagger || 0.05,
					scrollTrigger: {
						trigger: el,
						start: options.start || 'top 75%',
						end: options.end || 'top 25%',
						scrub: options.scrub !== undefined ? options.scrub : 0.8
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
