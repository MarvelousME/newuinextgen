/**
 * NGT3D Plugin Manager — detect available GSAP plugins; never invent Club APIs.
 */
(function (root) {
	'use strict';

	var KNOWN = [
		'ScrollTrigger',
		'ScrollSmoother',
		'SplitText',
		'ScrambleTextPlugin',
		'TextPlugin',
		'DrawSVGPlugin',
		'MorphSVGPlugin',
		'MotionPathPlugin',
		'Flip',
		'Draggable',
		'InertiaPlugin',
		'Observer',
		'Physics2DPlugin',
		'PhysicsPropsPlugin',
		'CustomEase',
		'CustomBounce',
		'CustomWiggle'
	];

	function resolve(name) {
		if (root[name]) return root[name];
		var gsap = root.gsap;
		if (!gsap) return null;
		if (gsap.plugins && gsap.plugins[name]) return gsap.plugins[name];
		// common aliases
		var aliases = {
			ScrambleTextPlugin: ['ScrambleText', 'scrambleText'],
			TextPlugin: ['Text', 'text'],
			DrawSVGPlugin: ['DrawSVG', 'drawSVG'],
			MorphSVGPlugin: ['MorphSVG', 'morphSVG'],
			MotionPathPlugin: ['MotionPath', 'motionPath'],
			InertiaPlugin: ['Inertia', 'inertia'],
			Physics2DPlugin: ['Physics2D', 'physics2D'],
			PhysicsPropsPlugin: ['PhysicsProps', 'physicsProps']
		};
		var list = aliases[name] || [];
		for (var i = 0; i < list.length; i++) {
			if (root[list[i]]) return root[list[i]];
			if (gsap.plugins && gsap.plugins[list[i]]) return gsap.plugins[list[i]];
		}
		return null;
	}

	function snapshot() {
		var out = {
			gsap: !!root.gsap,
			gsapVersion: root.gsap && root.gsap.version ? root.gsap.version : null,
			available: {},
			missing: []
		};
		KNOWN.forEach(function (name) {
			var plugin = resolve(name);
			out.available[name] = !!plugin;
			if (!plugin) out.missing.push(name);
			else if (root.gsap && typeof root.gsap.registerPlugin === 'function') {
				try {
					root.gsap.registerPlugin(plugin);
				} catch (e) { /* already */ }
			}
		});
		return out;
	}

	function requirePlugins(list) {
		var missing = [];
		(list || []).forEach(function (name) {
			if (!resolve(name)) missing.push(name);
		});
		return { ok: missing.length === 0, missing: missing };
	}

	root.NGT3DPluginManager = {
		KNOWN: KNOWN,
		resolve: resolve,
		snapshot: snapshot,
		requirePlugins: requirePlugins
	};
})(typeof window !== 'undefined' ? window : this);
