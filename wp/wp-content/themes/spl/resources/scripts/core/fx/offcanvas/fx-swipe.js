// offcanvas/fx-swipe.js

import { on, off, hasClass } from '../../dom.js';
import { createWeakStore } from '../../weak.js';

const THRESHOLD = 80;
const swipes = createWeakStore();

export const bindSwipe = (panel, overlay, onClose) => {
	if (swipes.has(panel)) return;

	let startX = 0;
	let startY = 0;
	let currX = 0;
	let currY = 0;
	let dragging = false;
	const right = hasClass(panel, 'position-right');

	const tStart = (e) => {
		if (!hasClass(panel, 'is-open')) return;
		dragging = true;
		startX = e.touches[0].clientX;
		startY = e.touches[0].clientY;
		currX = startX;
		currY = startY;
		panel.style.transition = 'none';
	};

	const tMove = (e) => {
		if (!dragging) return;
		currX = e.touches[0].clientX;
		currY = e.touches[0].clientY;
		const dx = currX - startX;
		const dy = currY - startY;

		// Only apply transform if horizontal movement > vertical (swipe, not scroll)
		if (Math.abs(dx) > Math.abs(dy)) {
			if ((right && dx > 0) || (!right && dx < 0)) {
				const w = panel.offsetWidth || 320;
				const d = Math.min(Math.abs(dx), w);
				panel.style.transform = `translate(${right ? d : -d}px)`;
				overlay.style.opacity = 1 - d / w;
			}
		}
	};

	const tEnd = () => {
		if (!dragging) return;
		dragging = false;

		panel.style.transition = '';
		panel.style.transform = '';
		overlay.style.opacity = '';

		const dx = Math.abs(currX - startX);
		const dy = Math.abs(currY - startY);

		// Only close if horizontal swipe is dominant and exceeds threshold
		if (dx > THRESHOLD && dx > dy) {
			const isSwipeOut = (right && currX > startX) || (!right && currX < startX);
			if (isSwipeOut) onClose();
		}
	};

	swipes.set(panel, { tStart, tMove, tEnd });

	on(panel, 'touchstart', tStart, { passive: true });
	on(document, 'touchmove', tMove, { passive: true });
	on(document, 'touchend', tEnd);
};

export const unbindSwipe = (panel) => {
	const h = swipes.get(panel);
	if (!h) return;

	off(panel, 'touchstart', h.tStart);
	off(document, 'touchmove', h.tMove);
	off(document, 'touchend', h.tEnd);

	swipes.delete(panel);
};
