/**
 * Showcase preset: transforms
 * Evidence: Framer scroll-transform / 3D image gallery patterns
 * (Academy scroll transforms + Framer University 3D image scroll gallery).
 * Original transforms.framer.website changed domain — this implements the
 * documented motion language: perspective stage, rotateY/X, translateZ, scale.
 */
(function (root) {
	'use strict';

	function register(id, def) {
		if (typeof root.NGT3DRegisterPreset === 'function') root.NGT3DRegisterPreset(id, def);
		else if (root.NGT3D && typeof root.NGT3D.registerPreset === 'function') root.NGT3D.registerPreset(id, def);
		else (root.NGT3DPresets || (root.NGT3DPresets = {}))[id] = def;
	}

	register('transforms', {
		id: 'transforms',
		title: '3D Transforms Gallery',
		status: 'ready',
		apply: function (el, options, ctx) {
			el.classList.add('ngt-3d-preset--transforms');
			el.classList.add('ngt-3d-ready');
			el.removeAttribute('data-ngt-preset-status');

			var stage = el.querySelector('.ngt-3d-transforms__stage, [data-ngt-transforms-stage]') || el;
			var items = el.querySelectorAll('.ngt-3d-transforms__item, [data-ngt-transforms-item]');
			if (!items.length) {
				items = stage.querySelectorAll('figure, article, img, .ngt-3d-demo__card');
			}
			items = Array.prototype.slice.call(items);
			if (!items.length) return function () {};

			var scrub = options.scrub !== undefined ? options.scrub : 1;
			var perspective = options.perspective != null ? options.perspective : 1600;
			var rotateY = options.rotateY != null ? options.rotateY : 55;
			var rotateX = options.rotateX != null ? options.rotateX : -8;
			var zFrom = options.zFrom != null ? options.zFrom : -220;
			var zTo = options.zTo != null ? options.zTo : 40;
			var scaleFrom = options.scaleFrom != null ? options.scaleFrom : 0.82;

			if (!ctx.gsap) return function () {};

			ctx.gsap.set(stage, {
				perspective: perspective,
				transformStyle: 'preserve-3d'
			});
			ctx.gsap.set(items, {
				transformOrigin: '50% 50%',
				transformStyle: 'preserve-3d',
				force3D: true
			});

			if (ctx.reducedMotion) {
				ctx.gsap.set(items, { clearProps: 'rotateY,rotateX,z,scale,opacity,x' });
				return function () {};
			}

			var tl = ctx.gsap.timeline({
				scrollTrigger: {
					trigger: el,
					start: options.start || 'top 70%',
					end: options.end || 'bottom 20%',
					scrub: scrub
				}
			});

			items.forEach(function (item, i) {
				var dir = i % 2 === 0 ? 1 : -1;
				var offset = (i - (items.length - 1) / 2) * 18;
				tl.fromTo(
					item,
					{
						rotateY: rotateY * dir,
						rotateX: rotateX,
						z: zFrom,
						x: offset * 2,
						scale: scaleFrom,
						opacity: 0.75
					},
					{
						rotateY: 0,
						rotateX: 0,
						z: zTo,
						x: 0,
						scale: 1,
						opacity: 1,
						ease: 'none'
					},
					i * 0.08
				);
			});

			return function () {
				if (tl.scrollTrigger) tl.scrollTrigger.kill();
				tl.kill();
			};
		}
	});
})(typeof window !== 'undefined' ? window : this);
