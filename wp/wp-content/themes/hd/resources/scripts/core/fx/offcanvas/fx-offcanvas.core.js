// offcanvas/fx-offcanvas.core.js

import Events from '../../events.js';
import { $$ as qsa, trigger, hasClass, addClass, removeClass } from '../../dom.js';
import { lockScroll, unlockScroll } from './fx-overlay.js';
import { bindSwipe, unbindSwipe } from './fx-swipe.js';

export const isOpen = (panel) => hasClass(panel, 'is-open');

/** Count how many off-canvas panels are currently open */
const openCount = () => qsa('.is-open[data-fx-off-canvas]').length;

export const openOffCanvas = (panel, overlay) => {
	if (isOpen(panel)) return;

	addClass(panel, 'is-open');
	removeClass(panel, 'is-closed');
	addClass(overlay, 'is-visible is-closable');

	if (panel.dataset.contentScroll === 'false') lockScroll();
	bindSwipe(panel, overlay, () => closeOffCanvas(panel, overlay));

	Events.emit('fx:offcanvas:open', { el: panel });
	trigger(panel, 'fx.offcanvas.opened', { el: panel });
};

export const closeOffCanvas = (panel, overlay) => {
	if (!isOpen(panel)) return;

	removeClass(panel, 'is-open');
	addClass(panel, 'is-closed');

	unbindSwipe(panel);

	// Only hide overlay and unlock scroll when ALL panels are closed
	if (openCount() === 0) {
		removeClass(overlay, 'is-visible is-closable');
		unlockScroll();
	}

	Events.emit('fx:offcanvas:close', { el: panel });
	trigger(panel, 'fx.offcanvas.closed', { el: panel });
};
