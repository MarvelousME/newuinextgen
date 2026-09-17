/**
 * NGT 3D Scroll — Lenis smooth-scroll bridge.
 *
 * Wires a single Lenis instance into the GSAP ticker so ScrollTrigger and
 * Lenis agree on scroll position every frame (the documented GSAP+Lenis
 * integration pattern). Only runs when Settings → "Lenis smooth scroll" is
 * enabled AND the visitor has not asked for reduced motion — Lenis changes
 * the physical feel of scrolling, which prefers-reduced-motion visitors
 * should never get without opting in.
 *
 * Enqueued only when NGT3D_Dependency_Resolver reports needs_lenis, and only
 * after assets/vendor/lenis.min.js (globalThis.Lenis) has loaded.
 */
(function (root) {
	'use strict';

	var cfg = root.NGT3D || {};

	function prefersReducedMotion() {
		try {
			return root.matchMedia('(prefers-reduced-motion: reduce)').matches;
		} catch (e) {
			return false;
		}
	}

	function log(level, msg, meta) {
		if (root.console && typeof root.console[level] === 'function') {
			root.console[level]('[NGT3D:lenis]', msg, meta || '');
		}
	}

	var bridge = {
		instance: null,
		started: false,
		raf: null,
		tickerFn: null,

		/**
		 * Start the singleton Lenis instance. Safe to call more than once —
		 * later calls are no-ops while an instance is already running.
		 */
		start: function () {
			if (this.started) return this.instance;
			if (typeof root.Lenis !== 'function') {
				log('warn', 'Lenis library not loaded');
				return null;
			}
			if (!root.gsap) {
				log('warn', 'GSAP not loaded — cannot drive Lenis from the ticker');
				return null;
			}
			if (root.__NGT3D_LENIS__ && root.__NGT3D_LENIS__.destroyed !== true) {
				// Another instance (e.g. from a theme) already owns smooth scroll.
				this.instance = root.__NGT3D_LENIS__;
				this.started = true;
				return this.instance;
			}
			if (prefersReducedMotion()) {
				log('info', 'Reduced motion — smooth scroll left native');
				return null;
			}

			var opts = (cfg.lenisOptions && typeof cfg.lenisOptions === 'object') ? cfg.lenisOptions : {};

			/** Named easings the admin Settings screen offers; Lenis itself only takes a function. */
			var EASINGS = {
				linear: function (t) { return t; },
				'ease-out': function (t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)); },
				'ease-in-out': function (t) { return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2; }
			};
			var easingFn = EASINGS[opts.easing] || EASINGS['ease-out'];

			var lenis;
			try {
				lenis = new root.Lenis({
					duration: typeof opts.duration === 'number' ? opts.duration : 1.2,
					easing: easingFn,
					smoothWheel: opts.smoothWheel !== false,
					autoRaf: false
				});
			} catch (err) {
				log('error', 'Lenis init failed', { err: String(err) });
				return null;
			}

			var ScrollTrigger = root.ScrollTrigger;
			if (ScrollTrigger && typeof ScrollTrigger.update === 'function') {
				lenis.on('scroll', ScrollTrigger.update);
			}

			this.tickerFn = function (time) {
				// GSAP ticker time is in seconds; Lenis expects milliseconds.
				lenis.raf(time * 1000);
			};
			root.gsap.ticker.add(this.tickerFn);
			root.gsap.ticker.lagSmoothing(0);

			// Keep ScrollTrigger's pinning maths in sync with Lenis-driven scroll.
			if (ScrollTrigger && typeof ScrollTrigger.addEventListener === 'function') {
				ScrollTrigger.addEventListener('refresh', function () { lenis.resize(); });
			}

			root.__NGT3D_LENIS__ = lenis;
			this.instance = lenis;
			this.started = true;
			log('info', 'Started', { duration: opts.duration || 1.2 });
			return lenis;
		},

		/** Temporarily pause Lenis without tearing it down (e.g. modal open). */
		stop: function () {
			if (this.instance && typeof this.instance.stop === 'function') this.instance.stop();
		},

		/** Resume after stop(). */
		resume: function () {
			if (this.instance && typeof this.instance.start === 'function') this.instance.start();
		},

		/** Scroll to a target, delegating to Lenis when active, else native. */
		scrollTo: function (target, options) {
			if (this.instance && typeof this.instance.scrollTo === 'function') {
				this.instance.scrollTo(target, options || {});
				return;
			}
			if (typeof target === 'number') {
				root.scrollTo({ top: target, behavior: 'smooth' });
			} else if (target && target.scrollIntoView) {
				target.scrollIntoView({ behavior: 'smooth' });
			}
		},

		/** Fully tear down (rarely needed — NGT3D.update() calls this on reconfigure). */
		destroy: function () {
			if (this.tickerFn && root.gsap) {
				root.gsap.ticker.remove(this.tickerFn);
				this.tickerFn = null;
			}
			if (this.instance && typeof this.instance.destroy === 'function') {
				try { this.instance.destroy(); } catch (e) { /* isolated */ }
			}
			if (root.__NGT3D_LENIS__ === this.instance) {
				root.__NGT3D_LENIS__ = null;
			}
			this.instance = null;
			this.started = false;
		}
	};

	root.NGT3DLenis = bridge;

	if (cfg.lenis) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', function () { bridge.start(); });
		} else {
			bridge.start();
		}
	}
})(typeof window !== 'undefined' ? window : this);
