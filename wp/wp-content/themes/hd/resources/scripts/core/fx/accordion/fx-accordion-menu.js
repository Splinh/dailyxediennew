// accordion/fx-accordion-menu.js

import { $ as qs, $$ as qsa, off, on, uid, create, addClass, removeClass, hasClass } from '../../dom.js';
import Events from '../../events.js';
import { createWeakStore } from '../../weak.js';

const SELECTOR = '[data-fx-accordion-menu]';
const PARENT_CLASS = 'is-accordion-submenu-parent';
const SUBMENU_CLASS = 'is-accordion-submenu';

const toggleHandlers = createWeakStore();
const linkHandlers = createWeakStore();

const FxAccordionMenu = {
	initAll(root = document) {
		qsa(SELECTOR, root).forEach((menu) => {
			const allowMulti = menu.dataset.multiSelectable === 'true';
			const autoToggle = menu.dataset.submenuToggle === 'true';

			menu.setAttribute('role', 'menubar');

			qsa('li:has(> ul)', menu).forEach((li) => {
				const link = qs(':scope > a, :scope > button', li);
				const sub = qs(':scope > ul', li);

				if (!link || !sub) return;

				li.setAttribute('role', 'none');
				link.setAttribute('role', 'menuitem');

				// ---- Toggle button ----
				let toggleBtn = qs(':scope > .submenu-toggle', li);
				if (!toggleBtn) {
					toggleBtn = create('button', { class: 'submenu-toggle', html: '<span class="submenu-toggle-text">Toggle menu</span>' });

					toggleBtn.id = uid('acc-menu-link');
					link.after(toggleBtn);
				}

				// ---- Accessibility ----
				const submenuId = uid('acc-menu');

				sub.id = submenuId;
				addClass(sub, SUBMENU_CLASS);
				sub.setAttribute('role', 'group');
				sub.setAttribute('aria-labelledby', toggleBtn.id);

				const isOpen = hasClass(li, 'active') || hasClass(li, 'is-active');

				toggleBtn.setAttribute('aria-controls', submenuId);
				toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
				sub.setAttribute('aria-hidden', isOpen ? 'false' : 'true');

				if (!isOpen) addClass(sub, 'hidden');
				addClass(li, PARENT_CLASS);

				// ---- Handler ----
				const toggleHandler = (e) => {
					e.preventDefault();
					e.stopPropagation();

					const expanded = toggleBtn.getAttribute('aria-expanded') === 'true';

					if (!allowMulti) {
						qsa(`.${PARENT_CLASS}`, menu).forEach((other) => {
							if (other !== li) FxAccordionMenu._close(other);
						});
					}

					expanded ? FxAccordionMenu._close(li) : FxAccordionMenu._open(li);
					Events.emit('fx:accordionmenu:toggle', { li, submenu: sub });
				};

				// Remove stale handler before re-attaching (idempotent init)
				const existingToggle = toggleHandlers.get(toggleBtn);
				if (existingToggle) off(toggleBtn, 'click', existingToggle);
				toggleHandlers.set(toggleBtn, toggleHandler);
				on(toggleBtn, 'click', toggleHandler);

				// auto toggle via link
				if (autoToggle && link.tagName.toLowerCase() === 'a') {
					const linkHandler = (e) => {
						if (link.getAttribute('href') === '#') {
							e.preventDefault();
							toggleHandler(e);
						}
					};

					const existingLink = linkHandlers.get(link);
					if (existingLink) off(link, 'click', existingLink);
					linkHandlers.set(link, linkHandler);
					on(link, 'click', linkHandler);
				}
			});
		});
	},

	destroyAll(root = document) {
		qsa(SELECTOR, root).forEach((menu) => {
			qsa('.submenu-toggle', menu).forEach((btn) => {
				const h = toggleHandlers.get(btn);
				if (h) {
					off(btn, 'click', h);
					toggleHandlers.delete(btn);
				}
			});

			qsa('a, button', menu).forEach((el) => {
				const h = linkHandlers.get(el);
				if (h) {
					off(el, 'click', h);
					linkHandlers.delete(el);
				}
			});
		});
	},

	// ---------- Helper ----------
	_open(li) {
		const btn = qs('.submenu-toggle', li);
		const sub = qs(':scope > ul', li);
		if (!btn || !sub) return;

		removeClass(sub, 'hidden');
		btn.setAttribute('aria-expanded', 'true');
		sub.setAttribute('aria-hidden', 'false');
		addClass(li, 'active is-active');
	},

	_close(li) {
		const btn = qs('.submenu-toggle', li);
		const sub = qs(':scope > ul', li);
		if (!btn || !sub) return;

		addClass(sub, 'hidden');
		btn.setAttribute('aria-expanded', 'false');
		sub.setAttribute('aria-hidden', 'true');
		removeClass(li, 'active is-active');
	},
};

export default FxAccordionMenu;
