/**
 * Motion primitives + catalogue presets (source-tagged).
 * GSAPIFY-VERIFIED names map to maintainable first-party recipes — not copied minified bundles.
 */
(function (root) {
	'use strict';

	function register(id, def) {
		if (typeof root.NGT3DRegisterPreset === 'function') root.NGT3DRegisterPreset(id, def);
		else if (root.NGT3D && typeof root.NGT3D.registerAnimation === 'function') root.NGT3D.registerAnimation(id, def);
		else (root.NGT3DAnimations || (root.NGT3DAnimations = {}))[id] = def;
	}

	function composer() {
		return root.NGT3DTransformComposer || null;
	}

	function dirToXY(direction, distance) {
		var d = String(direction || 'up').toLowerCase();
		var dist = distance == null ? 48 : distance;
		if (d === 'up') return { x: 0, y: dist };
		if (d === 'down') return { x: 0, y: -dist };
		if (d === 'left') return { x: dist, y: 0 };
		if (d === 'right') return { x: -dist, y: 0 };
		return { x: 0, y: dist };
	}

	function killST(tween) {
		if (tween && tween.scrollTrigger) tween.scrollTrigger.kill();
		if (tween && typeof tween.kill === 'function') tween.kill();
	}

	/**
	 * Core primitive: fade + optional translate + scale + blur + rotate.
	 */
	function applyEntrance(el, options, ctx, recipe) {
		recipe = recipe || {};
		var gsap = ctx.gsap;
		if (!gsap) return function () {};

		var owner = recipe.id || 'entrance';
		var c = composer();
		var target = el;
		if (c && recipe.layer) target = c.ensureLayer(el, recipe.layer) || el;
		if (c) {
			['transform', 'opacity'].forEach(function (p) {
				var claim = c.claim(target, p, owner);
				if (!claim.ok && ctx.diagnostics) {
					ctx.diagnostics.log('warn', 'TRANSFORM_CONFLICT', claim.conflict);
					if (claim.conflict && claim.conflict.resolution === 'isolate-layer') {
						target = c.ensureLayer(el, 'text') || target;
						c.claim(target, p, owner);
					}
				}
			});
		}

		var duration = options.duration != null ? options.duration : recipe.duration != null ? recipe.duration : 0.85;
		var delay = options.delay != null ? options.delay : 0;
		var ease = options.ease || recipe.ease || 'power3.out';
		var xy = dirToXY(options.direction || recipe.direction, options.distance != null ? options.distance : recipe.distance);
		var from = {
			autoAlpha: recipe.opacityFrom != null ? recipe.opacityFrom : 0,
			x: recipe.x != null ? recipe.x : xy.x,
			y: recipe.y != null ? recipe.y : xy.y,
			scale: recipe.scaleFrom != null ? recipe.scaleFrom : options.scaleFrom != null ? options.scaleFrom : 1,
			rotation: recipe.rotation != null ? recipe.rotation : options.rotation || 0,
			rotationX: recipe.rotationX != null ? recipe.rotationX : options.rotationX || 0,
			rotationY: recipe.rotationY != null ? recipe.rotationY : options.rotationY || 0,
			filter: recipe.blur ? 'blur(' + recipe.blur + 'px)' : undefined,
			transformOrigin: recipe.transformOrigin || '50% 50%'
		};
		Object.keys(from).forEach(function (k) {
			if (from[k] === undefined) delete from[k];
		});

		var to = {
			autoAlpha: 1,
			x: 0,
			y: 0,
			scale: options.scaleTo != null ? options.scaleTo : 1,
			rotation: 0,
			rotationX: 0,
			rotationY: 0,
			filter: recipe.blur ? 'blur(0px)' : undefined,
			duration: duration,
			delay: delay,
			ease: ease,
			stagger: options.stagger
		};
		Object.keys(to).forEach(function (k) {
			if (to[k] === undefined) delete to[k];
		});

		if (ctx.reducedMotion) {
			gsap.set(target, { clearProps: 'transform,opacity,filter,visibility' });
			gsap.set(target, { autoAlpha: 1 });
			return function () {
				if (c) c.release(target, owner);
			};
		}

		var triggerType = (options.trigger && options.trigger.type) || recipe.trigger || 'viewport';
		var tween;

		if (triggerType === 'scroll' || triggerType === 'scrub' || triggerType === 'scroll-scrub') {
			to.scrollTrigger = {
				trigger: options.trigger && options.trigger.trigger ? options.trigger.trigger : el,
				start: (options.trigger && options.trigger.start) || options.start || 'top 80%',
				end: (options.trigger && options.trigger.end) || options.end || 'top 20%',
				scrub: triggerType.indexOf('scrub') !== -1 ? (options.scrub != null ? options.scrub : true) : false,
				toggleActions: options.toggleActions || 'play none none reverse',
				once: !!options.once
			};
			if (options.markers && ctx.cfg && ctx.cfg.debug) to.scrollTrigger.markers = true;
			tween = gsap.fromTo(target, from, to);
		} else if (triggerType === 'hover') {
			gsap.set(target, from);
			var enter = function () {
				gsap.to(target, Object.assign({}, to, { overwrite: 'auto' }));
			};
			var leave = function () {
				gsap.to(target, Object.assign({}, from, { duration: duration * 0.7, ease: ease, overwrite: 'auto' }));
			};
			el.addEventListener('pointerenter', enter);
			el.addEventListener('pointerleave', leave);
			return function () {
				el.removeEventListener('pointerenter', enter);
				el.removeEventListener('pointerleave', leave);
				if (c) c.release(target, owner);
			};
		} else {
			// viewport / load
			to.scrollTrigger = {
				trigger: el,
				start: (options.trigger && options.trigger.start) || options.start || 'top 85%',
				toggleActions: options.toggleActions || 'play none none none',
				once: options.once !== false
			};
			tween = gsap.fromTo(target, from, to);
		}

		return function () {
			killST(tween);
			if (c) c.release(target, owner);
		};
	}

	function applyStaggerChildren(el, options, ctx, recipe) {
		var gsap = ctx.gsap;
		if (!gsap) return function () {};
		var sel = options.childSelector || recipe.childSelector || ':scope > *';
		var kids;
		try {
			kids = Array.prototype.slice.call(el.querySelectorAll(sel));
		} catch (e) {
			kids = Array.prototype.slice.call(el.children || []);
		}
		if (!kids.length) return applyEntrance(el, options, ctx, recipe);

		var owner = recipe.id || 'stagger';
		var c = composer();
		if (c) c.claim(el, 'transform', owner);

		if (ctx.reducedMotion) {
			gsap.set(kids, { clearProps: 'transform,opacity,filter,visibility', autoAlpha: 1 });
			return function () {
				if (c) c.release(el, owner);
			};
		}

		var xy = dirToXY(options.direction || recipe.direction || 'up', options.distance != null ? options.distance : 36);
		var tween = gsap.fromTo(
			kids,
			{
				autoAlpha: 0,
				x: xy.x,
				y: xy.y,
				scale: recipe.scaleFrom != null ? recipe.scaleFrom : 0.96
			},
			{
				autoAlpha: 1,
				x: 0,
				y: 0,
				scale: 1,
				duration: options.duration != null ? options.duration : 0.7,
				ease: options.ease || 'power3.out',
				stagger: {
					each: options.stagger != null ? options.stagger : 0.08,
					from: options.staggerFrom || recipe.staggerFrom || 'start'
				},
				scrollTrigger: {
					trigger: el,
					start: options.start || 'top 80%',
					toggleActions: 'play none none none',
					once: options.once !== false
				}
			}
		);
		return function () {
			killST(tween);
			if (c) c.release(el, owner);
		};
	}

	function applyMagnetic(el, options, ctx) {
		if (ctx.reducedMotion) return function () {};
		if (root.matchMedia && root.matchMedia('(pointer: coarse)').matches) return function () {};
		var gsap = ctx.gsap;
		if (!gsap || typeof gsap.quickTo !== 'function') return function () {};
		var strength = options.strength != null ? options.strength : 0.35;
		var radius = options.radius != null ? options.radius : 120;
		var xTo = gsap.quickTo(el, 'x', { duration: 0.45, ease: 'power3' });
		var yTo = gsap.quickTo(el, 'y', { duration: 0.45, ease: 'power3' });
		var c = composer();
		if (c) c.claim(el, 'transform', 'magnetic');
		function onMove(e) {
			var r = el.getBoundingClientRect();
			var cx = r.left + r.width / 2;
			var cy = r.top + r.height / 2;
			var dx = e.clientX - cx;
			var dy = e.clientY - cy;
			var dist = Math.sqrt(dx * dx + dy * dy);
			if (dist > radius) {
				xTo(0);
				yTo(0);
				return;
			}
			xTo(dx * strength);
			yTo(dy * strength);
		}
		function onLeave() {
			xTo(0);
			yTo(0);
		}
		el.addEventListener('pointermove', onMove);
		el.addEventListener('pointerleave', onLeave);
		return function () {
			el.removeEventListener('pointermove', onMove);
			el.removeEventListener('pointerleave', onLeave);
			if (c) c.release(el, 'magnetic');
		};
	}

	function applyTextSplit(el, options, ctx, recipe) {
		var gsap = ctx.gsap;
		if (!gsap) return function () {};
		var pm = root.NGT3DPluginManager;
		var SplitText = pm && pm.resolve ? pm.resolve('SplitText') : root.SplitText;
		var mode = options.split || recipe.split || 'chars';
		var c = composer();
		var target = c ? c.ensureLayer(el, 'text') : el;
		var nodes = [];
		var splitInst = null;

		if (SplitText && typeof SplitText.create === 'function') {
			splitInst = SplitText.create(target, {
				type: mode === 'chars' ? 'chars,words' : mode,
				aria: 'auto'
			});
			nodes = mode.indexOf('char') !== -1 ? splitInst.chars : mode.indexOf('word') !== -1 ? splitInst.words : splitInst.lines;
		} else {
			// SYSTEM-ENHANCEMENT fallback — not labelled as SplitText
			var html = target.textContent || '';
			target.setAttribute('data-motion-text-fallback', '1');
			if (mode.indexOf('word') !== -1) {
				target.innerHTML = html
					.split(/(\s+)/)
					.map(function (w) {
						return /\s+/.test(w) ? w : '<span class="ngt-motion-word" style="display:inline-block">' + w + '</span>';
					})
					.join('');
				nodes = Array.prototype.slice.call(target.querySelectorAll('.ngt-motion-word'));
			} else {
				target.innerHTML = Array.prototype.map
					.call(html, function (ch) {
						if (ch === ' ') return ' ';
						return '<span class="ngt-motion-char" style="display:inline-block">' + ch + '</span>';
					})
					.join('');
				nodes = Array.prototype.slice.call(target.querySelectorAll('.ngt-motion-char'));
			}
		}

		if (!nodes.length) return function () {};
		if (ctx.reducedMotion) {
			gsap.set(nodes, { clearProps: 'all', autoAlpha: 1 });
			return function () {
				if (splitInst && splitInst.revert) splitInst.revert();
			};
		}

		var xy = dirToXY(options.direction || recipe.direction || 'up', options.distance != null ? options.distance : 24);
		var tween = gsap.fromTo(
			nodes,
			{
				autoAlpha: 0,
				y: xy.y,
				x: xy.x,
				rotationX: recipe.rotationX || 0,
				filter: recipe.blur ? 'blur(' + recipe.blur + 'px)' : undefined
			},
			{
				autoAlpha: 1,
				y: 0,
				x: 0,
				rotationX: 0,
				filter: recipe.blur ? 'blur(0px)' : undefined,
				duration: options.duration != null ? options.duration : 0.6,
				ease: options.ease || 'power3.out',
				stagger: options.stagger != null ? options.stagger : 0.03,
				scrollTrigger: {
					trigger: el,
					start: options.start || 'top 85%',
					toggleActions: 'play none none none',
					once: true
				}
			}
		);
		return function () {
			killST(tween);
			if (splitInst && splitInst.revert) splitInst.revert();
		};
	}

	function def(id, meta, applyFn) {
		register(
			id,
			Object.assign(
				{
					id: id,
					status: 'ready',
					apply: function (el, options, ctx) {
						return applyFn(el, options || {}, ctx);
					}
				},
				meta
			)
		);
	}

	// ── GSAP-OFFICIAL / SYSTEM primitives ─────────────────────────────────────
	var entrances = [
		['fade-in', { label: 'Fade In', source: 'GSAP-OFFICIAL', category: 'entrance', direction: null, opacityFrom: 0, distance: 0 }],
		['fade-up', { label: 'Fade Up', source: 'GSAP-OFFICIAL', category: 'entrance', direction: 'up' }],
		['fade-down', { label: 'Fade Down', source: 'GSAP-OFFICIAL', category: 'entrance', direction: 'down' }],
		['fade-left', { label: 'Fade Left', source: 'GSAP-OFFICIAL', category: 'entrance', direction: 'left' }],
		['fade-right', { label: 'Fade Right', source: 'GSAP-OFFICIAL', category: 'entrance', direction: 'right' }],
		['slide-up', { label: 'Slide Up', source: 'GSAP-OFFICIAL', category: 'entrance', direction: 'up', opacityFrom: 1 }],
		['slide-down', { label: 'Slide Down', source: 'GSAP-OFFICIAL', category: 'entrance', direction: 'down', opacityFrom: 1 }],
		['slide-left', { label: 'Slide Left', source: 'GSAP-OFFICIAL', category: 'entrance', direction: 'left', opacityFrom: 1 }],
		['slide-right', { label: 'Slide Right', source: 'GSAP-OFFICIAL', category: 'entrance', direction: 'right', opacityFrom: 1 }],
		['scale-in', { label: 'Scale In', source: 'GSAP-OFFICIAL', category: 'entrance', scaleFrom: 0.85, distance: 0 }],
		['zoom-in', { label: 'Zoom In', source: 'GSAP-OFFICIAL', category: 'entrance', scaleFrom: 0.7, distance: 0 }],
		['blur-in', { label: 'Blur In', source: 'SYSTEM-ENHANCEMENT', category: 'entrance', blur: 10, distance: 0 }],
		['blur-up', { label: 'Blur + Slide Up', source: 'SYSTEM-ENHANCEMENT', category: 'entrance', blur: 8, direction: 'up' }],
		['rotate-in', { label: 'Rotate In', source: 'GSAP-OFFICIAL', category: 'entrance', rotation: -12, distance: 0 }],
		['flip-in-x', { label: 'Flip In X', source: 'GSAP-OFFICIAL', category: 'entrance', rotationX: 75, distance: 0 }],
		['flip-in-y', { label: 'Flip In Y', source: 'GSAP-OFFICIAL', category: 'entrance', rotationY: 75, distance: 0 }],
		['depth-entrance', { label: 'Depth Entrance', source: 'SYSTEM-ENHANCEMENT', category: '3d', rotationX: 18, distance: 24 }],
		['elastic-pop', { label: 'Elastic Pop', source: 'SYSTEM-ENHANCEMENT', category: 'entrance', scaleFrom: 0.6, distance: 0, ease: 'elastic.out(1,0.5)' }]
	];

	entrances.forEach(function (pair) {
		var id = pair[0];
		var meta = pair[1];
		def(
			id,
			{
				title: meta.label,
				source: meta.source,
				category: meta.category,
				performanceCost: meta.blur ? 'medium' : 'low',
				supportsReducedMotion: true,
				supportsScrub: true,
				supportsStagger: true
			},
			function (el, options, ctx) {
				return applyEntrance(el, options, ctx, Object.assign({ id: id }, meta));
			}
		);
	});

	def(
		'stagger-children',
		{
			title: 'Stagger Children',
			source: 'GSAP-OFFICIAL',
			category: 'stagger',
			performanceCost: 'low'
		},
		function (el, options, ctx) {
			return applyStaggerChildren(el, options, ctx, { id: 'stagger-children', direction: 'up' });
		}
	);

	def(
		'magnetic-button',
		{ title: 'Magnetic Button', source: 'SYSTEM-ENHANCEMENT', category: 'button', performanceCost: 'medium' },
		applyMagnetic
	);

	def(
		'text-chars-rise',
		{ title: 'Character Rise', source: 'SYSTEM-ENHANCEMENT', category: 'text', performanceCost: 'medium' },
		function (el, options, ctx) {
			return applyTextSplit(el, options, ctx, { id: 'text-chars-rise', split: 'chars', direction: 'up' });
		}
	);
	def(
		'text-words-rise',
		{ title: 'Word Rise', source: 'SYSTEM-ENHANCEMENT', category: 'text', performanceCost: 'medium' },
		function (el, options, ctx) {
			return applyTextSplit(el, options, ctx, { id: 'text-words-rise', split: 'words', direction: 'up' });
		}
	);
	def(
		'text-blur-chars',
		{ title: 'Blur Characters', source: 'SYSTEM-ENHANCEMENT', category: 'text', performanceCost: 'high' },
		function (el, options, ctx) {
			return applyTextSplit(el, options, ctx, { id: 'text-blur-chars', split: 'chars', blur: 8, direction: 'up' });
		}
	);

	// ── GSAPIFY-VERIFIED mappings (observed names → recipes) ──────────────────
	var gsapifyMap = [
		['gsapify-fade-up-on-scroll', 'Fade Up on Scroll', { direction: 'up', trigger: 'viewport' }],
		['gsapify-card-hover-lift', 'Card Hover Lift', { direction: 'up', distance: 12, scaleFrom: 1, trigger: 'hover', opacityFrom: 1 }],
		['gsapify-card-slide-in-stagger', 'Card Slide-In Stagger', { staggerChildren: true, direction: 'up' }],
		['gsapify-clip-path-image-reveal', 'Clip-Path Image Reveal', { clip: true, direction: 'up' }],
		['gsapify-horizontal-scroll-section', 'Horizontal Scroll Section', { alias: 'horizontal-scroll' }],
		['gsapify-image-parallax-zoom', 'Image Parallax Zoom', { scaleFrom: 1.15, trigger: 'scrub' }],
		['gsapify-image-tilt-on-hover', 'Image Tilt on Hover', { alias: 'tilt-3d' }],
		['gsapify-ken-burns', 'Ken Burns Slideshow', { scaleFrom: 1, scaleTo: 1.08, trigger: 'scrub' }],
		['gsapify-kinetic-split-lines', 'Kinetic Split Lines', { text: 'words', direction: 'up' }],
		['gsapify-layered-zoom-scroll', 'Layered Zoom Scroll', { scaleFrom: 0.85, trigger: 'scrub' }],
		['gsapify-magnetic-button', 'Magnetic Button', { magnetic: true }],
		['gsapify-stacked-card-fan', 'Stacked Card Fan', { alias: 'cards-fan' }],
		['gsapify-stagger-letter-reveal', 'Stagger Letter Reveal', { text: 'chars' }],
		['gsapify-staggered-grid-reveal', 'Staggered Grid Reveal', { staggerChildren: true }],
		['gsapify-text-scramble', 'Text Scramble', { text: 'chars', blur: 2 }],
		['gsapify-typewriter', 'Typewriter', { text: 'chars', stagger: 0.04 }],
		['gsapify-tilt-parallax-card', 'Tilt Parallax Card', { alias: 'tilt-3d' }],
		['gsapify-vertical-card-stack', 'Vertical Card Stack', { alias: 'stack-3d' }],
		['gsapify-wobble-card-enter', 'Wobble Card Enter', { rotation: 6, direction: 'up', ease: 'elastic.out(1,0.4)' }],
		['gsapify-word-by-word-slide', 'Word-by-Word Slide', { text: 'words', direction: 'left' }],
		['gsapify-curtain-reveal', 'Curtain Reveal', { direction: 'up', distance: 80 }],
		['gsapify-grayscale-to-color', 'Grayscale to Color', { filterGray: true }],
		['gsapify-underline-slide', 'Underline Slide', { direction: 'left', distance: 40, opacityFrom: 1 }],
		['gsapify-count-up', 'Count-Up Numbers', { counter: true }],
		['gsapify-scroll-scrubbed-progress', 'Scroll-Scrubbed Progress', { trigger: 'scrub', scaleFrom: 0.2 }]
	];

	gsapifyMap.forEach(function (row) {
		var id = row[0];
		var label = row[1];
		var recipe = row[2];
		def(
			id,
			{
				title: label,
				source: 'GSAPIFY-VERIFIED',
				category: 'gsapify',
				referenceName: label,
				performanceCost: 'medium',
				supportsReducedMotion: true
			},
			function (el, options, ctx) {
				if (recipe.alias && root.NGT3DAnimations && root.NGT3DAnimations[recipe.alias]) {
					return root.NGT3DAnimations[recipe.alias].apply(el, options, ctx);
				}
				if (recipe.magnetic) return applyMagnetic(el, options, ctx);
				if (recipe.text) {
					return applyTextSplit(el, options, ctx, {
						id: id,
						split: recipe.text,
						direction: recipe.direction || 'up',
						blur: recipe.blur
					});
				}
				if (recipe.staggerChildren) {
					return applyStaggerChildren(el, options, ctx, { id: id, direction: recipe.direction || 'up' });
				}
				if (recipe.counter) {
					var gsap = ctx.gsap;
					if (!gsap) return function () {};
					var end = parseFloat(options.endValue != null ? options.endValue : el.getAttribute('data-count') || el.textContent) || 0;
					var obj = { v: 0 };
					var tween = gsap.to(obj, {
						v: end,
						duration: options.duration != null ? options.duration : 1.4,
						ease: 'power2.out',
						scrollTrigger: { trigger: el, start: 'top 85%', once: true },
						onUpdate: function () {
							el.textContent = Math.round(obj.v).toLocaleString();
						}
					});
					return function () {
						killST(tween);
					};
				}
				if (recipe.filterGray && ctx.gsap) {
					var tw = ctx.gsap.fromTo(
						el,
						{ filter: 'grayscale(1)' },
						{
							filter: 'grayscale(0)',
							ease: 'none',
							scrollTrigger: {
								trigger: el,
								start: 'top 80%',
								end: 'top 20%',
								scrub: true
							}
						}
					);
					return function () {
						killST(tw);
					};
				}
				return applyEntrance(
					el,
					options,
					ctx,
					Object.assign(
						{
							id: id,
							direction: recipe.direction || 'up',
							trigger: recipe.trigger || 'viewport',
							ease: recipe.ease,
							scaleFrom: recipe.scaleFrom,
							distance: recipe.distance,
							rotation: recipe.rotation,
							opacityFrom: recipe.opacityFrom
						},
						recipe
					)
				);
			}
		);
	});

	// Text-page GSAPify mappings
	[
		['gsapify-text-fade-up-words', 'Fade Up Words', 'words', 'up'],
		['gsapify-text-line-by-line', 'Line-by-Line Reveal', 'words', 'up'],
		['gsapify-text-blur-in', 'Blur In', 'chars', 'up'],
		['gsapify-text-slide-left', 'Slide From Left', 'words', 'left'],
		['gsapify-text-slide-right', 'Slide From Right', 'words', 'right'],
		['gsapify-text-staggered-letters', 'Staggered Letters', 'chars', 'up'],
		['gsapify-text-word-build', 'Word-by-Word Build', 'words', 'up'],
		['gsapify-text-scramble-decode', 'Scramble Decode', 'chars', 'up'],
		['gsapify-text-typewriter-effect', 'Typewriter Effect', 'chars', 'up']
	].forEach(function (row) {
		def(
			row[0],
			{
				title: row[1],
				source: 'GSAPIFY-VERIFIED',
				category: 'text',
				referenceName: row[1],
				performanceCost: 'medium'
			},
			function (el, options, ctx) {
				var o = Object.assign({}, options);
				if (row[0].indexOf('typewriter') !== -1) o.stagger = o.stagger != null ? o.stagger : 0.035;
				return applyTextSplit(el, o, ctx, { id: row[0], split: row[2], direction: row[3], blur: row[1] === 'Blur In' ? 8 : 0 });
			}
		);
	});

	root.NGT3DMotionPrimitives = {
		applyEntrance: applyEntrance,
		applyStaggerChildren: applyStaggerChildren,
		applyMagnetic: applyMagnetic,
		applyTextSplit: applyTextSplit
	};
})(typeof window !== 'undefined' ? window : this);
