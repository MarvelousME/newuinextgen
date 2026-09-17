/**
 * Showcase preset: horizontal-3d-scroll
 *
 * Pins the target section and drives its panels horizontally as the page
 * scrolls vertically (GSAP + ScrollTrigger). Each panel gets its own local
 * 0→1 progress via GSAP's documented `containerAnimation` technique, which
 * feeds two things:
 *   - CSS 3D transforms on any `[data-depth]` child layers (translateZ /
 *     rotateY scrubbed by that panel's progress).
 *   - A live Three.js object (NGT3DWebGL.createModelStage) on any panel
 *     carrying `data-ngt-3d-model` — procedural geometry by default, or a
 *     glTF/GLB the site owner points it at.
 *
 * Progressive enhancement: the base CSS (ngt-3d-preset-horizontal-3d-scroll.css)
 * already renders a native horizontally-swipeable, scroll-snapped strip with
 * no JS at all. This script only *upgrades* that into the pinned/scrubbed/3D
 * experience — on mobile_mode:"disabled" or prefers-reduced-motion it simply
 * never applies the upgrade class, so content is never hidden.
 */
(function (root) {
	'use strict';

	function register(id, def) {
		if (typeof root.NGT3DRegisterPreset === 'function') root.NGT3DRegisterPreset(id, def);
		else if (root.NGT3D && typeof root.NGT3D.registerPreset === 'function') root.NGT3D.registerPreset(id, def);
		else (root.NGT3DPresets || (root.NGT3DPresets = {}))[id] = def;
	}

	function num(val, fallback) {
		var n = typeof val === 'number' ? val : parseFloat(val);
		return isFinite(n) ? n : fallback;
	}

	/**
	 * Resolve (and, if necessary, create) the track element that actually
	 * moves. Enhancement is non-destructive: existing children are relocated
	 * into the new wrapper, never removed or rebuilt.
	 */
	function ensureTrack(el, trackSelector) {
		var track = trackSelector ? el.querySelector(trackSelector) : null;
		if (!track) {
			track = el.querySelector('.ngt-3d-hscroll__track, [data-ngt-hscroll-track]');
		}
		if (track) return track;

		track = document.createElement('div');
		track.className = 'ngt-3d-hscroll__track';
		var kids = Array.prototype.slice.call(el.children);
		kids.forEach(function (kid) { track.appendChild(kid); });
		el.appendChild(track);
		return track;
	}

	function resolvePanels(track, panelSelector) {
		if (panelSelector) {
			var matched = track.querySelectorAll(panelSelector);
			if (matched.length) return Array.prototype.slice.call(matched);
		}
		return Array.prototype.filter.call(track.children, function (n) { return n.nodeType === 1; });
	}

	function collectDepthLayers(panel) {
		var nodes = panel.querySelectorAll('[data-depth]');
		return Array.prototype.map.call(nodes, function (node) {
			return { el: node, depth: num(node.getAttribute('data-depth'), 40) };
		});
	}

	register('horizontal-3d-scroll', {
		id: 'horizontal-3d-scroll',
		title: 'Horizontal 3D Scroll',
		apply: function (el, options, ctx) {
			options = options || {};

			var direction = options.direction === 'rtl' ? 'rtl' : 'ltr';
			var track = ensureTrack(el, options.trackSelector);
			var panels = resolvePanels(track, options.panelSelector);

			el.classList.add('ngt-3d-hscroll');
			track.classList.add('ngt-3d-hscroll__track');
			if ('rtl' === direction) el.classList.add('ngt-3d-hscroll--rtl');
			panels.forEach(function (panel) { panel.classList.add('ngt-3d-hscroll__panel'); });

			if (!panels.length || !ctx.gsap || !ctx.ScrollTrigger) {
				return function () {};
			}

			// Reduced motion: leave the CSS-only swipeable strip in place, but
			// still light up any opted-in 3D models as static objects so the
			// section isn't inert — no pin, no scrub, no scroll-linked motion.
			if (ctx.reducedMotion) {
				var staticStages = [];
				panels.forEach(function (panel) {
					var modelAttr = panel.getAttribute('data-ngt-3d-model');
					if (modelAttr === null || !root.NGT3DWebGL) return;
					var stage = root.NGT3DWebGL.createModelStage(panel, panelModelOptions(panel, modelAttr, options), ctx);
					if (stage) staticStages.push(stage);
				});
				return function () {
					staticStages.forEach(function (s) { s.destroy(); });
				};
			}

			el.classList.add('ngt-3d-hscroll--engaged');

			function distance() {
				return Math.max(0, track.scrollWidth - el.clientWidth);
			}

			var scrub = options.scrub === false ? false : (options.scrub === undefined ? 1 : options.scrub);
			var sign = 'rtl' === direction ? 1 : -1;

			var tween = ctx.gsap.to(track, {
				x: function () { return sign * distance(); },
				ease: 'none',
				scrollTrigger: {
					trigger: el,
					start: 'top top',
					end: function () { return '+=' + distance(); },
					scrub: scrub,
					pin: true,
					pinSpacing: true,
					anticipatePin: 1,
					invalidateOnRefresh: true,
					snap: options.snap ? {
						snapTo: panels.length > 1 ? 1 / (panels.length - 1) : 1,
						duration: num(options.snapDuration, 0.4),
						ease: 'power1.inOut'
					} : undefined
				}
			});

			var panelTriggers = [];
			var stages = [];

			panels.forEach(function (panel) {
				var layers = collectDepthLayers(panel);
				var layerDepth = num(options.layerDepth, 60);
				var rotateYRange = num(options.rotateYRange, 10);

				var modelAttr = panel.getAttribute('data-ngt-3d-model');
				var stage = null;
				if (null !== modelAttr && root.NGT3DWebGL) {
					stage = root.NGT3DWebGL.createModelStage(panel, panelModelOptions(panel, modelAttr, options), ctx);
					if (stage) stages.push(stage);
				}

				var panelST = ctx.ScrollTrigger.create({
					trigger: panel,
					containerAnimation: tween,
					start: 'left center',
					end: 'right center',
					onUpdate: function (self) {
						var progress = self.progress;      // 0..1 across this panel's local span
						var p = progress * 2 - 1;            // -1..1, 0 = centered in viewport

						panel.style.setProperty('--ngt-3d-panel-progress', progress.toFixed(4));
						panel.style.setProperty('--ngt-3d-panel-p', p.toFixed(4));
						panel.style.setProperty('--ngt-3d-panel-scale', (1 - Math.min(0.06, Math.abs(p) * 0.06)).toFixed(4));
						panel.style.setProperty('--ngt-3d-panel-rotate-y', (p * 3).toFixed(2) + 'deg');

						layers.forEach(function (layer) {
							var z = -p * layer.depth;
							var ry = p * (rotateYRange * (layer.depth / Math.max(1, layerDepth)));
							layer.el.style.transform = 'translateZ(' + z.toFixed(2) + 'px) rotateY(' + ry.toFixed(2) + 'deg)';
						});

						if (stage) stage.setProgress(p);
					}
				});
				panelTriggers.push(panelST);
			});

			return function destroy() {
				panelTriggers.forEach(function (t) { t.kill(); });
				stages.forEach(function (s) { s.destroy(); });
				if (tween.scrollTrigger) tween.scrollTrigger.kill();
				tween.kill();
				el.classList.remove('ngt-3d-hscroll--engaged');
			};

			/**
			 * Merge rule-level defaults with a panel's own data-attribute
			 * overrides into the option shape NGT3DWebGL.createModelStage expects.
			 */
			function panelModelOptions(panel, modelAttr, ruleOptions) {
				var isUrl = /\.(glb|gltf)(\?.*)?$/i.test(modelAttr || '');
				return {
					modelGeometry: isUrl ? 'none' : (modelAttr || ruleOptions.modelGeometry || 'icosahedron'),
					modelUrl: isUrl ? modelAttr : '',
					modelColor: panel.getAttribute('data-ngt-3d-color') || ruleOptions.modelColor || '#3ABF35',
					modelMetalness: num(panel.getAttribute('data-ngt-3d-metalness'), num(ruleOptions.modelMetalness, 0.35)),
					modelRoughness: num(panel.getAttribute('data-ngt-3d-roughness'), num(ruleOptions.modelRoughness, 0.4)),
					autoRotate: panel.getAttribute('data-ngt-3d-autorotate') === 'false' ? false : (ruleOptions.autoRotate !== false),
					autoRotateSpeed: num(ruleOptions.autoRotateSpeed, 0.4),
					lightIntensity: num(ruleOptions.lightIntensity, 1.1),
					cameraFov: num(ruleOptions.cameraFov, 45),
					rotateYRange: num(ruleOptions.rotateYRange, 20)
				};
			}
		}
	});
})(typeof window !== 'undefined' ? window : this);
