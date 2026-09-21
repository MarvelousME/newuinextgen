/**
 * Showcase preset: swag-card
 * Evidence: Swag sticky card stack recreation —
 * https://swag-card.learnframer.site/ and Framer University card-on-scroll.
 * Cards stay sticky and peel / stack with scroll (y, scale, rotateX).
 */
(function (root) {
	'use strict';

	function register(id, def) {
		if (typeof root.NGT3DRegisterPreset === 'function') root.NGT3DRegisterPreset(id, def);
		else if (root.NGT3D && typeof root.NGT3D.registerPreset === 'function') root.NGT3D.registerPreset(id, def);
		else (root.NGT3DPresets || (root.NGT3DPresets = {}))[id] = def;
	}

	register('swag-card', {
		id: 'swag-card',
		title: 'Swag Card Stack',
		status: 'ready',
		apply: function (el, options, ctx) {
			el.classList.add('ngt-3d-preset--swag-card');
			el.classList.add('ngt-3d-ready');
			el.removeAttribute('data-ngt-preset-status');

			var stage = el.querySelector('.ngt-3d-swag__stage, [data-ngt-swag-stage]') || el;
			var cards = el.querySelectorAll('.ngt-3d-swag__card, [data-ngt-swag-card]');
			if (!cards.length) {
				cards = stage.querySelectorAll('article, .ngt-3d-demo__card, figure');
			}
			cards = Array.prototype.slice.call(cards);
			if (!cards.length) return function () {};

			var scrub = options.scrub !== undefined ? options.scrub : 1;
			var perspective = options.perspective != null ? options.perspective : 1400;
			var rise = options.rise != null ? options.rise : 120;
			var scaleTo = options.scaleTo != null ? options.scaleTo : 0.88;
			var rotateX = options.rotateX != null ? options.rotateX : -14;

			ctx.gsap && ctx.gsap.set(stage, { perspective: perspective, transformStyle: 'preserve-3d' });
			ctx.gsap && ctx.gsap.set(cards, { transformOrigin: '50% 100%', force3D: true });

			if (!ctx.gsap || ctx.reducedMotion) {
				if (ctx.gsap) ctx.gsap.set(cards, { clearProps: 'y,scale,rotateX,z,opacity' });
				return function () {};
			}

			var tl = ctx.gsap.timeline({
				scrollTrigger: {
					trigger: el,
					start: options.start || 'top top',
					end: options.end || ('+=' + Math.max(220, cards.length * 90) + '%'),
					scrub: scrub,
					pin: options.pin !== false,
					anticipatePin: 1
				}
			});

			cards.forEach(function (card, i) {
				ctx.gsap.set(card, { zIndex: cards.length - i });
				if (i === 0) return;
				var slot = i / Math.max(1, cards.length - 1);
				tl.fromTo(
					card,
					{ y: rise + i * 28, scale: 1 - slot * 0.04, rotateX: rotateX * 0.35, opacity: 0.96 },
					{ y: 0, scale: 1, rotateX: 0, opacity: 1, ease: 'none' },
					0
				);
				tl.to(
					cards[i - 1],
					{
						y: -rise * 0.55,
						scale: scaleTo,
						rotateX: rotateX,
						opacity: 0.55,
						ease: 'none'
					},
					slot * 0.85
				);
			});

			return function () {
				if (tl.scrollTrigger) tl.scrollTrigger.kill();
				tl.kill();
			};
		}
	});
})(typeof window !== 'undefined' ? window : this);
