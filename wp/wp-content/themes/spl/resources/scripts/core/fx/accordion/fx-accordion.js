// accordion/fx-accordion.js
import './fx-accordion.scss';

import { $ as qs, $$ as qsa, off, delegate, closest, hasClass, addClass, removeClass } from '../../dom.js';
import Events from '../../events.js';
import { createWeakStore } from '../../weak.js';

const SELECTOR = '[data-fx-accordion]';
const ITEM = '[data-fx-accordion-item]';
const CONTENT = '[data-fx-accordion-content]';
const TITLE = '[data-fx-accordion-title], .accordion-title';
const ACTIVE_CLASS = 'is-active';

const delegatedHandlers = createWeakStore();

const FxAccordion = {
	initAll(root = document) {
		qsa(SELECTOR, root).forEach((accordionEl) => {
			const allowAllClosed = accordionEl.dataset.allowAllClosed === 'true';
			const multiExpand = accordionEl.dataset.multiExpand === 'true';

			// Init aria state for all items
			qsa(ITEM, accordionEl).forEach((item) => {
				const btn = qs(TITLE, item);
				const panel = qs(CONTENT, item);
				if (!btn || !panel) return;

				const active = hasClass(item, ACTIVE_CLASS);
				btn.setAttribute('aria-expanded', active ? 'true' : 'false');
				panel.setAttribute('aria-hidden', active ? 'false' : 'true');
			});

			// Event delegation: 1 handler per accordion instead of N handlers per button
			const handler = (e, btn) => {
				e.preventDefault();

				const item = closest(btn, ITEM);
				const panel = item ? qs(CONTENT, item) : null;
				if (!item || !panel) return;

				const isOpen = hasClass(item, ACTIVE_CLASS);

				// CLOSE
				if (isOpen) {
					if (!allowAllClosed) {
						const openedCount = qsa(`${ITEM}.${ACTIVE_CLASS}`, accordionEl).length;
						if (openedCount <= 1) return;
					}

					removeClass(item, ACTIVE_CLASS);
					btn.setAttribute('aria-expanded', 'false');
					panel.setAttribute('aria-hidden', 'true');

					Events.emit('fx:accordion:close', { item, panel });
					return;
				}

				// OPEN
				if (!multiExpand) {
					qsa(ITEM, accordionEl).forEach((other) => {
						if (other !== item) {
							removeClass(other, ACTIVE_CLASS);
							const b = qs(TITLE, other);
							const p = qs(CONTENT, other);
							b?.setAttribute('aria-expanded', 'false');
							p?.setAttribute('aria-hidden', 'true');
						}
					});
				}

				addClass(item, ACTIVE_CLASS);
				btn.setAttribute('aria-expanded', 'true');
				panel.setAttribute('aria-hidden', 'false');

				Events.emit('fx:accordion:open', { item, panel });
			};

			// Remove stale handler before re-attaching (idempotent init)
			const existing = delegatedHandlers.get(accordionEl);
			if (existing) off(accordionEl, 'click', existing);

			const wrapperFn = delegate(accordionEl, TITLE, 'click', handler);
			delegatedHandlers.set(accordionEl, wrapperFn);
		});
	},

	destroyAll(root = document) {
		qsa(SELECTOR, root).forEach((accordionEl) => {
			const wrapperFn = delegatedHandlers.get(accordionEl);
			if (wrapperFn) {
				off(accordionEl, 'click', wrapperFn);
				delegatedHandlers.delete(accordionEl);
			}
		});
	},
};

export default FxAccordion;
