// smoothscroll/fx-smoothscroll.js

import { $$ as qsa, on, off } from '../../dom.js';
import Events from '../../events.js';
import { createWeakStore } from '../../weak.js';

const SELECTOR = '[data-fx-scroll]';
const handlers = createWeakStore();

/** Easing: ease-in-out cubic */
const easeInOutCubic = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);

const FxSmoothScroll = {
	_rafId: null,
	_urlTargetScrolled: false,

	/**
	 * Smooth scroll to target Y position using requestAnimationFrame.
	 * Independent of CSS scroll-behavior.
	 *
	 * @param {number} targetY
	 * @param {Object} [options]
	 * @param {number} [options.offset=0]
	 * @param {number} [options.duration=600] - Animation duration in ms
	 * @param {Function} [options.onStart]
	 * @param {Function} [options.onEnd]
	 */
	smoothScrollTo(targetY, { offset = 0, duration = 600, onStart, onEnd } = {}) {
		// Cancel any in-progress animation
		if (this._rafId) {
			cancelAnimationFrame(this._rafId);
			this._rafId = null;
		}

		const finalTarget = Math.max(0, targetY - offset);
		const startY = window.scrollY;
		const distance = finalTarget - startY;

		onStart?.({ startY, targetY: finalTarget });

		// Already at target — fire onEnd immediately
		if (Math.abs(distance) < 1) {
			onEnd?.({ finalY: finalTarget });
			return;
		}

		let startTime = null;

		const step = (currentTime) => {
			if (!startTime) startTime = currentTime;

			const elapsed = currentTime - startTime;
			const progress = Math.min(elapsed / duration, 1);
			const eased = easeInOutCubic(progress);

			window.scrollTo(0, startY + distance * eased);

			if (progress < 1) {
				this._rafId = requestAnimationFrame(step);
			} else {
				this._rafId = null;
				onEnd?.({ finalY: finalTarget });
			}
		};

		this._rafId = requestAnimationFrame(step);
	},

	/**
	 * Extract target ID from href
	 * Supports: #abc, ?section=abc, &section=abc
	 * @param {string} href
	 * @returns {string|null}
	 */
	getTargetId(href) {
		if (!href) return null;

		// Check for hash link: #abc
		if (href.startsWith('#')) {
			return href.slice(1) || null;
		}

		// Check for query parameter: ?section=abc or &section=abc
		try {
			const url = new URL(href, window.location.origin);
			const section = url.searchParams.get('section');
			if (section) return section;
		} catch {
			// Fallback regex for relative URLs or malformed URLs
			const match = href.match(/[?&]section=([^&]+)/);
			if (match) return match[1];
		}

		return null;
	},

	initAll(root = document) {
		qsa(SELECTOR, root).forEach((a) => {
			const handler = (e) => {
				const href = a.getAttribute('href');
				const targetId = this.getTargetId(href);
				if (!targetId) return;

				e.preventDefault();

				const target = document.getElementById(targetId);
				if (!target) return;

				const offset = parseInt(a.dataset.fxOffset ?? a.closest('[data-fx-offset]')?.dataset.fxOffset ?? document.body.dataset.fxOffset ?? 0, 10);

				const targetY = target.getBoundingClientRect().top + window.scrollY;

				FxSmoothScroll.smoothScrollTo(targetY, {
					offset,
					onStart: () => Events.emit('fx:smoothscroll:start', { link: a, target }),
					onEnd: () => {
						target.setAttribute('tabindex', '-1');
						target.focus({ preventScroll: true });
						Events.emit('fx:smoothscroll:goto', { link: a, target });
					},
				});
			};

			handlers.set(a, handler);
			on(a, 'click', handler);
		});

		// Auto scroll on page load if URL has section parameter (only once)
		if (!this._urlTargetScrolled) {
			this._urlTargetScrolled = true;
			this.scrollToUrlTarget();
		}
	},

	/**
	 * Scroll to target section based on current URL
	 * Checks for: #abc, ?section=abc, &section=abc
	 * Called automatically on page load/init
	 */
	scrollToUrlTarget() {
		const url = window.location;

		// Check query parameter ?section=abc first, then fall back to #hash
		const section = new URLSearchParams(url.search).get('section') || (url.hash.length > 1 ? url.hash.slice(1) : null);
		if (!section) return;

		const target = document.getElementById(section);
		if (!target) return;

		// Get offset from body or default to 0
		const offset = parseInt(document.body.dataset.fxOffset ?? 0, 10);
		const targetY = target.getBoundingClientRect().top + window.scrollY;

		// Small delay to ensure page is fully loaded
		requestAnimationFrame(() => {
			FxSmoothScroll.smoothScrollTo(targetY, {
				offset,
				onStart: () => Events.emit('fx:smoothscroll:start', { link: null, target }),
				onEnd: () => Events.emit('fx:smoothscroll:goto', { link: null, target }),
			});
		});
	},

	destroyAll(root = document) {
		if (this._rafId) {
			cancelAnimationFrame(this._rafId);
			this._rafId = null;
		}

		qsa(SELECTOR, root).forEach((a) => {
			const handler = handlers.get(a);
			if (handler) {
				off(a, 'click', handler);
				handlers.delete(a);
			}
		});
	},
};

export default FxSmoothScroll;
