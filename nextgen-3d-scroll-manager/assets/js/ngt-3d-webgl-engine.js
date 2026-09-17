/**
 * NGT 3D Scroll — WebGL engine (Three.js).
 *
 * Shared, reusable Three.js primitives used by:
 *   - the `webgl-distortion` / `webgl-image-reveal` registry animations
 *     (mouse-reactive ripple / scroll-revealed image, both driven by real
 *     WebGL shaders — no external displacement-map asset required, a noise
 *     texture is generated at runtime).
 *   - the `horizontal-3d-scroll` showcase preset's per-panel live model
 *     (NGT3DWebGL.createModelStage), which renders a procedural geometry or
 *     a glTF/GLB the site owner supplies.
 *
 * Enqueued only when NGT3D_Dependency_Resolver reports needs_webgl, and only
 * after assets/vendor/three.min.js (window.THREE) has loaded. Respects the
 * Settings → FPS safeguard: a sustained low frame rate steps every stage
 * down (pixel ratio → idle animation → full suspend) rather than let a slow
 * device grind to a halt.
 */
(function (root) {
	'use strict';

	var cfg = root.NGT3D || {};

	function log(level, msg, meta) {
		if (root.console && typeof root.console[level === 'error' ? 'error' : 'debug'] === 'function') {
			root.console[level === 'error' ? 'error' : 'debug']('[NGT3D:webgl]', msg, meta || '');
		}
	}

	function num(val, fallback) {
		var n = typeof val === 'number' ? val : parseFloat(val);
		return isFinite(n) ? n : fallback;
	}

	function capPixelRatio(scale) {
		return Math.min(root.devicePixelRatio || 1, scale || 2);
	}

	/**
	 * Rolling-average FPS monitor. Calls `onDegrade()` after ~2s of sustained
	 * frames below `threshold`; if `onDegrade` returns true, monitoring stops
	 * (the caller has reached its final degraded state).
	 */
	function createFpsMonitor(threshold, onDegrade) {
		var enabled = cfg.fpsSafeguard !== false;
		var limit = threshold || cfg.fpsThreshold || 30;
		var samples = [];
		var lastTime = null;
		var belowSince = null;
		var stopped = false;

		return {
			tick: function () {
				if (!enabled || stopped) return;
				var now = (root.performance && root.performance.now) ? root.performance.now() : Date.now();
				if (lastTime !== null) {
					var delta = now - lastTime;
					if (delta > 0 && delta < 1000) {
						samples.push(1000 / delta);
						if (samples.length > 45) samples.shift();
					}
				}
				lastTime = now;
				if (samples.length < 30) return;
				var sum = 0;
				for (var i = 0; i < samples.length; i++) sum += samples[i];
				var avg = sum / samples.length;
				if (avg < limit) {
					if (belowSince === null) {
						belowSince = now;
					} else if (now - belowSince > 2000) {
						belowSince = now;
						samples.length = 0;
						if (onDegrade() === true) stopped = true;
					}
				} else {
					belowSince = null;
				}
			}
		};
	}

	function geometryFor(THREE, name) {
		switch (name) {
			case 'torusKnot': return new THREE.TorusKnotGeometry(0.62, 0.22, 140, 20);
			case 'dodecahedron': return new THREE.DodecahedronGeometry(1, 0);
			case 'capsule': return new THREE.CapsuleGeometry(0.55, 0.85, 4, 16);
			case 'sphere': return new THREE.SphereGeometry(1, 32, 32);
			case 'box': return new THREE.BoxGeometry(1.3, 1.3, 1.3);
			case 'icosahedron':
			default: return new THREE.IcosahedronGeometry(1, 0);
		}
	}

	/**
	 * Small runtime-generated noise texture — avoids requiring the site owner
	 * to supply a displacement map for the two hover/reveal effects below.
	 */
	function noiseTexture(THREE, size) {
		size = size || 128;
		var canvas = document.createElement('canvas');
		canvas.width = canvas.height = size;
		var c2d = canvas.getContext('2d');
		var img = c2d.createImageData(size, size);
		for (var i = 0; i < img.data.length; i += 4) {
			var v = Math.floor(Math.random() * 255);
			img.data[i] = v;
			img.data[i + 1] = v;
			img.data[i + 2] = v;
			img.data[i + 3] = 255;
		}
		c2d.putImageData(img, 0, 0);
		var tex = new THREE.CanvasTexture(canvas);
		tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
		return tex;
	}

	function findImage(el) {
		return el.tagName === 'IMG' ? el : el.querySelector('img');
	}

	function makeOverlayCanvas(el) {
		var canvas = document.createElement('canvas');
		canvas.className = 'ngt-3d-webgl-canvas';
		canvas.setAttribute('aria-hidden', 'true');
		canvas.style.position = 'absolute';
		canvas.style.inset = '0';
		canvas.style.width = '100%';
		canvas.style.height = '100%';
		canvas.style.pointerEvents = 'none';
		var computed = root.getComputedStyle(el);
		if (computed.position === 'static') el.style.position = 'relative';
		el.appendChild(canvas);
		return canvas;
	}

	function baseRenderer(THREE, canvas) {
		var renderer = new THREE.WebGLRenderer({ canvas: canvas, alpha: true, antialias: true, powerPreference: 'high-performance' });
		renderer.setPixelRatio(capPixelRatio(2));
		if ('outputEncoding' in renderer && THREE.sRGBEncoding !== undefined) {
			renderer.outputEncoding = THREE.sRGBEncoding;
		}
		return renderer;
	}

	// ── webgl-distortion — mouse-reactive ripple over an image ──────────────

	function distortion(el, options, ctx) {
		var THREE = root.THREE;
		if (!THREE) { log('warn', 'THREE missing'); return function () {}; }
		var img = findImage(el);
		if (!img) return function () {};

		var intensity = num(options.intensity, 0.3);
		var speed = num(options.speed, 0.5);

		var canvas = makeOverlayCanvas(el);
		var renderer = baseRenderer(THREE, canvas);
		var scene = new THREE.Scene();
		var camera = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);

		var loader = new THREE.TextureLoader();
		loader.crossOrigin = 'anonymous';
		var texture = loader.load(img.currentSrc || img.src);
		texture.minFilter = THREE.LinearFilter;

		var uniforms = {
			uTexture: { value: texture },
			uMouse: { value: new THREE.Vector2(0.5, 0.5) },
			uHover: { value: 0 },
			uTime: { value: 0 },
			uIntensity: { value: intensity }
		};

		var material = new THREE.ShaderMaterial({
			uniforms: uniforms,
			transparent: true,
			vertexShader: 'varying vec2 vUv; void main(){ vUv = uv; gl_Position = vec4(position, 1.0); }',
			fragmentShader: [
				'precision mediump float;',
				'uniform sampler2D uTexture;',
				'uniform vec2 uMouse;',
				'uniform float uHover;',
				'uniform float uTime;',
				'uniform float uIntensity;',
				'varying vec2 vUv;',
				'void main(){',
				'  vec2 uv = vUv;',
				'  float dist = distance(uv, uMouse);',
				'  float ripple = sin(dist * 40.0 - uTime * 3.0) * uIntensity * uHover * 0.05 * (1.0 - smoothstep(0.0, 0.6, dist));',
				'  vec2 dir = normalize(uv - uMouse + 0.0001);',
				'  vec2 distortedUv = uv + dir * ripple;',
				'  gl_FragColor = texture2D(uTexture, distortedUv);',
				'}'
			].join('\n')
		});

		var quad = new THREE.Mesh(new THREE.PlaneGeometry(2, 2), material);
		scene.add(quad);

		function resize() {
			var rect = el.getBoundingClientRect();
			renderer.setSize(Math.max(1, rect.width), Math.max(1, rect.height), false);
		}
		resize();
		var ro = root.ResizeObserver ? new ResizeObserver(resize) : null;
		if (ro) ro.observe(el);

		function onMove(e) {
			var rect = el.getBoundingClientRect();
			uniforms.uMouse.value.set(
				(e.clientX - rect.left) / rect.width,
				1 - (e.clientY - rect.top) / rect.height
			);
		}
		function onEnter() { uniforms.uHover.value = 1; }
		function onLeave() { uniforms.uHover.value = 0; }
		el.addEventListener('mousemove', onMove);
		el.addEventListener('mouseenter', onEnter);
		el.addEventListener('mouseleave', onLeave);

		var clock = new THREE.Clock();
		var raf = null;
		var visible = true;
		var io = root.IntersectionObserver ? new IntersectionObserver(function (entries) {
			entries.forEach(function (en) { visible = en.isIntersecting; });
		}, { rootMargin: '150px' }) : null;
		if (io) io.observe(el);

		var fps = createFpsMonitor(cfg.fpsThreshold, function () {
			raf && root.cancelAnimationFrame(raf);
			raf = null;
			return true;
		});

		function loop() {
			raf = root.requestAnimationFrame(loop);
			if (!visible) return;
			fps.tick();
			uniforms.uTime.value = clock.getElapsedTime() * speed;
			renderer.render(scene, camera);
		}
		loop();

		return function destroy() {
			if (raf) root.cancelAnimationFrame(raf);
			if (ro) ro.disconnect();
			if (io) io.disconnect();
			el.removeEventListener('mousemove', onMove);
			el.removeEventListener('mouseenter', onEnter);
			el.removeEventListener('mouseleave', onLeave);
			material.dispose();
			quad.geometry.dispose();
			texture.dispose();
			renderer.dispose();
			canvas.remove();
		};
	}

	// ── webgl-image-reveal — scroll-triggered wipe with noise displacement ──

	function imageReveal(el, options, ctx) {
		var THREE = root.THREE;
		if (!THREE) { log('warn', 'THREE missing'); return function () {}; }
		var img = findImage(el);
		if (!img) return function () {};

		var duration = num(options.duration, 1.5);

		var canvas = makeOverlayCanvas(el);
		var renderer = baseRenderer(THREE, canvas);
		var scene = new THREE.Scene();
		var camera = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);

		var loader = new THREE.TextureLoader();
		loader.crossOrigin = 'anonymous';
		var texture = loader.load(img.currentSrc || img.src);
		var noise = noiseTexture(THREE, 128);

		var uniforms = {
			uTexture: { value: texture },
			uNoise: { value: noise },
			uProgress: { value: ctx.reducedMotion ? 1 : 0 }
		};

		var material = new THREE.ShaderMaterial({
			uniforms: uniforms,
			transparent: true,
			vertexShader: 'varying vec2 vUv; void main(){ vUv = uv; gl_Position = vec4(position, 1.0); }',
			fragmentShader: [
				'precision mediump float;',
				'uniform sampler2D uTexture;',
				'uniform sampler2D uNoise;',
				'uniform float uProgress;',
				'varying vec2 vUv;',
				'void main(){',
				'  float n = texture2D(uNoise, vUv * 2.0).r;',
				'  float edge = smoothstep(uProgress - 0.15, uProgress + 0.15, vUv.x + (n - 0.5) * 0.25);',
				'  vec4 color = texture2D(uTexture, vUv);',
				'  gl_FragColor = vec4(color.rgb, 1.0 - edge);',
				'}'
			].join('\n')
		});

		var quad = new THREE.Mesh(new THREE.PlaneGeometry(2, 2), material);
		scene.add(quad);

		function resize() {
			var rect = el.getBoundingClientRect();
			renderer.setSize(Math.max(1, rect.width), Math.max(1, rect.height), false);
		}
		resize();
		var ro = root.ResizeObserver ? new ResizeObserver(resize) : null;
		if (ro) ro.observe(el);

		var raf = null;
		function render() {
			renderer.render(scene, camera);
		}
		render();

		var tween = null;
		if (!ctx.reducedMotion && ctx.gsap) {
			tween = ctx.gsap.to(uniforms.uProgress, {
				value: 1,
				duration: duration,
				ease: 'power2.out',
				onUpdate: render,
				scrollTrigger: {
					trigger: el,
					start: 'top 80%',
					toggleActions: 'play none none none'
				}
			});
		}

		function onResize() { resize(); render(); }
		root.addEventListener('resize', onResize);

		return function destroy() {
			root.removeEventListener('resize', onResize);
			if (raf) root.cancelAnimationFrame(raf);
			if (ro) ro.disconnect();
			if (tween) {
				if (tween.scrollTrigger) tween.scrollTrigger.kill();
				tween.kill();
			}
			material.dispose();
			quad.geometry.dispose();
			texture.dispose();
			noise.dispose();
			renderer.dispose();
			canvas.remove();
		};
	}

	// ── createModelStage — reusable live 3D model for one panel/element ─────

	/**
	 * Mount a rotating, scroll-reactive 3D object (procedural geometry or a
	 * glTF/GLB) inside `el`. Used by the horizontal-3d-scroll preset.
	 *
	 * @param {Element} el       Host element — canvas is appended, absolutely
	 *                           positioned to fill it (host needs `position`).
	 * @param {object}  options  modelGeometry, modelColor, modelMetalness,
	 *                           modelRoughness, autoRotate, autoRotateSpeed,
	 *                           lightIntensity, cameraFov, modelUrl, rotateYRange.
	 * @param {object}  ctx      Shared context (diagnostics, reducedMotion…).
	 * @return {object|null} stage — { setProgress(p), setVisible(bool), destroy() }
	 */
	function createModelStage(el, options, ctx) {
		var THREE = root.THREE;
		if (!THREE) { log('warn', 'THREE missing — model stage skipped'); return null; }

		options = options || {};
		var geometryName = options.modelGeometry || 'icosahedron';
		var color = options.modelColor || '#3ABF35';
		var metalness = num(options.modelMetalness, 0.35);
		var roughness = num(options.modelRoughness, 0.4);
		var lightIntensity = num(options.lightIntensity, 1.1);
		var fov = num(options.cameraFov, 45);
		var rotateYRange = num(options.rotateYRange, 20) * (Math.PI / 180);
		var modelUrl = options.modelUrl || '';
		var autoRotateEnabled = options.autoRotate !== false && !ctx.reducedMotion;
		var autoRotateSpeed = num(options.autoRotateSpeed, 0.4);

		if ('none' === geometryName && !modelUrl) return null;

		var canvas = document.createElement('canvas');
		canvas.className = 'ngt-3d-hscroll__canvas';
		canvas.setAttribute('aria-hidden', 'true');
		var computed = root.getComputedStyle(el);
		if (computed.position === 'static') el.style.position = 'relative';
		el.appendChild(canvas);

		var renderer;
		try {
			renderer = baseRenderer(THREE, canvas);
		} catch (e) {
			log('error', 'WebGLRenderer failed', { err: String(e) });
			canvas.remove();
			return null;
		}

		var scene = new THREE.Scene();
		var camera = new THREE.PerspectiveCamera(fov, 1, 0.1, 100);
		camera.position.set(0, 0, 3.4);

		scene.add(new THREE.AmbientLight(0xffffff, 0.5 * lightIntensity));
		var key = new THREE.DirectionalLight(0xffffff, lightIntensity);
		key.position.set(2.5, 3, 4);
		scene.add(key);
		var rim = new THREE.DirectionalLight(0xffffff, 0.35 * lightIntensity);
		rim.position.set(-3, -2, -2);
		scene.add(rim);

		var group = new THREE.Group();
		scene.add(group);

		function applyProcedural() {
			var geometry = geometryFor(THREE, geometryName);
			var material = new THREE.MeshStandardMaterial({ color: new THREE.Color(color), metalness: metalness, roughness: roughness });
			group.add(new THREE.Mesh(geometry, material));
		}

		var disposed = false;
		if (modelUrl && THREE.GLTFLoader) {
			try {
				var gltfLoader = new THREE.GLTFLoader();
				gltfLoader.load(
					modelUrl,
					function (gltf) {
						if (disposed) return;
						var loaded = gltf.scene || (gltf.scenes && gltf.scenes[0]);
						if (!loaded) { applyProcedural(); return; }
						var box = new THREE.Box3().setFromObject(loaded);
						var size = new THREE.Vector3();
						box.getSize(size);
						var center = new THREE.Vector3();
						box.getCenter(center);
						var maxDim = Math.max(size.x, size.y, size.z) || 1;
						loaded.position.sub(center);
						loaded.scale.setScalar(1.6 / maxDim);
						group.add(loaded);
						if (ctx.reducedMotion) renderer.render(scene, camera);
					},
					undefined,
					function (err) {
						if (ctx.diagnostics) ctx.diagnostics.log('warn', 'glTF load failed — using procedural fallback', { url: modelUrl, err: String(err) });
						if (!disposed) applyProcedural();
					}
				);
			} catch (e) {
				applyProcedural();
			}
		} else {
			applyProcedural();
		}

		// The canvas's own box (sized by CSS — it may be a small badge, a full
		// bleed background, or anything in between) is what the renderer and
		// camera aspect must match, not the host element's box.
		function resize() {
			var rect = canvas.getBoundingClientRect();
			var w = Math.max(1, rect.width);
			var h = Math.max(1, rect.height);
			renderer.setSize(w, h, false);
			camera.aspect = w / h;
			camera.updateProjectionMatrix();
		}
		resize();
		var ro = root.ResizeObserver ? new ResizeObserver(resize) : null;
		if (ro) ro.observe(canvas);

		var panelVisible = true;
		var io = root.IntersectionObserver ? new IntersectionObserver(function (entries) {
			entries.forEach(function (en) { panelVisible = en.isIntersecting; });
		}, { root: null, rootMargin: '0px 600px', threshold: 0 }) : null;
		if (io) io.observe(el);

		var externalVisible = true;
		var scrollYaw = 0;
		var idleYaw = 0;

		function applyTransform() {
			group.rotation.y = idleYaw + scrollYaw;
		}

		var degradeLevel = 0;
		var fps = createFpsMonitor(cfg.fpsThreshold, function () {
			degradeLevel++;
			if (degradeLevel === 1) {
				renderer.setPixelRatio(1);
				return false;
			}
			if (degradeLevel === 2) {
				autoRotateEnabled = false;
				return false;
			}
			if (ctx.diagnostics) ctx.diagnostics.log('warn', 'Model stage suspended (fps safeguard)', {});
			suspended = true;
			return true;
		});

		var suspended = false;
		var clock = new THREE.Clock();
		var rafId = null;

		function loop() {
			rafId = root.requestAnimationFrame(loop);
			if (suspended || !panelVisible || !externalVisible || disposed) return;
			fps.tick();
			var dt = Math.min(clock.getDelta(), 0.05);
			if (autoRotateEnabled) idleYaw += dt * autoRotateSpeed;
			applyTransform();
			renderer.render(scene, camera);
		}

		// Reduced motion: render exactly one static frame, never start the
		// per-frame render loop (setProgress() below still updates that frame).
		if (ctx.reducedMotion) {
			applyTransform();
			renderer.render(scene, camera);
		} else {
			rafId = root.requestAnimationFrame(loop);
		}

		return {
			setProgress: function (p) {
				var clamped = Math.max(-1, Math.min(1, p));
				scrollYaw = clamped * rotateYRange;
				group.rotation.x = clamped * 0.16;
				group.position.y = clamped * -0.12;
				var s = 1 - Math.min(0.2, Math.abs(clamped) * 0.2);
				group.scale.setScalar(s);
				if (suspended || ctx.reducedMotion) {
					applyTransform();
					renderer.render(scene, camera);
				}
			},
			setVisible: function (isVisible) {
				externalVisible = !!isVisible;
				canvas.style.visibility = externalVisible ? '' : 'hidden';
			},
			destroy: function () {
				disposed = true;
				if (rafId) root.cancelAnimationFrame(rafId);
				if (ro) ro.disconnect();
				if (io) io.disconnect();
				group.traverse(function (obj) {
					if (obj.geometry) obj.geometry.dispose();
					if (obj.material) {
						var mats = Array.isArray(obj.material) ? obj.material : [obj.material];
						mats.forEach(function (m) { m.dispose && m.dispose(); });
					}
				});
				renderer.dispose();
				canvas.remove();
			}
		};
	}

	root.NGT3DWebGL = {
		distortion: distortion,
		imageReveal: imageReveal,
		createModelStage: createModelStage,
		createFpsMonitor: createFpsMonitor
	};
})(typeof window !== 'undefined' ? window : this);
