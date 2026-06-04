// sticky/fx-sticky.js

import { $$ as qsa, on, off, create, css, addClass, removeClass, toggleClass } from '../../dom.js';
import { createWeakStore } from '../../weak.js';
import Events from '../../events.js';

const SELECTOR = '[data-fx-sticky]';

const instanceStore = createWeakStore();

const getStickyElements = (root = document) => {
	const elements = qsa(SELECTOR, root);
	return root.nodeType === 1 && root.matches?.(SELECTOR) ? [root, ...elements] : elements;
};

/**
 * Parse options from data attributes
 * @param {HTMLElement} el - Sticky element
 * @returns {Object}
 */
const parseOptions = (el) => ({
	stickyClass: el.dataset.stickyClass || 'is-sticky',
	offset: parseInt(el.dataset.stickyOffset, 10) || 0,
	// 'up' = show on scroll up, 'down' = show on scroll down, 'both' = always when past threshold
	direction: el.dataset.stickyDirection || 'both',
});

/**
 * Create sticky instance for an element
 * @param {HTMLElement} el - Element to make sticky
 * @returns {Object} - Instance with destroy method
 */
const createStickyInstance = (el) => {
	const options = parseOptions(el);
	const { stickyClass, offset, direction } = options;

	let lastScrollY = window.scrollY;
	let isSticky = false;
	let sentinel = null;
	let observer = null;
	let scrollHandler = null;

	// Create sentinel element for IntersectionObserver
	sentinel = create('div');
	css(sentinel, {
		display: 'block',
		width: '1px',
		height: '0',
		marginTop: offset ? `-${offset}px` : '0',
		opacity: '0',
		visibility: 'hidden',
		pointerEvents: 'none',
	});
	el.parentNode.insertBefore(sentinel, el);

	/**
	 * Update sticky state
	 * @param {boolean} sticky - Should be sticky
	 * @param {string|null} scrollDir - 'up' or 'down'
	 */
	const updateSticky = (sticky, scrollDir = null) => {
		if (direction === 'both') {
			// Simple mode: sticky when past threshold
			if (sticky !== isSticky) {
				isSticky = sticky;
				toggleClass(el, stickyClass, sticky);
				Events.emit('fx:sticky:change', { el, isSticky, direction: scrollDir });
			}
		} else if (direction === 'up') {
			// Show on scroll up (hide on scroll down)
			if (sticky && scrollDir === 'up') {
				if (!isSticky) {
					isSticky = true;
					addClass(el, stickyClass);
					Events.emit('fx:sticky:change', { el, isSticky, direction: scrollDir });
				}
			} else if (!sticky || scrollDir === 'down') {
				if (isSticky) {
					isSticky = false;
					removeClass(el, stickyClass);
					Events.emit('fx:sticky:change', { el, isSticky, direction: scrollDir });
				}
			}
		} else if (direction === 'down') {
			// Show on scroll down (hide on scroll up)
			if (sticky && scrollDir === 'down') {
				if (!isSticky) {
					isSticky = true;
					addClass(el, stickyClass);
					Events.emit('fx:sticky:change', { el, isSticky, direction: scrollDir });
				}
			} else if (!sticky || scrollDir === 'up') {
				if (isSticky) {
					isSticky = false;
					removeClass(el, stickyClass);
					Events.emit('fx:sticky:change', { el, isSticky, direction: scrollDir });
				}
			}
		}
	};

	// IntersectionObserver for threshold detection
	observer = new IntersectionObserver(
		([entry]) => {
			const scrollDir = window.scrollY > lastScrollY ? 'down' : 'up';
			updateSticky(!entry.isIntersecting, scrollDir);
			lastScrollY = window.scrollY;
		},
		{
			rootMargin: '0px',
			threshold: 0,
		},
	);
	observer.observe(sentinel);

	// Scroll handler for direction detection (only if direction !== 'both')
	if (direction !== 'both') {
		let ticking = false;
		scrollHandler = () => {
			if (!ticking) {
				requestAnimationFrame(() => {
					const currentScrollY = window.scrollY;
					const scrollDir = currentScrollY > lastScrollY ? 'down' : 'up';

					// Check if past threshold
					const rect = sentinel.getBoundingClientRect();
					const isPastThreshold = rect.top < 0;

					updateSticky(isPastThreshold, scrollDir);
					lastScrollY = currentScrollY;
					ticking = false;
				});
				ticking = true;
			}
		};
		on(window, 'scroll', scrollHandler, { passive: true });
	}

	// Check initial state
	const initialRect = el.getBoundingClientRect();
	if (initialRect.top <= offset) {
		addClass(el, stickyClass);
		isSticky = true;
	}

	return {
		el,
		options,
		destroy() {
			if (observer) {
				observer.disconnect();
				observer = null;
			}
			if (sentinel) {
				sentinel.remove();
				sentinel = null;
			}
			if (scrollHandler) {
				off(window, 'scroll', scrollHandler);
				scrollHandler = null;
			}
			removeClass(el, stickyClass);
		},
	};
};

const FxSticky = {
	/**
	 * Initialize all sticky elements in root
	 * @param {Document|Element} root - Root element to search
	 */
	initAll(root = document) {
		getStickyElements(root).forEach(
			/** @param {HTMLElement} el */
			(el) => {
				if (instanceStore.has(el)) return;

				const instance = createStickyInstance(el);
				instanceStore.set(el, instance);

				Events.emit('fx:sticky:init', { el, instance });
			},
		);
	},

	/**
	 * Destroy all sticky instances in root
	 * @param {Document|Element} root - Root element to search
	 */
	destroyAll(root = document) {
		getStickyElements(root).forEach(
			/** @param {HTMLElement} el */
			(el) => {
				instanceStore.cleanup(el, (instance) => {
					instance.destroy();
				});
			},
		);

		Events.emit('fx:sticky:destroy');
	},

	/**
	 * Manually init a specific element
	 * @param {HTMLElement} el - Element to init
	 * @returns {Object} - Instance
	 */
	init(el) {
		if (instanceStore.has(el)) return instanceStore.get(el);
		const instance = createStickyInstance(el);
		instanceStore.set(el, instance);
		return instance;
	},
};

export default FxSticky;
