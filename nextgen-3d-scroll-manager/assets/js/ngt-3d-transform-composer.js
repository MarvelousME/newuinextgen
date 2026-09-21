/**
 * Transform Composer — property ownership + optional motion layers.
 * Prevents competing effects from fighting over the same transform/opacity/filter/clip.
 */
(function (root) {
	'use strict';

	var OWNER_KEYS = ['transform', 'opacity', 'filter', 'clipPath', 'scroll', 'pointer'];
	var store = new WeakMap();

	function meta(el) {
		var m = store.get(el);
		if (!m) {
			m = { owners: Object.create(null), layers: Object.create(null), warnings: [] };
			store.set(el, m);
		}
		return m;
	}

	function claim(el, prop, ownerId) {
		var m = meta(el);
		var prev = m.owners[prop];
		if (prev && prev !== ownerId) {
			var warn = {
				element: el.id || el.className || el.tagName,
				prop: prop,
				effectA: prev,
				effectB: ownerId,
				resolution: 'isolate-layer'
			};
			m.warnings.push(warn);
			return { ok: false, conflict: warn };
		}
		m.owners[prop] = ownerId;
		return { ok: true };
	}

	function release(el, ownerId) {
		var m = store.get(el);
		if (!m) return;
		OWNER_KEYS.forEach(function (k) {
			if (m.owners[k] === ownerId) delete m.owners[k];
		});
	}

	/**
	 * Ensure a named motion layer wrapper. Only creates DOM when needed.
	 * @param {Element} el
	 * @param {'scroll-3d'|'element'|'text'} layer
	 */
	function ensureLayer(el, layer) {
		var m = meta(el);
		if (m.layers[layer] && document.contains(m.layers[layer])) {
			return m.layers[layer];
		}

		if (layer === 'element' || !layer) {
			el.setAttribute('data-motion-layer', 'element');
			m.layers.element = el;
			return el;
		}

		if (layer === 'text') {
			var existing = el.querySelector(':scope > [data-motion-layer="text"]');
			if (existing) {
				m.layers.text = existing;
				return existing;
			}
			var wrap = document.createElement('span');
			wrap.setAttribute('data-motion-layer', 'text');
			wrap.style.display = 'inline-block';
			wrap.style.transformStyle = 'preserve-3d';
			while (el.firstChild) wrap.appendChild(el.firstChild);
			el.appendChild(wrap);
			m.layers.text = wrap;
			return wrap;
		}

		if (layer === 'scroll-3d') {
			var parent = el.parentNode;
			if (!parent) return el;
			if (el.parentElement && el.parentElement.getAttribute('data-motion-layer') === 'scroll-3d') {
				m.layers['scroll-3d'] = el.parentElement;
				return el.parentElement;
			}
			var outer = document.createElement('div');
			outer.setAttribute('data-motion-layer', 'scroll-3d');
			outer.style.perspective = '1200px';
			outer.style.transformStyle = 'preserve-3d';
			parent.insertBefore(outer, el);
			outer.appendChild(el);
			m.layers['scroll-3d'] = outer;
			return outer;
		}

		return el;
	}

	function warningsFor(el) {
		var m = store.get(el);
		return m ? m.warnings.slice() : [];
	}

	root.NGT3DTransformComposer = {
		claim: claim,
		release: release,
		ensureLayer: ensureLayer,
		warningsFor: warningsFor,
		OWNER_KEYS: OWNER_KEYS
	};
})(typeof window !== 'undefined' ? window : this);
