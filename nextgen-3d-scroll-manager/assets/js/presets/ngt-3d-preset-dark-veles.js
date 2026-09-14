/**
 * Showcase preset: dark-veles
 * Evidence: full landing motion LANGUAGE only —
 * sticky section pacing, staggered reveals, image scale.
 * Not a content clone of DARK VELES.
 */
(function (root) {
	'use strict';

	function register(id, def) {
		if (typeof root.NGT3DRegisterPreset === 'function') root.NGT3DRegisterPreset(id, def);
		else if (root.NGT3D && typeof root.NGT3D.registerPreset === 'function') root.NGT3D.registerPreset(id, def);
		else (root.NGT3DPresets || (root.NGT3DPresets = {}))[id] = def;
	}

	register('dark-veles', {
		id: 'dark-veles',
		title: 'DARK VELES',
		apply: function (el, options, ctx) {
			el.classList.add('ngt-3d-preset--dark-veles');
			el.classList.add('ngt-3d-ready');
			if (!ctx.gsap) return function () {};

			var sections = el.querySelectorAll('[data-ngt-pace], .ngt-3d-dv__section, section');
			var paceTargets = sections.length ? Array.prototype.slice.call(sections) : [el];
			var reveals = el.querySelectorAll('[data-ngt-reveal], .ngt-3d-dv__reveal, h1, h2, h3, p, li');
			var images = el.querySelectorAll('[data-ngt-scale], .ngt-3d-dv__media, img, picture');
			var cleanups = [];

			paceTargets.forEach(function (sec) {
				var st = ctx.ScrollTrigger.create({
					trigger: sec,
					start: 'top top',
					end: options.paceEnd || '+=80%',
					pin: options.pin !== false && paceTargets.length > 1 ? true : options.pin === true,
					pinSpacing: true,
					scrub: options.scrub !== undefined ? options.scrub : 1
				});
				cleanups.push(function () { st.kill(); });
			});

			if (reveals.length && !ctx.reducedMotion) {
				var rt = ctx.gsap.fromTo(reveals,
					{ y: 36, opacity: 0.88 },
					{
						y: 0,
						opacity: 1,
						stagger: options.stagger || 0.07,
						ease: 'none',
						scrollTrigger: {
							trigger: el,
							start: 'top 70%',
							end: 'bottom 40%',
							scrub: 0.9
						}
					}
				);
				cleanups.push(function () {
					if (rt.scrollTrigger) rt.scrollTrigger.kill();
					rt.kill();
				});
			}

			if (images.length) {
				var it = ctx.gsap.fromTo(images,
					{ scale: options.scaleFrom != null ? options.scaleFrom : 1.12 },
					{
						scale: 1,
						ease: 'none',
						scrollTrigger: {
							trigger: el,
							start: 'top bottom',
							end: 'bottom top',
							scrub: 1
						}
					}
				);
				cleanups.push(function () {
					if (it.scrollTrigger) it.scrollTrigger.kill();
					it.kill();
				});
			}

			return function () {
				while (cleanups.length) cleanups.pop()();
			};
		}
	});
})(typeof window !== 'undefined' ? window : this);
