/**
 * Showcase preset: scroll-mask
 * Evidence: "Scroll Mask by Framer Institute" — luxury masked text reveals.
 */
(function (root) {
	'use strict';

	function register(id, def) {
		if (typeof root.NGT3DRegisterPreset === 'function') root.NGT3DRegisterPreset(id, def);
		else if (root.NGT3D && typeof root.NGT3D.registerPreset === 'function') root.NGT3D.registerPreset(id, def);
		else (root.NGT3DPresets || (root.NGT3DPresets = {}))[id] = def;
	}

	register('scroll-mask', {
		id: 'scroll-mask',
		title: 'Scroll Mask by Framer Institute',
		apply: function (el, options, ctx) {
			el.classList.add('ngt-3d-preset--scroll-mask');
			el.classList.add('ngt-3d-ready');
			if (!ctx.gsap) return function () {};

			var texts = el.querySelectorAll('.ngt-3d-mask__text, [data-ngt-mask], h1, h2, h3, .ngt-mask-line');
			var targets = texts.length ? Array.prototype.slice.call(texts) : [el];

			targets.forEach(function (t) {
				t.classList.add('ngt-3d-mask__text');
			});

			if (ctx.reducedMotion) {
				ctx.gsap.set(targets, { clearProps: 'clipPath,webkitMaskImage,maskImage,opacity' });
				return function () {};
			}

			var tween = ctx.gsap.fromTo(targets,
				{
					clipPath: 'inset(0 0 100% 0)',
					WebkitMaskImage: 'linear-gradient(180deg, #000 0%, transparent 0%)',
					maskImage: 'linear-gradient(180deg, #000 0%, transparent 0%)',
					opacity: 0.92
				},
				{
					clipPath: 'inset(0 0 0% 0)',
					WebkitMaskImage: 'linear-gradient(180deg, #000 85%, transparent 100%)',
					maskImage: 'linear-gradient(180deg, #000 85%, transparent 100%)',
					opacity: 1,
					ease: 'none',
					stagger: options.stagger || 0.12,
					scrollTrigger: {
						trigger: el,
						start: options.start || 'top 70%',
						end: options.end || 'top 20%',
						scrub: options.scrub !== undefined ? options.scrub : 1
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
