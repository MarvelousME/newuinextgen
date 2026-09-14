/**
 * NGT 3D Scroll — compatibility shim.
 * Loads before the runtime. Preserves preset/animation registries across
 * wp_localize_script overwrite of window.NGT3D.
 */
(function (root) {
	'use strict';

	var presets = root.NGT3DPresets || Object.create(null);
	var animations = root.NGT3DAnimations || Object.create(null);
	var queue = root.__NGT3D_PRESET_QUEUE__ || [];

	root.NGT3DPresets = presets;
	root.NGT3DAnimations = animations;
	root.__NGT3D_PRESET_QUEUE__ = queue;

	/**
	 * Register a showcase preset strategy.
	 * Safe to call before or after runtime init.
	 *
	 * @param {string} id
	 * @param {object} definition
	 */
	function registerPreset(id, definition) {
		if (!id || !definition || typeof definition !== 'object') return;
		var key = String(id);
		presets[key] = definition;
		queue.push({ id: key, definition: definition });
		if (root.NGT3D && typeof root.NGT3D.onPresetRegistered === 'function') {
			try {
				root.NGT3D.onPresetRegistered(key, definition);
			} catch (err) {
				/* isolated */
			}
		}
	}

	/**
	 * Register a legacy / custom animation strategy.
	 *
	 * @param {string} id
	 * @param {object} definition
	 */
	function registerAnimation(id, definition) {
		if (!id || !definition || typeof definition !== 'object') return;
		animations[String(id)] = definition;
		if (root.NGT3D && typeof root.NGT3D.onAnimationRegistered === 'function') {
			try {
				root.NGT3D.onAnimationRegistered(String(id), definition);
			} catch (err) {
				/* isolated */
			}
		}
	}

	root.NGT3DRegisterPreset = registerPreset;
	root.NGT3DRegisterAnimation = registerAnimation;

	/* Early stubs so late-loaded presets can attach methods when runtime arrives. */
	var early = root.NGT3D || {};
	early.registerPreset = registerPreset;
	early.registerAnimation = registerAnimation;
	root.NGT3D = early;

	/**
	 * Map legacy BeyondInfinity data attributes → CSS hooks (no competing transforms).
	 */
	function bridgeLegacyAttrs() {
		var nodes = document.querySelectorAll('[data-bi-tilt], [data-bi-stack-3d], .bi-tilt-3d, .bi-stack-3d');
		for (var i = 0; i < nodes.length; i++) {
			var el = nodes[i];
			el.classList.add('ngt-3d-legacy-bridge');
			if (el.hasAttribute('data-bi-tilt') || el.classList.contains('bi-tilt-3d')) {
				el.setAttribute('data-ngt-tilt-host', '1');
			}
			if (el.hasAttribute('data-bi-stack-3d') || el.classList.contains('bi-stack-3d')) {
				el.setAttribute('data-ngt-stack-host', '1');
			}
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bridgeLegacyAttrs);
	} else {
		bridgeLegacyAttrs();
	}
})(typeof window !== 'undefined' ? window : this);
