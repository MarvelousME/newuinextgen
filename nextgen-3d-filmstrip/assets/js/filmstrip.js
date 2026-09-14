/**
 * NextGen 3D Filmstrip — perspective deck runtime.
 */
(() => {
	'use strict';

	const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	class Filmstrip {
		/**
		 * @param {HTMLElement} root
		 */
		constructor(root) {
			this.root = root;
			this.viewport = root.querySelector('[data-ngtfs-viewport]');
			this.stage = root.querySelector('[data-ngtfs-stage]');
			this.cards = Array.from(root.querySelectorAll('[data-ngtfs-card]'));
			this.dots = Array.from(root.querySelectorAll('.ngtfs__dot'));
			this.live = root.querySelector('[data-ngtfs-live]');
			this.n = this.cards.length;
			this.index = 0;
			this.loop = root.dataset.loop !== '0';
			this.autoplay = root.dataset.autoplay === '1' && !reduceMotion;
			this.timer = null;
			this.pointerId = null;
			this.startX = 0;
			this.dragging = false;

			if (!this.viewport || this.n < 1) {
				return;
			}

			root.dataset.ngtfsEnhanced = 'true';
			if (!root.hasAttribute('tabindex')) {
				root.setAttribute('tabindex', '0');
			}

			this.bind();
			this.render();
			this.startAuto();
		}

		bind() {
			const prev = this.root.querySelector('[data-ngtfs-prev]');
			const next = this.root.querySelector('[data-ngtfs-next]');
			if (prev) prev.addEventListener('click', () => { this.go(-1); this.stopAuto(); });
			if (next) next.addEventListener('click', () => { this.go(1); this.stopAuto(); });

			this.dots.forEach((dot) => {
				dot.addEventListener('click', () => {
					this.goTo(parseInt(dot.dataset.index || '0', 10));
					this.stopAuto();
				});
			});

			this.root.addEventListener('keydown', (e) => {
				if (e.key === 'ArrowLeft') {
					e.preventDefault();
					this.go(-1);
					this.stopAuto();
				} else if (e.key === 'ArrowRight') {
					e.preventDefault();
					this.go(1);
					this.stopAuto();
				}
			});

			this.viewport.addEventListener('pointerdown', (e) => {
				if (e.button !== 0) return;
				this.pointerId = e.pointerId;
				this.startX = e.clientX;
				this.dragging = true;
				try {
					this.viewport.setPointerCapture(e.pointerId);
				} catch (_) { /* ignore */ }
			});

			this.viewport.addEventListener('pointerup', (e) => this.endDrag(e.clientX));
			this.viewport.addEventListener('pointercancel', () => {
				this.dragging = false;
				this.pointerId = null;
			});

			this.viewport.addEventListener(
				'wheel',
				(e) => {
					if (Math.abs(e.deltaY) < 8 && Math.abs(e.deltaX) < 8) return;
					e.preventDefault();
					const dir = (Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY) > 0 ? 1 : -1;
					this.go(dir);
					this.stopAuto();
				},
				{ passive: false }
			);

			this.cards.forEach((card, i) => {
				card.addEventListener('click', (e) => {
					if (i !== this.index) {
						e.preventDefault();
						this.goTo(i);
						this.stopAuto();
					}
				});
			});

			this.root.addEventListener('mouseenter', () => this.stopAuto());
			this.root.addEventListener('mouseleave', () => this.startAuto());
			window.addEventListener('resize', () => this.render());
		}

		endDrag(clientX) {
			if (!this.dragging) return;
			const dx = clientX - this.startX;
			this.dragging = false;
			this.pointerId = null;
			if (Math.abs(dx) > 50) {
				this.go(dx < 0 ? 1 : -1);
				this.stopAuto();
			}
		}

		go(dir) {
			if (this.loop) {
				this.index = (this.index + dir + this.n) % this.n;
			} else {
				this.index = Math.max(0, Math.min(this.n - 1, this.index + dir));
			}
			this.render();
		}

		goTo(i) {
			this.index = ((i % this.n) + this.n) % this.n;
			this.render();
		}

		announce() {
			if (!this.live) return;
			const title = this.cards[this.index]?.querySelector('.ngtfs-card__name')?.textContent?.trim() || '';
			this.live.textContent = title
				? `Showing ${this.index + 1} of ${this.n}: ${title}`
				: `Showing ${this.index + 1} of ${this.n}`;
		}

		render() {
			const mobile = window.matchMedia('(max-width: 720px)').matches;
			const spread = Math.min(mobile ? 210 : 300, Math.max(160, Math.floor(this.viewport.clientWidth * 0.22)));
			const depth = mobile ? 90 : 130;
			const rot = mobile ? 16 : 24;

			this.cards.forEach((card, i) => {
				let diff = i - this.index;
				if (this.loop) {
					if (diff > this.n / 2) diff -= this.n;
					if (diff < -this.n / 2) diff += this.n;
				}
				const abs = Math.abs(diff);
				const tx = diff * spread;
				const tz = -abs * depth;
				const ry = -diff * rot;
				const scale = Math.max(0.72, 1 - abs * 0.08);
				const opacity = abs > 3 ? 0 : Math.max(0.25, 1 - abs * 0.22);
				const blur = reduceMotion || abs < 1 ? 0 : Math.min(4, abs * 1.2);

				card.style.transform = `translate(-50%, -50%) translateX(${tx}px) translateZ(${tz}px) rotateY(${ry}deg) scale(${scale})`;
				card.style.opacity = String(opacity);
				card.style.filter = blur ? `blur(${blur}px)` : 'none';
				card.style.zIndex = String(100 - abs);
				card.style.pointerEvents = abs === 0 ? 'auto' : 'auto';
				card.classList.toggle('is-active', abs === 0);
				card.setAttribute('aria-hidden', abs === 0 ? 'false' : 'true');
			});

			this.dots.forEach((dot, i) => {
				const on = i === this.index;
				dot.classList.toggle('is-active', on);
				dot.setAttribute('aria-selected', on ? 'true' : 'false');
			});

			this.announce();
		}

		startAuto() {
			this.stopAuto();
			if (!this.autoplay || this.n < 2) return;
			this.timer = window.setInterval(() => this.go(1), 4800);
		}

		stopAuto() {
			if (this.timer) {
				clearInterval(this.timer);
				this.timer = null;
			}
		}
	}

	const boot = () => {
		document.querySelectorAll('[data-ngtfs]').forEach((root) => {
			if (root.dataset.ngtfsReady === '1') return;
			root.dataset.ngtfsReady = '1';
			new Filmstrip(root);
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
