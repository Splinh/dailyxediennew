// animation/fx-lottie.js
// Lottie animation component using @lottiefiles/dotlottie-wc
// This entire chunk is lazy-loaded by createLoader when [data-fx-lottie] is found.
import './fx-lottie.scss';

import '@lottiefiles/dotlottie-wc';

import { $$ as qsa, on, off, hasClass, addClass, removeClass } from '../../dom.js';
import { createWeakStore } from '../../weak.js';
import Events from '../../events.js';

const SELECTOR = '[data-fx-lottie]';
const LOADED = 'is-lottie-loaded';

const instanceStore = createWeakStore();
const observerStore = createWeakStore();
const hoverStore = createWeakStore();

/**
 * Build a <dotlottie-wc> element from container's data-* attributes.
 *
 * Supported attributes:
 *   data-fx-lottie       — (required) animation src URL (.json or .lottie)
 *   data-lottie-loop     — "true" (default) | "false"
 *   data-lottie-autoplay — "true" (default) | "false"
 *   data-lottie-speed    — number (default 1)
 *   data-lottie-mode     — "forward" | "reverse" | "bounce" | "reverse-bounce"
 *   data-lottie-hover    — "true" to play only on hover
 *
 * @param {HTMLElement} container
 */
const activate = (container) => {
	if (hasClass(container, LOADED)) return;
	addClass(container, LOADED);

	const src = container.dataset.fxLottie;
	if (!src) return;

	const d = container.dataset;
	const hoverOnly = d.lottieHover === 'true';

	const player = document.createElement('dotlottie-wc');
	player.setAttribute('src', src);
	player.setAttribute('style', 'width:100%;height:100%');

	if (d.lottieSpeed) player.setAttribute('speed', d.lottieSpeed);
	if (d.lottieMode) player.setAttribute('mode', d.lottieMode);

	// Loop & autoplay defaults to true unless explicitly "false"
	if (d.lottieLoop !== 'false') player.setAttribute('loop', '');
	if (!hoverOnly && d.lottieAutoplay !== 'false') player.setAttribute('autoplay', '');

	container.appendChild(player);
	instanceStore.set(container, player);

	// Hover-to-play mode
	if (hoverOnly) {
		const onEnter = () => player.dotLottie?.play();
		const onLeave = () => player.dotLottie?.pause();

		on(container, 'mouseenter', onEnter);
		on(container, 'mouseleave', onLeave);

		// Store handlers for cleanup
		hoverStore.set(container, { onEnter, onLeave });
	}

	Events.emit('fx:lottie:init', { el: container, player });
};

const FxLottie = {
	initAll(root = document) {
		qsa(SELECTOR, root).forEach((el) => {
			if (instanceStore.has(el)) return;

			// Lazy init via IntersectionObserver
			if ('IntersectionObserver' in window) {
				const observer = new IntersectionObserver(
					([entry], obs) => {
						if (entry.isIntersecting) {
							activate(entry.target);
							obs.unobserve(entry.target);
							observerStore.delete(entry.target);
						}
					},
					{ rootMargin: '200px' },
				);

				observer.observe(el);
				observerStore.set(el, observer);
			} else {
				activate(el);
			}
		});
	},

	destroyAll(root = document) {
		qsa(SELECTOR, root).forEach((el) => {
			// Disconnect lazy observer
			observerStore.cleanup(el, (obs) => obs.disconnect());

			// Remove hover handlers
			hoverStore.cleanup(el, ({ onEnter, onLeave }) => {
				off(el, 'mouseenter', onEnter);
				off(el, 'mouseleave', onLeave);
			});

			// Destroy dotlottie instance
			instanceStore.cleanup(el, (player) => {
				player.dotLottie?.destroy();
				player.remove();
			});

			// Allow re-init after destroy
			removeClass(el, LOADED);
		});
	},
};

export default FxLottie;
