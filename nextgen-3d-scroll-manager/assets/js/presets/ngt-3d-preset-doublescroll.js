/**
 * Showcase preset: doublescroll
 * Evidence: dual-column titles, opposite-direction vertical tracks, sticky scene.
 */
(function (root) {
	'use strict';

	function register(id, def) {
		if (typeof root.NGT3DRegisterPreset === 'function') root.NGT3DRegisterPreset(id, def);
		else if (root.NGT3D && typeof root.NGT3D.registerPreset === 'function') root.NGT3D.registerPreset(id, def);
		else (root.NGT3DPresets || (root.NGT3DPresets = {}))[id] = def;
	}

	function findTracks(el) {
		var left = el.querySelector('[data-ngt-track="left"], .ngt-3d-ds__left, .ngt-ds-left');
		var right = el.querySelector('[data-ngt-track="right"], .ngt-3d-ds__right, .ngt-ds-right');
		if (left && right) return { left: left, right: right };
		var cols = el.querySelectorAll('.ngt-3d-ds__col, [data-ngt-col]');
		if (cols.length >= 2) return { left: cols[0], right: cols[1] };
		var kids = Array.prototype.filter.call(el.children, function (n) {
			return n.nodeType === 1;
		});
		if (kids.length >= 2) return { left: kids[0], right: kids[1] };
		return null;
	}

	register('doublescroll', {
		id: 'doublescroll',
		title: 'Double Scroll Effect',
		apply: function (el, options, ctx) {
			el.classList.add('ngt-3d-preset--doublescroll');
			el.classList.add('ngt-3d-ready');
			var tracks = findTracks(el);
			if (!tracks || !ctx.gsap) return function () {};

			var dist = options.distance || Math.max(el.offsetHeight, 400);
			var scrub = options.scrub !== undefined ? options.scrub : 1;

			var tl = ctx.gsap.timeline({
				scrollTrigger: {
					trigger: el,
					start: 'top top',
					end: options.end || '+=' + (dist * 2),
					scrub: scrub,
					pin: options.pin !== false,
					anticipatePin: 1
				}
			});

			tl.fromTo(tracks.left, { y: 0 }, { y: -dist, ease: 'none' }, 0);
			tl.fromTo(tracks.right, { y: -dist * 0.35 }, { y: dist * 0.65, ease: 'none' }, 0);

			var titles = el.querySelectorAll('.ngt-3d-ds__title, [data-ngt-title], h1, h2');
			if (titles.length) {
				tl.fromTo(titles, { opacity: 0.85, y: 24 }, { opacity: 1, y: 0, stagger: 0.08, ease: 'none' }, 0);
			}

			return function () {
				if (tl.scrollTrigger) tl.scrollTrigger.kill();
				tl.kill();
			};
		}
	});
})(typeof window !== 'undefined' ? window : this);
