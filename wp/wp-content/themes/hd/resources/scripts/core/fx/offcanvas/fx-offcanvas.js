// offcanvas/fx-offcanvas.js
import './fx-offcanvas.scss';

import { $ as qs, $$ as qsa, on, off, closest } from '../../dom.js';
import { createWeakStore } from '../../weak.js';
import { getOverlay } from './fx-overlay.js';
import { openOffCanvas, closeOffCanvas, isOpen } from './fx-offcanvas.core.js';

const OC = 'data-fx-off-canvas';
const OPEN = 'data-open';
const CLOSE = 'data-close';

const openHandlers = createWeakStore();
const closeHandlers = createWeakStore();
/** panel → Set<trigger btn> — used to reset aria-expanded on close */
const panelOpeners = new WeakMap();
/** panel → previously focused element — restored when panel closes */
const savedFocus = new WeakMap();
let overlayHandler = null;
let escHandler = null;

function getOpenPanels() {
	return qsa(`[${OC}].is-open`);
}

function afterClose(panel, overlay) {
	panelOpeners.get(panel)?.forEach((btn) => btn.setAttribute('aria-expanded', 'false'));
	savedFocus.get(panel)?.focus();
	savedFocus.delete(panel);

	if (escHandler && getOpenPanels().length === 0) {
		document.removeEventListener('keydown', escHandler);
		escHandler = null;
	}
}

const FxOffCanvas = {
	initAll(root = document) {
		const overlay = getOverlay();

		// OPEN
		qsa(`[${OPEN}]`, root).forEach((btn) => {
			const id = btn.getAttribute(OPEN);
			const panel = document.getElementById(id);
			if (!panel || !panel.hasAttribute(OC)) return;

			if (!panelOpeners.has(panel)) panelOpeners.set(panel, new Set());
			panelOpeners.get(panel).add(btn);

			const h = (e) => {
				e.preventDefault();
				if (isOpen(panel)) return;
				savedFocus.set(panel, document.activeElement);
				openOffCanvas(panel, overlay);
				btn.setAttribute('aria-expanded', 'true');

				if (!escHandler) {
					escHandler = (ev) => {
						if (ev.key !== 'Escape') return;
						getOpenPanels().forEach((p) => {
							closeOffCanvas(p, overlay);
							afterClose(p, overlay);
						});
					};
					document.addEventListener('keydown', escHandler);
				}

				const focusable = qs('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])', panel);
				requestAnimationFrame(() => focusable?.focus());
			};

			openHandlers.set(btn, h);
			on(btn, 'click', h);
		});

		// CLOSE
		qsa(`[${CLOSE}]`, root).forEach((btn) => {
			const sel = btn.getAttribute(CLOSE);
			const panel = sel ? qs(sel) : closest(btn, `[${OC}]`);
			if (!panel) return;

			const h = (e) => {
				e.preventDefault();
				closeOffCanvas(panel, overlay);
				afterClose(panel, overlay);
			};

			closeHandlers.set(btn, h);
			on(btn, 'click', h);
		});

		// OVERLAY CLICK -> CLOSE ALL (singleton)
		if (!overlayHandler) {
			overlayHandler = () => {
				qsa(`[${OC}]`).forEach((p) => {
					closeOffCanvas(p, overlay);
					afterClose(p, overlay);
				});
			};
			on(overlay, 'click', overlayHandler);
		}
	},

	destroyAll(root = document) {
		qsa(`[${OPEN}]`, root).forEach((btn) => {
			const h = openHandlers.get(btn);
			if (h) {
				off(btn, 'click', h);
				openHandlers.delete(btn);
			}
		});

		qsa(`[${CLOSE}]`, root).forEach((btn) => {
			const h = closeHandlers.get(btn);
			if (h) {
				off(btn, 'click', h);
				closeHandlers.delete(btn);
			}
		});

		// Only remove overlay/esc handlers when destroying entire document
		if (root === document) {
			if (overlayHandler) {
				off(getOverlay(), 'click', overlayHandler);
				overlayHandler = null;
			}
			if (escHandler) {
				document.removeEventListener('keydown', escHandler);
				escHandler = null;
			}
		}
	},
};

export default FxOffCanvas;
