/**
 * Showcase preset: onscroll
 * Evidence: "OnScroll" / Illustrations Showcase — staggered image/media entrances.
 */
(function (root) {
	'use strict';

	function register(id, def) {
		if (typeof root.NGT3DRegisterPreset === 'function') root.NGT3DRegisterPreset(id, def);
		else if (root.NGT3D && typeof root.NGT3D.registerPreset === 'function') root.NGT3D.registerPreset(id, def);
		else (root.NGT3DPresets || (root.NGT3DPresets = {}))[id] = def;
	}

	register('onscroll', {
		id: 'onscroll',
		title: 'OnScroll',
		apply: function (el, options, ctx) {
			el.classList.add('ngt-3d-preset--onscroll');
			el.classList.add('ngt-3d-ready');
			if (!ctx.gsap) return function () {};

			var media = el.querySelectorAll(
				'.ngt-3d-onscroll__item, [data-ngt-enter], img, picture, video, figure, .illustration'
			);
			var targets = media.length ? Array.prototype.slice.call(media) : Array.prototype.slice.call(el.children);
			if (!targets.length) targets = [el];

			if (ctx.reducedMotion) {
				ctx.gsap.set(targets, { clearProps: 'y,opacity,scale' });
				return function () {};
			}

			var tween = ctx.gsap.fromTo(targets,
				{ y: options.y != null ? options.y : 48, opacity: 0.9, scale: options.scaleFrom != null ? options.scaleFrom : 0.96 },
				{
					y: 0,
					opacity: 1,
					scale: 1,
					stagger: options.stagger || 0.1,
					ease: options.scrub ? 'none' : 'power2.out',
					duration: options.duration || 0.9,
					scrollTrigger: options.scrub
						? { trigger: el, start: 'top 80%', end: 'top 30%', scrub: options.scrub }
						: { trigger: el, start: 'top 85%', toggleActions: 'play none none none' }
				}
			);

			return function () {
				if (tween.scrollTrigger) tween.scrollTrigger.kill();
				tween.kill();
			};
		}
	});
})(typeof window !== 'undefined' ? window : this);
