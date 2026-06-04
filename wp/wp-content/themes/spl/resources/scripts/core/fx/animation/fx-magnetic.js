// animation/fx-magnetic.js
// Magnetic effect — elements subtly follow the cursor when hovered.
// Desktop-only (pointer:fine). Uses GSAP for buttery-smooth spring physics.

import { $$ as qsa, on, off } from '../../dom.js';
import { createWeakStore } from '../../weak.js';
import Events from '../../events.js';
import gsap from 'gsap';

const SELECTOR = '[data-fx-magnetic]';

const store = createWeakStore();

/** Shared media query — evaluated once, auto-updates on device change */
const pointerFine = window.matchMedia('(pointer: fine)');

/**
 * Parse strength from data attribute (0–1), default 0.3.
 * @param {HTMLElement} el
 * @returns {number}
 */
const getStrength = (el) => {
	const v = parseFloat(el.dataset.magneticStrength);
	return Number.isFinite(v) ? Math.min(Math.max(v, 0), 1) : 0.3;
};

/**
 * Parse trigger area multiplier.
 * 1 = element bounds only, 1.5 = 50% larger hitbox (default).
 * @param {HTMLElement} el
 * @returns {number}
 */
const getTrigger = (el) => {
	const v = parseFloat(el.dataset.magneticTrigger);
	return Number.isFinite(v) && v >= 1 ? v : 1;
};

/**
 * Bind magnetic effect to a single element.
 * @param {HTMLElement} el
 */
const bind = (el) => {
	if (store.has(el)) return;

	const strength = getStrength(el);
	const trigger = getTrigger(el);
	const childSelector = el.dataset.magneticChild || null;

	const state = { rafId: 0 };

	const onMove = (e) => {
		cancelAnimationFrame(state.rafId);
		state.rafId = requestAnimationFrame(() => {
			const rect = el.getBoundingClientRect();

			// Centre of element
			const cx = rect.left + rect.width / 2;
			const cy = rect.top + rect.height / 2;

			// Expand hitbox by trigger multiplier
			const halfW = (rect.width / 2) * trigger;
			const halfH = (rect.height / 2) * trigger;

			const dx = e.clientX - cx;
			const dy = e.clientY - cy;

			// Only apply if cursor is inside the expanded hitbox
			if (Math.abs(dx) > halfW || Math.abs(dy) > halfH) return;

			const targetX = dx * strength;
			const targetY = dy * strength;

			gsap.to(el, {
				x: targetX,
				y: targetY,
				duration: 0.4,
				ease: 'power3.out',
				overwrite: 'auto',
			});

			// Optionally move an inner child at a different rate for parallax feel
			if (childSelector) {
				const child = el.querySelector(childSelector);
				if (child) {
					gsap.to(child, {
						x: targetX * 0.6,
						y: targetY * 0.6,
						duration: 0.4,
						ease: 'power3.out',
						overwrite: 'auto',
					});
				}
			}
		});
	};

	const onLeave = () => {
		cancelAnimationFrame(state.rafId);

		gsap.to(el, {
			x: 0,
			y: 0,
			duration: 1,
			ease: 'elastic.out(1, 0.3)',
			overwrite: 'auto',
		});

		if (childSelector) {
			const child = el.querySelector(childSelector);
			if (child) {
				gsap.to(child, {
					x: 0,
					y: 0,
					duration: 1,
					ease: 'elastic.out(1, 0.3)',
					overwrite: 'auto',
				});
			}
		}
	};

	on(el, 'mousemove', onMove);
	on(el, 'mouseleave', onLeave);

	store.set(el, { onMove, onLeave, state });

	Events.emit('fx:magnetic:init', { el });
};

/**
 * Unbind magnetic effect from a single element.
 * @param {HTMLElement} el
 */
const unbind = (el) => {
	store.cleanup(el, ({ onMove, onLeave, state }) => {
		cancelAnimationFrame(state.rafId);
		off(el, 'mousemove', onMove);
		off(el, 'mouseleave', onLeave);
		gsap.set(el, { x: 0, y: 0 });
	});
};

const FxMagnetic = {
	initAll(root = document) {
		// Skip entirely on touch / no-mouse devices
		if (!pointerFine.matches) return;

		qsa(SELECTOR, root).forEach(bind);
	},

	destroyAll(root = document) {
		qsa(SELECTOR, root).forEach(unbind);
	},
};

export default FxMagnetic;
