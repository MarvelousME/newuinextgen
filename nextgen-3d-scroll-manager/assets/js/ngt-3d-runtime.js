/**
 * NGT 3D Scroll — frontend runtime.
 * Registry/strategy pattern. Uses window.gsap + ScrollTrigger.
 */
(function (root) {
	'use strict';

	var cfg = root.NGT3D || {};
	var presets = root.NGT3DPresets || (root.NGT3DPresets = Object.create(null));
	var animations = root.NGT3DAnimations || (root.NGT3DAnimations = Object.create(null));
	var SHOWCASE_IDS = [
		'doublescroll', '4kvideo', 'wiper', 'dark-veles', 'scroll-mask',
		'onscroll', 'zoom', 'swag-card', 'transforms'
	];

	var state = {
		booted: false,
		destroyed: false,
		ctx: null,
		mm: null,
		instances: [],
		cleanups: [],
		loadedCss: Object.create(null),
		loadedJs: Object.create(null),
		pendingLoads: Object.create(null)
	};

	var diagnostics = {
		enabled: !!cfg.debug,
		events: [],
		unavailable: Object.create(null),
		errors: [],
		log: function (level, msg, meta) {
			if (!this.enabled && level !== 'error') return;
			var entry = { t: Date.now(), level: level, msg: String(msg), meta: meta || null };
			this.events.push(entry);
			if (this.events.length > 200) this.events.shift();
			if (root.console && typeof root.console[level === 'error' ? 'error' : 'debug'] === 'function') {
				root.console[level === 'error' ? 'error' : 'debug']('[NGT3D]', msg, meta || '');
			}
		},
		markUnavailable: function (id, reason) {
			this.unavailable[id] = { status: 'unavailable', reason: reason || '' };
			this.log('warn', id + ' unavailable', { reason: reason });
		},
		markDisabled: function (id, reason) {
			this.unavailable[id] = { status: 'disabled', reason: reason || '' };
			this.log('warn', id + ' disabled', { reason: reason });
		},
		snapshot: function () {
			return {
				version: cfg.version || null,
				page: cfg.page || null,
				rules: (cfg.rules || []).length,
				instances: state.instances.length,
				presets: Object.keys(presets),
				animations: Object.keys(animations),
				unavailable: this.unavailable,
				errors: this.errors.slice(),
				events: this.events.slice()
			};
		}
	};

	function prefersReducedMotion() {
		try {
			return root.matchMedia('(prefers-reduced-motion: reduce)').matches;
		} catch (e) {
			return false;
		}
	}

	function deviceMode(rule) {
		var w = root.innerWidth || 1200;
		if (w < 768) return rule.mobileMode || 'disabled';
		if (w < 1024) return rule.tabletMode || 'reduced';
		return rule.desktopMode || 'full';
	}

	function opt(options, key, fallback) {
		if (!options || options[key] === undefined || options[key] === null) return fallback;
		return options[key];
	}

	function qsAll(selector, rootEl) {
		try {
			return Array.prototype.slice.call((rootEl || document).querySelectorAll(selector));
		} catch (e) {
			diagnostics.log('error', 'Invalid selector', { selector: selector, err: String(e) });
			return [];
		}
	}

	function ensureGsap() {
		var gsap = root.gsap;
		if (!gsap) return null;
		var ST = root.ScrollTrigger || (gsap.plugins && gsap.plugins.ScrollTrigger);
		if (ST && typeof gsap.registerPlugin === 'function') {
			try { gsap.registerPlugin(ST); } catch (e) { /* already registered */ }
		}
		return { gsap: gsap, ScrollTrigger: root.ScrollTrigger || ST || null };
	}

	function setCssBridge(el) {
		if (!el) return;
		el.style.setProperty('--ngt-3d-perspective', String(cfg.perspective || 1200) + 'px');
		el.style.setProperty('--ngt-3d-version', '"' + String(cfg.version || '') + '"');
	}

	function markReady(el) {
		el.classList.add('ngt-3d');
		el.classList.add('ngt-3d-scene');
		el.classList.add('ngt-3d-ready');
	}

	function loadStylesheet(href, id) {
		if (state.loadedCss[id]) return Promise.resolve();
		if (state.pendingLoads[id]) return state.pendingLoads[id];
		state.pendingLoads[id] = new Promise(function (resolve) {
			var link = document.createElement('link');
			link.rel = 'stylesheet';
			link.href = href;
			link.id = 'ngt3d-css-' + id;
			link.onload = function () {
				state.loadedCss[id] = true;
				resolve();
			};
			link.onerror = function () {
				diagnostics.log('warn', 'CSS load failed', { id: id, href: href });
				resolve();
			};
			document.head.appendChild(link);
		});
		return state.pendingLoads[id];
	}

	function loadScript(src, id) {
		if (state.loadedJs[id] || presets[id]) return Promise.resolve(presets[id]);
		if (state.pendingLoads['js:' + id]) return state.pendingLoads['js:' + id];
		state.pendingLoads['js:' + id] = new Promise(function (resolve) {
			var s = document.createElement('script');
			s.src = src;
			s.async = true;
			s.onload = function () {
				state.loadedJs[id] = true;
				resolve(presets[id] || null);
			};
			s.onerror = function () {
				diagnostics.log('warn', 'Preset script load failed', { id: id, src: src });
				resolve(null);
			};
			document.head.appendChild(s);
		});
		return state.pendingLoads['js:' + id];
	}

	function pluginBase() {
		var url = cfg.pluginUrl || '';
		if (url && url.charAt(url.length - 1) !== '/') url += '/';
		return url;
	}

	function ensureShowcaseAssets(id) {
		if (SHOWCASE_IDS.indexOf(id) === -1) return Promise.resolve(presets[id] || null);
		var base = pluginBase();
		if (!base) return Promise.resolve(presets[id] || null);
		var cssP = loadStylesheet(base + 'assets/css/presets/ngt-3d-preset-' + id + '.css', id);
		var jsP = presets[id] ? Promise.resolve(presets[id]) : loadScript(base + 'assets/js/presets/ngt-3d-preset-' + id + '.js', id);
		return Promise.all([cssP, jsP]).then(function (pair) { return pair[1] || presets[id] || null; });
	}

	function registerPreset(id, definition) {
		if (!id || !definition) return;
		presets[String(id)] = definition;
		if (definition.status === 'unavailable') {
			diagnostics.markUnavailable(id, definition.reason || '');
		}
		if (definition.status === 'disabled') {
			diagnostics.markDisabled(id, definition.reason || '');
		}
	}

	function registerAnimation(id, definition) {
		if (!id || !definition) return;
		animations[String(id)] = definition;
	}

	function pushCleanup(fn) {
		if (typeof fn === 'function') state.cleanups.push(fn);
	}

	function killScrollTriggersFor(el) {
		var ST = root.ScrollTrigger;
		if (!ST || typeof ST.getAll !== 'function') return;
		ST.getAll().forEach(function (t) {
			if (t.trigger === el || t.pin === el || (t.vars && t.vars.trigger === el)) {
				t.kill();
			}
		});
	}

	/* ── Legacy animation strategies ─────────────────────────────────────── */

	function scrubVal(options, fallback) {
		var s = opt(options, 'scrub', fallback);
		if (s === false) return false;
		if (s === true) return true;
		return s;
	}

	function applyDepthScroll(el, options, ctx) {
		var y = opt(options, 'y', -80);
		if (ctx.mode === 'reduced') y = y * 0.4;
		return ctx.gsap.to(el, {
			y: y,
			ease: 'none',
			scrollTrigger: {
				trigger: el,
				start: 'top bottom',
				end: 'bottom top',
				scrub: scrubVal(options, 1)
			}
		});
	}

	function applyDepthHero(el, options, ctx) {
		var y = opt(options, 'y', -120);
		var persp = opt(options, 'perspective', cfg.perspective || 1200);
		el.style.perspective = persp + 'px';
		var layers = el.querySelectorAll('[data-depth], .ngt-3d-layer, .bi-cinematic-hero__bg, .ngi-hero__media');
		var targets = layers.length ? Array.prototype.slice.call(layers) : [el];
		targets.forEach(function (layer, i) {
			var rate = (i + 1) / (targets.length + 1);
			ctx.gsap.to(layer, {
				y: y * rate,
				ease: 'none',
				scrollTrigger: {
					trigger: el,
					start: 'top top',
					end: 'bottom top',
					scrub: scrubVal(options, 1.2)
				}
			});
		});
	}

	function applyParallax(el, options, ctx, defaultRate) {
		var rate = opt(options, 'rate', defaultRate);
		if (ctx.mode === 'reduced') rate *= 0.5;
		var dist = Math.round(120 * rate);
		ctx.gsap.to(el, {
			y: -dist,
			ease: 'none',
			scrollTrigger: {
				trigger: el,
				start: 'top bottom',
				end: 'bottom top',
				scrub: scrubVal(options, 1)
			}
		});
	}

	function applyPerspectiveReveal(el, options, ctx) {
		var rx = opt(options, 'rotateX', 6);
		var fromOp = opt(options, 'opacityFrom', 0.7);
		var toOp = opt(options, 'opacityTo', 1);
		var persp = opt(options, 'perspective', cfg.perspective || 1200);
		el.parentElement && (el.parentElement.style.perspective = persp + 'px');
		markReady(el);
		ctx.gsap.fromTo(el,
			{ rotateX: rx, opacity: fromOp, transformOrigin: 'center top' },
			{
				rotateX: 0,
				opacity: toOp,
				ease: 'none',
				scrollTrigger: {
					trigger: el,
					start: 'top 85%',
					end: 'top 35%',
					scrub: scrubVal(options, 0.8)
				}
			}
		);
	}

	function applyPerspectiveExit(el, options, ctx) {
		var scaleTo = opt(options, 'scaleTo', 0.92);
		var rx = opt(options, 'rotateX', 4);
		var opacityTo = opt(options, 'opacityTo', 0.6);
		markReady(el);
		ctx.gsap.to(el, {
			scale: scaleTo,
			rotateX: rx,
			opacity: opacityTo,
			transformOrigin: 'center center',
			ease: 'none',
			scrollTrigger: {
				trigger: el,
				start: 'top top',
				end: 'bottom top',
				scrub: scrubVal(options, 1.2)
			}
		});
	}

	function applyRotateScroll(el, options, ctx, axis) {
		var deg = opt(options, axis === 'x' ? 'rotateX' : 'rotateY', 15);
		var props = { ease: 'none', scrollTrigger: {
			trigger: el, start: 'top bottom', end: 'bottom top', scrub: scrubVal(options, 1)
		} };
		props[axis === 'x' ? 'rotateX' : 'rotateY'] = deg;
		ctx.gsap.fromTo(el,
			axis === 'x' ? { rotateX: -deg } : { rotateY: -deg },
			props
		);
	}

	function applyScaleDepth(el, options, ctx) {
		var from = opt(options, 'scaleFrom', 0.92);
		var to = opt(options, 'scaleTo', 1);
		markReady(el);
		ctx.gsap.fromTo(el,
			{ scale: from },
			{
				scale: to,
				ease: 'none',
				scrollTrigger: {
					trigger: el,
					start: 'top 90%',
					end: 'top 40%',
					scrub: scrubVal(options, 1)
				}
			}
		);
	}

	function applyTranslateZ(el, options, ctx) {
		var z = opt(options, 'z', 80);
		var persp = opt(options, 'perspective', 1000);
		if (el.parentElement) el.parentElement.style.perspective = persp + 'px';
		ctx.gsap.fromTo(el,
			{ z: -z * 0.25 },
			{
				z: z,
				ease: 'none',
				scrollTrigger: {
					trigger: el,
					start: 'top bottom',
					end: 'center center',
					scrub: scrubVal(options, 1)
				}
			}
		);
	}

	function childCards(el) {
		var kids = el.querySelectorAll('.ngi-card, .bi-stack-3d__card, [data-ngt-card], article, .card');
		return kids.length ? Array.prototype.slice.call(kids) : Array.prototype.slice.call(el.children);
	}

	function applyStack3d(el, options, ctx) {
		var cards = childCards(el);
		if (!cards.length) return;
		var persp = opt(options, 'perspective', 1000);
		el.style.perspective = persp + 'px';
		markReady(el);
		var tl = ctx.gsap.timeline({
			scrollTrigger: {
				trigger: el,
				start: 'top top',
				end: opt(options, 'end', '+=200%'),
				scrub: scrubVal(options, 1.5),
				pin: !!opt(options, 'pin', true),
				pinSpacing: true
			}
		});
		cards.forEach(function (card, i) {
			tl.fromTo(card,
				{ z: -80 - i * 40, y: 40 + i * 12, rotateX: 8, opacity: 0.65 },
				{ z: 0, y: 0, rotateX: 0, opacity: 1, ease: 'none' },
				0
			);
		});
	}

	function applyCardsFan(el, options, ctx) {
		var cards = childCards(el);
		if (!cards.length) return;
		markReady(el);
		var stagger = opt(options, 'stagger', 0.08);
		ctx.gsap.fromTo(cards,
			{ rotateY: -28, z: -60, opacity: 0.7, transformOrigin: 'center left' },
			{
				rotateY: 0,
				z: 0,
				opacity: 1,
				stagger: stagger,
				ease: 'none',
				scrollTrigger: {
					trigger: el,
					start: 'top 75%',
					end: 'bottom 40%',
					scrub: scrubVal(options, 1.2)
				}
			}
		);
	}

	function applyStaggerDepth(el, options, ctx) {
		var kids = childCards(el);
		if (!kids.length) kids = Array.prototype.slice.call(el.children);
		if (!kids.length) return;
		markReady(el);
		var scrub = scrubVal(options, false);
		var vars = {
			rotateX: 0,
			y: 0,
			opacity: 1,
			stagger: opt(options, 'stagger', 0.1),
			duration: opt(options, 'duration', 0.8),
			ease: scrub ? 'none' : 'power2.out'
		};
		if (scrub) {
			vars.scrollTrigger = { trigger: el, start: 'top 80%', end: 'top 30%', scrub: scrub };
		} else {
			vars.scrollTrigger = { trigger: el, start: 'top 85%', toggleActions: 'play none none none' };
		}
		ctx.gsap.fromTo(kids,
			{ rotateX: opt(options, 'rotateX', 10), y: opt(options, 'y', 30), opacity: 0.85 },
			vars
		);
	}

	function applyPinSection(el, options, ctx) {
		ctx.ScrollTrigger.create({
			trigger: el,
			start: 'top top',
			end: opt(options, 'end', '+=300%'),
			pin: !!opt(options, 'pin', true),
			pinSpacing: opt(options, 'pinSpacing', true) !== false,
			scrub: scrubVal(options, 1)
		});
	}

	function applyHorizontalScroll(el, options, ctx) {
		var track = el.querySelector('.ngt-3d-htrack, .ngi-track, [data-ngt-htrack]') || el;
		var items = track.children;
		if (!items.length) return;
		var total = 0;
		Array.prototype.forEach.call(items, function (n) { total += n.offsetWidth || 0; });
		var distance = Math.max(total - el.offsetWidth, el.offsetWidth * 0.5);
		markReady(el);
		ctx.gsap.to(track, {
			x: -distance,
			ease: 'none',
			scrollTrigger: {
				trigger: el,
				start: 'top top',
				end: function () { return '+=' + distance; },
				scrub: scrubVal(options, 1),
				pin: !!opt(options, 'pin', true),
				anticipatePin: 1
			}
		});
	}

	function applyImageDepth(el, options, ctx) {
		var img = el.tagName === 'IMG' ? el : el.querySelector('img');
		if (!img) img = el;
		applyParallax(img, options, ctx, opt(options, 'rate', 0.3));
	}

	function applyImageReveal3d(el, options, ctx) {
		var target = el.tagName === 'IMG' ? el : (el.querySelector('img') || el);
		markReady(el);
		if (ctx.reducedMotion) {
			ctx.gsap.set(target, { clearProps: 'clipPath,rotateX,opacity' });
			return;
		}
		ctx.gsap.fromTo(target,
			{ clipPath: 'inset(0 100% 0 0)', rotateX: opt(options, 'rotateX', 8), opacity: 0.9 },
			{
				clipPath: 'inset(0 0% 0 0)',
				rotateX: 0,
				opacity: 1,
				duration: opt(options, 'duration', 1.2),
				ease: 'power2.out',
				scrollTrigger: {
					trigger: el,
					start: 'top 80%',
					toggleActions: 'play none none none'
				}
			}
		);
	}

	function applyTextDepth(el, options, ctx) {
		var z = opt(options, 'z', 40);
		if (el.parentElement) el.parentElement.style.perspective = (cfg.perspective || 1200) + 'px';
		ctx.gsap.fromTo(el,
			{ z: -z * 0.5 },
			{
				z: z,
				ease: 'none',
				scrollTrigger: {
					trigger: el,
					start: 'top bottom',
					end: 'center center',
					scrub: scrubVal(options, 1)
				}
			}
		);
	}

	function applyTextPerspective(el, options, ctx) {
		markReady(el);
		if (ctx.reducedMotion) {
			ctx.gsap.set(el, { clearProps: 'rotateX,y,opacity' });
			return;
		}
		ctx.gsap.fromTo(el,
			{ rotateX: opt(options, 'rotateX', 12), y: opt(options, 'y', 20), opacity: 0.85 },
			{
				rotateX: 0,
				y: 0,
				opacity: 1,
				duration: opt(options, 'duration', 0.9),
				ease: 'power2.out',
				scrollTrigger: {
					trigger: el,
					start: 'top 85%',
					toggleActions: 'play none none none'
				}
			}
		);
	}

	function applySectionCinematic(el, options, ctx) {
		markReady(el);
		ctx.gsap.fromTo(el,
			{ scale: opt(options, 'scaleFrom', 1.05), opacity: opt(options, 'opacityFrom', 0.5) },
			{
				scale: opt(options, 'scaleTo', 1),
				opacity: 1,
				ease: 'none',
				scrollTrigger: {
					trigger: el,
					start: 'top 90%',
					end: 'top 30%',
					scrub: scrubVal(options, 1)
				}
			}
		);
	}

	function applyCarouselDepth(el, options, ctx) {
		ctx.gsap.to(el, {
			y: opt(options, 'y', -20),
			ease: 'none',
			scrollTrigger: {
				trigger: el,
				start: 'top bottom',
				end: 'bottom top',
				scrub: scrubVal(options, 0.6)
			}
		});
	}

	function applyMouseParallax(el, options, ctx) {
		var depth = opt(options, 'depth', 20);
		var raf = 0;
		function onMove(e) {
			cancelAnimationFrame(raf);
			raf = requestAnimationFrame(function () {
				var rect = el.getBoundingClientRect();
				if (!rect.width || !rect.height) return;
				var x = ((e.clientX - rect.left) / rect.width - 0.5) * 2;
				var y = ((e.clientY - rect.top) / rect.height - 0.5) * 2;
				el.style.setProperty('--ngt-3d-mx', String(x));
				el.style.setProperty('--ngt-3d-my', String(y));
				var layers = el.querySelectorAll('[data-ngt-parallax], .ngt-3d-mouse-layer');
				if (!layers.length && !el.hasAttribute('data-bi-tilt') && !el.querySelector('[data-bi-tilt]')) {
					el.style.transform = 'translate3d(' + (x * depth) + 'px,' + (y * depth) + 'px,0)';
				} else {
					Array.prototype.forEach.call(layers, function (layer) {
						var d = parseFloat(layer.getAttribute('data-ngt-parallax') || '1', 10);
						layer.style.transform = 'translate3d(' + (x * depth * d) + 'px,' + (y * depth * d) + 'px,0)';
					});
				}
			});
		}
		function onLeave() {
			cancelAnimationFrame(raf);
			el.style.removeProperty('--ngt-3d-mx');
			el.style.removeProperty('--ngt-3d-my');
			if (!el.hasAttribute('data-bi-tilt')) el.style.transform = '';
			var layers = el.querySelectorAll('[data-ngt-parallax], .ngt-3d-mouse-layer');
			Array.prototype.forEach.call(layers, function (layer) { layer.style.transform = ''; });
		}
		el.addEventListener('mousemove', onMove);
		el.addEventListener('mouseleave', onLeave);
		pushCleanup(function () {
			el.removeEventListener('mousemove', onMove);
			el.removeEventListener('mouseleave', onLeave);
			onLeave();
		});
	}

	function applyTilt3d(el, options, ctx) {
		var max = opt(options, 'max', 10);
		var hosts = [];
		if (el.hasAttribute('data-bi-tilt') || el.getAttribute('data-ngt-tilt-host') === '1') {
			hosts = [el];
		} else {
			hosts = qsAll('[data-bi-tilt], .bi-tilt-3d', el);
			if (!hosts.length) hosts = [el];
		}
		hosts.forEach(function (host) {
			var hasBi = host.hasAttribute('data-bi-tilt') || host.classList.contains('bi-tilt-3d');
			var raf = 0;
			function onMove(e) {
				cancelAnimationFrame(raf);
				raf = requestAnimationFrame(function () {
					var rect = host.getBoundingClientRect();
					if (!rect.width || !rect.height) return;
					var x = (e.clientX - rect.left) / rect.width;
					var y = (e.clientY - rect.top) / rect.height;
					var rotY = (x - 0.5) * max * 2;
					var rotX = (0.5 - y) * max * 2;
					host.style.setProperty('--ngt-3d-tilt-rx', rotX.toFixed(2) + 'deg');
					host.style.setProperty('--ngt-3d-tilt-ry', rotY.toFixed(2) + 'deg');
					host.style.setProperty('--ngt-3d-tilt-x', (x * 100).toFixed(1) + '%');
					host.style.setProperty('--ngt-3d-tilt-y', (y * 100).toFixed(1) + '%');
					if (!hasBi) {
						var inner = host.querySelector('.ngt-3d-tilt-inner') || host;
						inner.style.transform =
							'perspective(900px) rotateX(' + rotX + 'deg) rotateY(' + rotY + 'deg) translateZ(6px)';
					}
				});
			}
			function reset() {
				cancelAnimationFrame(raf);
				host.style.removeProperty('--ngt-3d-tilt-rx');
				host.style.removeProperty('--ngt-3d-tilt-ry');
				host.style.removeProperty('--ngt-3d-tilt-x');
				host.style.removeProperty('--ngt-3d-tilt-y');
				if (!hasBi) {
					var inner = host.querySelector('.ngt-3d-tilt-inner') || host;
					if (inner !== host || !hasBi) inner.style.transform = '';
				}
			}
			host.addEventListener('mousemove', onMove);
			host.addEventListener('mouseleave', reset);
			pushCleanup(function () {
				host.removeEventListener('mousemove', onMove);
				host.removeEventListener('mouseleave', reset);
				reset();
			});
		});
	}

	function applyAtropos(el, options, ctx) {
		var Atropos = root.Atropos;
		if (!Atropos) {
			diagnostics.log('warn', 'Atropos missing');
			return;
		}
		var targets = el.classList.contains('atropos') ? [el] : qsAll('.atropos', el);
		if (!targets.length) {
			el.classList.add('atropos');
			targets = [el];
		}
		targets.forEach(function (t) {
			try {
				var inst = Atropos({
					el: t,
					activeOffset: opt(options, 'activeOffset', 40),
					shadowScale: opt(options, 'shadowScale', 1.05)
				});
				pushCleanup(function () {
					if (inst && typeof inst.destroy === 'function') inst.destroy();
				});
			} catch (err) {
				diagnostics.log('error', 'Atropos init failed', { err: String(err) });
			}
		});
	}

	function applyWebgl(kind, el, options, ctx) {
		var eng = root.NGT3DWebGL;
		if (!eng || typeof eng[kind] !== 'function') {
			diagnostics.log('warn', 'WebGL engine missing for ' + kind);
			return;
		}
		try {
			var destroy = eng[kind](el, options, ctx);
			if (typeof destroy === 'function') pushCleanup(destroy);
		} catch (err) {
			diagnostics.log('error', 'WebGL ' + kind + ' failed', { err: String(err) });
		}
	}

	function bootLegacyRegistry() {
		var map = {
			'depth-scroll': function (el, o, c) { applyDepthScroll(el, o, c); },
			'depth-hero': function (el, o, c) { applyDepthHero(el, o, c); },
			'parallax-slow': function (el, o, c) { applyParallax(el, o, c, 0.2); },
			'parallax-medium': function (el, o, c) { applyParallax(el, o, c, 0.4); },
			'parallax-fast': function (el, o, c) { applyParallax(el, o, c, 0.6); },
			'perspective-reveal': function (el, o, c) { applyPerspectiveReveal(el, o, c); },
			'perspective-exit': function (el, o, c) { applyPerspectiveExit(el, o, c); },
			'rotate-x-scroll': function (el, o, c) { applyRotateScroll(el, o, c, 'x'); },
			'rotate-y-scroll': function (el, o, c) { applyRotateScroll(el, o, c, 'y'); },
			'scale-depth': function (el, o, c) { applyScaleDepth(el, o, c); },
			'translate-z': function (el, o, c) { applyTranslateZ(el, o, c); },
			'stack-3d': function (el, o, c) { applyStack3d(el, o, c); },
			'cards-fan': function (el, o, c) { applyCardsFan(el, o, c); },
			'stagger-depth': function (el, o, c) { applyStaggerDepth(el, o, c); },
			'pin-section': function (el, o, c) { applyPinSection(el, o, c); },
			'horizontal-scroll': function (el, o, c) { applyHorizontalScroll(el, o, c); },
			'image-depth': function (el, o, c) { applyImageDepth(el, o, c); },
			'image-reveal-3d': function (el, o, c) { applyImageReveal3d(el, o, c); },
			'text-depth': function (el, o, c) { applyTextDepth(el, o, c); },
			'text-perspective': function (el, o, c) { applyTextPerspective(el, o, c); },
			'section-cinematic': function (el, o, c) { applySectionCinematic(el, o, c); },
			'carousel-depth': function (el, o, c) { applyCarouselDepth(el, o, c); },
			'mouse-parallax': function (el, o, c) { applyMouseParallax(el, o, c); },
			'tilt-3d': function (el, o, c) { applyTilt3d(el, o, c); },
			'atropos-depth': function (el, o, c) { applyAtropos(el, o, c); },
			'webgl-distortion': function (el, o, c) { applyWebgl('distortion', el, o, c); },
			'webgl-image-reveal': function (el, o, c) { applyWebgl('imageReveal', el, o, c); }
		};
		Object.keys(map).forEach(function (id) {
			if (!animations[id]) {
				registerAnimation(id, { id: id, apply: map[id] });
			}
		});
	}

	function resolveStrategy(name) {
		if (presets[name] && typeof presets[name].apply === 'function') return { type: 'preset', def: presets[name] };
		if (animations[name] && typeof animations[name].apply === 'function') return { type: 'animation', def: animations[name] };
		return null;
	}

	function runStrategy(name, el, options, ctx) {
		var strat = resolveStrategy(name);
		if (!strat) {
			diagnostics.log('warn', 'Unknown animation', { name: name });
			return;
		}
		if (strat.def.status === 'unavailable' || strat.def.status === 'disabled') {
			diagnostics.log('warn', name + ' ' + strat.def.status + ' (stub no-op)');
		}
		try {
			var result = strat.def.apply(el, options || {}, ctx);
			if (typeof result === 'function') pushCleanup(result);
			else if (result && typeof result.destroy === 'function') pushCleanup(function () { result.destroy(); });
			state.instances.push({ id: name, el: el, type: strat.type });
		} catch (err) {
			diagnostics.errors.push({ name: name, err: String(err) });
			diagnostics.log('error', 'Strategy failed: ' + name, { err: String(err) });
		}
	}

	function applyRule(rule, libs) {
		var mode = deviceMode(rule);
		if (mode === 'disabled') return;

		var reduced = prefersReducedMotion();
		cfg.reducedMotion = reduced;

		var els = qsAll(rule.selector);
		if (!els.length) {
			diagnostics.log('warn', 'TARGET_NOT_FOUND', { selector: rule.selector, ruleId: rule.id });
			return;
		}

		var options = rule.options || {};
		var names = rule.animations || [];
		var classes = rule.styleClasses || [];

		els.forEach(function (el) {
			try {
				setCssBridge(el);
				classes.forEach(function (c) {
					if (c) el.classList.add(c);
				});
				el.classList.add('ngt-3d');
				el.classList.add('ngt-3d-scene');
				/* Ready BEFORE initial animated state — never permanently hide. */
				el.classList.add('ngt-3d-ready');

				if (reduced && mode !== 'full') {
					diagnostics.log('info', 'Reduced motion — skip scrub for rule ' + rule.id);
					return;
				}

				var ctx = {
					gsap: libs.gsap,
					ScrollTrigger: libs.ScrollTrigger,
					mode: reduced ? 'reduced' : mode,
					reducedMotion: reduced,
					perspective: cfg.perspective || 1200,
					rule: rule,
					diagnostics: diagnostics,
					cfg: cfg,
					plugins: root.NGT3DPluginManager ? root.NGT3DPluginManager.snapshot() : null
				};

				/* Schema v2 effect stack: options.effects[{preset,settings,trigger}] */
				var stack = Array.isArray(options.effects) ? options.effects : null;
				if (stack && stack.length) {
					stack.forEach(function (item, index) {
						if (!item || !item.preset) return;
						var merged = Object.assign({}, options, item.settings || {}, {
							trigger: item.trigger || options.trigger,
							_stackIndex: index
						});
						delete merged.effects;
						runStrategy(item.preset, el, merged, ctx);
					});
				} else {
					names.forEach(function (name) {
						runStrategy(name, el, options, ctx);
					});
				}

				if (root.NGT3DTransformComposer && typeof root.NGT3DTransformComposer.warningsFor === 'function') {
					var warns = root.NGT3DTransformComposer.warningsFor(el);
					if (warns && warns.length && diagnostics.enabled) {
						diagnostics.log('warn', 'Effect stack conflicts', { selector: rule.selector, warns: warns });
					}
				}
			} catch (err) {
				diagnostics.errors.push({ ruleId: rule.id, err: String(err) });
				diagnostics.log('error', 'Rule failed', { ruleId: rule.id, err: String(err) });
			}
		});
	}

	function collectNeededShowcase(rules) {
		var need = [];
		function add(name) {
			if (SHOWCASE_IDS.indexOf(name) !== -1 && need.indexOf(name) === -1) need.push(name);
		}
		(rules || []).forEach(function (rule) {
			(rule.animations || []).forEach(add);
			var effects = rule.options && rule.options.effects;
			if (Array.isArray(effects)) {
				effects.forEach(function (item) {
					if (item && item.preset) add(item.preset);
				});
			}
		});
		return need;
	}

	function destroy() {
		state.destroyed = true;
		while (state.cleanups.length) {
			try { state.cleanups.pop()(); } catch (e) { /* isolated */ }
		}
		if (state.ctx && typeof state.ctx.revert === 'function') {
			try { state.ctx.revert(); } catch (e2) { /* isolated */ }
		}
		if (state.mm && typeof state.mm.revert === 'function') {
			try { state.mm.revert(); } catch (e3) { /* isolated */ }
		}
		state.instances = [];
		state.ctx = null;
		state.mm = null;
		state.booted = false;
		diagnostics.log('info', 'Destroyed');
	}

	function refresh() {
		if (root.ScrollTrigger && typeof root.ScrollTrigger.refresh === 'function') {
			root.ScrollTrigger.refresh();
		}
		diagnostics.log('info', 'Refreshed');
	}

	function update(nextCfg) {
		if (nextCfg && typeof nextCfg === 'object') {
			Object.keys(nextCfg).forEach(function (k) { cfg[k] = nextCfg[k]; });
			root.NGT3D = cfg;
		}
		destroy();
		state.destroyed = false;
		return init();
	}

	function mountRules(libs) {
		var rules = cfg.rules || [];
		if (!rules.length) {
			diagnostics.log('info', 'No rules');
			return;
		}
		document.documentElement.classList.add('ngt-3d');
		document.body && document.body.classList.add('ngt-3d');
		setCssBridge(document.documentElement);

		if (libs.gsap && typeof libs.gsap.context === 'function') {
			state.ctx = libs.gsap.context(function () {
				rules.forEach(function (rule) { applyRule(rule, libs); });
			});
		} else {
			rules.forEach(function (rule) { applyRule(rule, libs); });
		}

		if (libs.gsap && typeof libs.gsap.matchMedia === 'function') {
			state.mm = libs.gsap.matchMedia();
			state.mm.add('(prefers-reduced-motion: reduce)', function () {
				cfg.reducedMotion = true;
				return function () { cfg.reducedMotion = prefersReducedMotion(); };
			});
		}

		refresh();
		state.booted = true;
		diagnostics.log('info', 'Booted', { instances: state.instances.length });
	}

	function init() {
		if (state.booted) return Promise.resolve(api);
		bootLegacyRegistry();

		var libs = ensureGsap();
		if (!libs) {
			diagnostics.log('error', 'GSAP not found on window.gsap');
			return Promise.resolve(api);
		}

		var needed = collectNeededShowcase(cfg.rules || []);
		var loads = needed.map(function (id) { return ensureShowcaseAssets(id); });

		return Promise.all(loads).then(function () {
			if (state.destroyed) return api;
			mountRules(libs);
			return api;
		});
	}

	var api = cfg;
	api.registerPreset = registerPreset;
	api.registerAnimation = registerAnimation;
	api.init = init;
	api.update = update;
	api.refresh = refresh;
	api.destroy = destroy;
	api.onPresetRegistered = function (id, def) {
		diagnostics.log('info', 'Preset registered', { id: id });
		if (def && (def.status === 'unavailable' || def.status === 'disabled')) {
			if (def.status === 'unavailable') diagnostics.markUnavailable(id, def.reason);
			else diagnostics.markDisabled(id, def.reason);
		}
	};
	api.onAnimationRegistered = function (id) {
		diagnostics.log('info', 'Animation registered', { id: id });
	};

	root.NGT3D = api;
	if (cfg.debug) {
		root.NGT3DDiagnostics = diagnostics;
	}

	/* Flush any presets queued before runtime. */
	var q = root.__NGT3D_PRESET_QUEUE__ || [];
	q.forEach(function (item) {
		if (item && item.id) registerPreset(item.id, item.definition);
	});
	root.__NGT3D_PRESET_QUEUE__ = [];

	function autoStart() {
		init().catch(function (err) {
			diagnostics.log('error', 'Init failed', { err: String(err) });
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', autoStart);
	} else {
		autoStart();
	}
})(typeof window !== 'undefined' ? window : this);
