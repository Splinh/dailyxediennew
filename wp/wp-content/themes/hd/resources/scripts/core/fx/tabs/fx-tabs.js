// tabs/fx-tabs.js
import './fx-tabs.scss';

import { $ as qs, $$ as qsa, off, delegate, hasClass, addClass, removeClass, toggleClass } from '../../dom.js';
import Events from '../../events.js';
import { createWeakStore } from '../../weak.js';

const DATA_TABS = '[data-fx-tabs]';
const DATA_TABS_CONTENT = 'data-fx-tabs-content';
const ACTIVE = 'is-active';
const TAB_BTN = '.tabs-title > a, .tabs-title > button';

const delegatedHandlers = createWeakStore();
const keyHandlers = createWeakStore();
const tabMeta = createWeakStore();
/** Saved href values for <a> tabs, restored on destroyAll */
const savedHrefs = new WeakMap();

/**
 * Index-based tab matching.
 * Buttons[i] → Panels[i] by position, no href/id coupling needed.
 * Works with both <a> and <button> triggers.
 */
const FxTabs = {
	initAll(root = document) {
		qsa(DATA_TABS, root).forEach((tabList) => {
			const collapse = tabList.dataset.activeCollapse === 'true';
			const tabListId = tabList.id;
			if (!tabListId) return;

			const content = qs(`[${DATA_TABS_CONTENT}="${tabListId}"]`, root);
			if (!content) return;

			tabList.setAttribute('role', 'tablist');

			const buttons = qsa(TAB_BTN, tabList);
			const panels = qsa(':scope > .tabs-panel', content);
			const titles = qsa('.tabs-title', tabList);

			// Generate a unique prefix for a11y IDs
			const a11yPrefix = `${tabListId}-tab`;

			// Init state — match buttons to panels by index
			buttons.forEach((btn, i) => {
				const li = btn.parentElement;
				const panel = panels.at(i);
				if (!panel) return;

				const active = hasClass(li, ACTIVE);

				// Ensure panel has a unique ID for aria-controls
				if (!panel.id) {
					panel.id = `${a11yPrefix}-panel-${i}`;
				}

				// Ensure button has a unique ID for aria-labelledby
				if (!btn.id) {
					btn.id = `${a11yPrefix}-${i}`;
				}

				// Strip href if it's a fragment — save original for restore on destroy
				if (btn.tagName === 'A' && btn.getAttribute('href')?.startsWith('#')) {
					savedHrefs.set(btn, btn.getAttribute('href'));
					btn.removeAttribute('href');
				}

				// role="presentation" makes li invisible to a11y tree
				// so role="tab" becomes direct child of role="tablist"
				li.setAttribute('role', 'presentation');

				btn.setAttribute('role', 'tab');
				btn.setAttribute('aria-controls', panel.id);
				btn.setAttribute('aria-selected', String(active));

				panel.setAttribute('role', 'tabpanel');
				panel.setAttribute('aria-labelledby', btn.id);
				panel.setAttribute('aria-hidden', String(!active));
				toggleClass(panel, ACTIVE, active);
			});

			// Store metadata for handler access
			tabMeta.set(tabList, { collapse, content, titles, panels, buttons });

			// Roving tabindex — only the active tab is keyboard-reachable
			buttons.forEach((b, i) => {
				const isActive = hasClass(b.parentElement, ACTIVE);
				b.setAttribute('tabindex', isActive ? '0' : '-1');
			});

			// Event delegation: 1 handler per tabList
			const handler = (e, btn) => {
				e.preventDefault();

				const meta = tabMeta.get(tabList);
				if (!meta) return;

				const btnIndex = meta.buttons.indexOf(btn);
				if (btnIndex === -1) return;

				const panel = meta.panels[btnIndex];
				if (!panel) return;

				const li = btn.parentElement;
				const isActive = hasClass(li, ACTIVE);

				if (meta.collapse && isActive) {
					removeClass(li, ACTIVE);
					btn.setAttribute('aria-selected', 'false');
					removeClass(panel, ACTIVE);
					panel.setAttribute('aria-hidden', 'true');

					Events.emit('fx:tabs:change', { tab: btn, panel, wrapper: tabList });
					return;
				}

				if (isActive) return;

				meta.titles.forEach((t) => removeClass(t, ACTIVE));
				meta.panels.forEach((p) => {
					removeClass(p, ACTIVE);
					p.setAttribute('aria-hidden', 'true');
				});
				meta.buttons.forEach((b) => {
					b.setAttribute('aria-selected', 'false');
					b.setAttribute('tabindex', '-1');
				});

				addClass(li, ACTIVE);
				btn.setAttribute('aria-selected', 'true');
				btn.setAttribute('tabindex', '0');
				addClass(panel, ACTIVE);
				panel.setAttribute('aria-hidden', 'false');

				Events.emit('fx:tabs:change', { tab: btn, panel, wrapper: tabList });
			};

			// delegate() returns wrapper function for cleanup
			const wrapperFn = delegate(tabList, TAB_BTN, 'click', handler);
			delegatedHandlers.set(tabList, wrapperFn);

			// Arrow key / Home / End navigation (WCAG AA roving tabindex)
			const keyHandler = (e) => {
				const meta = tabMeta.get(tabList);
				if (!meta) return;
				const { buttons: btns } = meta;
				const idx = btns.indexOf(document.activeElement);
				if (idx === -1) return;

				let next = -1;
				if (e.key === 'ArrowRight' || e.key === 'ArrowDown') next = (idx + 1) % btns.length;
				else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') next = (idx - 1 + btns.length) % btns.length;
				else if (e.key === 'Home') next = 0;
				else if (e.key === 'End') next = btns.length - 1;
				else return;

				e.preventDefault();
				btns[next].focus();
			};
			tabList.addEventListener('keydown', keyHandler);
			keyHandlers.set(tabList, keyHandler);
		});
	},

	destroyAll(root = document) {
		qsa(DATA_TABS, root).forEach((tabList) => {
			const wrapperFn = delegatedHandlers.get(tabList);
			if (wrapperFn) {
				off(tabList, 'click', wrapperFn);
				delegatedHandlers.delete(tabList);
			}
			const keyFn = keyHandlers.get(tabList);
			if (keyFn) {
				tabList.removeEventListener('keydown', keyFn);
				keyHandlers.delete(tabList);
			}
			// Restore stripped hrefs
			qsa(TAB_BTN, tabList).forEach((btn) => {
				const href = savedHrefs.get(btn);
				if (href) {
					btn.setAttribute('href', href);
					savedHrefs.delete(btn);
				}
				btn.removeAttribute('tabindex');
			});
			tabMeta.delete(tabList);
		});
	},
};

export default FxTabs;
