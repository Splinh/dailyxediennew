// counter/fx-counter.js
import './fx-counter.scss';

import { $$ as qsa, addClass, removeClass } from '../../dom.js';
import { createWeakStore } from '../../weak.js';
import Events from '../../events.js';

const SELECTOR = '[data-fx-counter]';
const COUNTER_CLASS = '.counter';

const observerStore = createWeakStore();

/** Per-element RAF tracking */
const rafMap = new WeakMap();

/**
 * Easing function for smooth animation (ease-out expo)
 * @param {number} t - Progress (0 to 1)
 * @returns {number}
 */
const ease = (t) => (t === 1 ? 1 : 1 - Math.pow(2, -10 * t));

/**
 * Format number with optional leading zeros
 * @param {number} value - Current value
 * @param {string} template - Original data-counter value (e.g., "08", "950")
 * @returns {string}
 */
const formatValue = (value, template) => {
	const needsPad = template.startsWith('0') && template.length > 1;
	return needsPad ? String(value).padStart(template.length, '0') : value.toLocaleString();
};

/**
 * Animate counter from 0 to end value
 * @param {HTMLElement} el - Counter element
 * @param {number} end - End value
 * @param {number} duration - Animation duration in ms
 */
const animate = (el, end, duration) => {
	// Cancel any in-flight animation
	const prevRaf = rafMap.get(el);
	if (prevRaf) {
		cancelAnimationFrame(prevRaf);
		rafMap.delete(el);
	}

	const start = performance.now();
	const template = el.dataset.counter;

	const tick = (now) => {
		const progress = Math.min((now - start) / duration, 1);
		const value = Math.floor(end * ease(progress));
		el.textContent = formatValue(value, template);

		if (progress < 1) {
			rafMap.set(el, requestAnimationFrame(tick));
		} else {
			rafMap.delete(el);
			// Ensure final value is exact
			el.textContent = formatValue(end, template);
			addClass(el, 'counter-completed');
			Events.emit('fx:counter:complete', { el, value: end });
		}
	};

	rafMap.set(el, requestAnimationFrame(tick));
};

/**
 * Reset counter to 0
 * @param {HTMLElement} el - Counter element
 */
const reset = (el) => {
	const raf = rafMap.get(el);
	if (raf) {
		cancelAnimationFrame(raf);
		rafMap.delete(el);
	}
	el.textContent = formatValue(0, el.dataset.counter);
	removeClass(el, 'counter-completed');
};

const FxCounter = {
	/**
	 * Initialize all counters in root
	 * @param {Document|Element} root - Root element to search
	 */
	initAll(root = document) {
		qsa(SELECTOR, root).forEach(
			/** @param {HTMLElement} container */
			(container) => {
				// Options from data attributes
				const once = container.dataset.once !== 'false'; // default: true (only count once)
				const duration = parseInt(container.dataset.duration, 10) || 2000;
				const threshold = parseFloat(container.dataset.threshold) || 0.3;

				// Find all counter elements
				const counters = qsa(COUNTER_CLASS, container);
				if (!counters.length) return;

				// Track if already counted (for once mode)
				let counted = false;

				// Create IntersectionObserver
				const observer = new IntersectionObserver(
					([entry]) => {
						if (entry.isIntersecting) {
							// Skip if already counted and once mode is enabled
							if (once && counted) return;

							counted = true;
							addClass(container, 'is-counting');

							// Start counting for each counter
							counters.forEach((el) => animate(el, parseInt(el.dataset.counter, 10) || 0, duration));

							Events.emit('fx:counter:start', { container, counters });

							// If once mode, disconnect after counting
							if (once) observer.disconnect();
						} else if (!once) {
							// Reset when out of view (only if not once mode)
							removeClass(container, 'is-counting');
							counters.forEach(reset);

							Events.emit('fx:counter:reset', { container, counters });
						}
					},
					{ threshold },
				);

				observer.observe(container);
				observerStore.set(container, observer);
			},
		);
	},

	/**
	 * Destroy all counters in root
	 * @param {Document|Element} root - Root element to search
	 */
	destroyAll(root = document) {
		qsa(SELECTOR, root).forEach(
			/** @param {HTMLElement} container */
			(container) => {
				observerStore.cleanup(container, (o) => o.disconnect());
				removeClass(container, 'is-counting');
			},
		);
	},
};

export default FxCounter;
