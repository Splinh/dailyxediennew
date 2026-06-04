// offcanvas/fx-overlay.js

import { create, append, addClass, removeClass } from '../../dom.js';

const CLASS = 'js-off-canvas-overlay';
const LOCK = 'is-off-canvas-open';

let overlay = null;

export const getOverlay = () => {
	if (!overlay) {
		overlay = create('div', { class: `${CLASS} is-overlay-fixed` });
		append(document.body, overlay);
	}
	return overlay;
};

export const lockScroll = () => {
	const scrollY = window.scrollY;
	document.body.style.top = `-${scrollY}px`;
	document.body.style.position = 'fixed';
	document.body.style.width = '100%';
	document.body.dataset.scrollY = String(scrollY);
	addClass(document.body, LOCK);
};

export const unlockScroll = () => {
	const scrollY = parseFloat(document.body.dataset.scrollY || '0');
	document.body.style.top = '';
	document.body.style.position = '';
	document.body.style.width = '';
	delete document.body.dataset.scrollY;
	removeClass(document.body, LOCK);
	window.scrollTo(0, scrollY);
};
