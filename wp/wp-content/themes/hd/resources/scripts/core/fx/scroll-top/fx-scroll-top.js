// scroll-top/fx-scroll-top.js

import { $$ as qsa, on, off, toggleClass, removeClass } from '../../dom.js';
import { createWeakStore } from '../../weak.js';

const SELECTOR = '[data-fx-scroll-top]';
const instanceStore = createWeakStore();

/** Easing: ease-in-out cubic */
const easeInOutCubic = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);

/** Active animation frame ID */
let rafId = null;

/**
 * Smooth scroll to top using requestAnimationFrame.
 * Independent of CSS scroll-behavior.
 *
 * @param {number} [duration=500] - Animation duration in ms
 */
const scrollToTop = (duration = 500) => {
	// Cancel any in-progress animation
	if (rafId) {
		cancelAnimationFrame(rafId);
		rafId = null;
	}

	const startY = window.scrollY;
	if (startY < 1) return;

	let startTime = null;

	const step = (currentTime) => {
		if (!startTime) startTime = currentTime;

		const elapsed = currentTime - startTime;
		const progress = Math.min(elapsed / duration, 1);
		const eased = easeInOutCubic(progress);

		window.scrollTo(0, startY * (1 - eased));

		if (progress < 1) {
			rafId = requestAnimationFrame(step);
		} else {
			rafId = null;
		}
	};

	rafId = requestAnimationFrame(step);
};

/**
 * Create scroll-top instance
 * @param {HTMLElement} btn - Button element
 * @returns {Object}
 */
const createInstance = (btn) => {
	const threshold = parseInt(btn.dataset.scrollStart, 10) || 300;
	const showClass = btn.dataset.showClass || 'back-to-top__show';

	let ticking = false;

	// Scroll handler
	const onScroll = () => {
		if (ticking) return;
		ticking = true;

		requestAnimationFrame(() => {
			const show = window.scrollY > threshold;
			toggleClass(btn, showClass, show);
			btn.dataset.show = show ? 'true' : 'false';
			ticking = false;
		});
	};

	// Click handler
	const onClick = (e) => {
		e.preventDefault();
		scrollToTop();
	};

	// Bind
	on(window, 'scroll', onScroll, { passive: true });
	on(btn, 'click', onClick);
	onScroll(); // Check initial state

	return {
		destroy() {
			if (rafId) {
				cancelAnimationFrame(rafId);
				rafId = null;
			}
			off(window, 'scroll', onScroll);
			off(btn, 'click', onClick);
			removeClass(btn, showClass);
			btn.dataset.show = 'false';
		},
	};
};

const FxScrollTop = {
	/**
	 * Initialize all scroll-top buttons in root
	 * @param {Document|Element} root - Root element to search
	 */
	initAll(root = document) {
		qsa(SELECTOR, root).forEach(
			/** @param {HTMLElement} btn */
			(btn) => {
				if (instanceStore.has(btn)) return;
				instanceStore.set(btn, createInstance(btn));
			},
		);
	},

	/**
	 * Destroy all scroll-top instances in root
	 * @param {Document|Element} root - Root element to search
	 */
	destroyAll(root = document) {
		qsa(SELECTOR, root).forEach(
			/** @param {HTMLElement} btn */
			(btn) => {
				instanceStore.cleanup(btn, (inst) => inst.destroy());
			},
		);
	},

	/**
	 * Scroll to top programmatically
	 */
	scrollToTop,
};

export default FxScrollTop;
