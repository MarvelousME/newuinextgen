/**
 * Showcase preset: 4kvideo
 * Evidence: "Crystal Clear 4K" — media scale/pin emphasis.
 * Does NOT scrub video.currentTime.
 */
(function (root) {
	'use strict';

	function register(id, def) {
		if (typeof root.NGT3DRegisterPreset === 'function') root.NGT3DRegisterPreset(id, def);
		else if (root.NGT3D && typeof root.NGT3D.registerPreset === 'function') root.NGT3D.registerPreset(id, def);
		else (root.NGT3DPresets || (root.NGT3DPresets = {}))[id] = def;
	}

	register('4kvideo', {
		id: '4kvideo',
		title: 'Crystal Clear 4K',
		apply: function (el, options, ctx) {
			el.classList.add('ngt-3d-preset--4kvideo');
			el.classList.add('ngt-3d-ready');
			if (!ctx.gsap) return function () {};

			var media = el.querySelector('video, .ngt-3d-4k__media, [data-ngt-media], img');
			if (!media) media = el;

			/* Explicitly never touch video.currentTime scrubbing. */
			var scaleFrom = options.scaleFrom != null ? options.scaleFrom : 1.18;
			var scaleTo = options.scaleTo != null ? options.scaleTo : 1;

			var tween = ctx.gsap.fromTo(media,
				{ scale: scaleFrom },
				{
					scale: scaleTo,
					ease: 'none',
					scrollTrigger: {
						trigger: el,
						start: 'top top',
						end: options.end || '+=120%',
						scrub: options.scrub !== undefined ? options.scrub : 1,
						pin: options.pin !== false,
						anticipatePin: 1
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
