// dropdown/fx-dropdown-menu.js
import './fx-dropdown-menu.scss';

import { $ as qs, $$ as qsa, on, off, create, addClass, removeClass, toggleClass, hasClass } from '../../dom.js';
import Events from '../../events.js';
import { createWeakStore } from '../../weak.js';

const ROOT = '[data-fx-dropdown-menu]';
const OPEN = 'is-active';
const DELAY = { open: 80, close: 120 };

const FxDropdownMenu = {
	_handlers: createWeakStore(),
	_observers: createWeakStore(),

	initAll(root = document) {
		qsa(ROOT, root).forEach((menu) => {
			this._observers.get(menu)?.disconnect();
			this._observers.delete(menu);
			this._destroyMenu(menu);

			this.initMenu(menu);
			if (menu.dataset.autohide === 'true') this.initAutoHide(menu);
		});
	},

	initMenu(menu, useHover = menu.dataset?.hover === 'true') {
		menu.setAttribute('role', 'menubar');

		qsa('li', menu).forEach((li) => {
			const sub = qs(':scope > ul', li);
			const btn = qs(':scope > a, :scope > button', li);

			if (!sub || !btn) {
				li.setAttribute('role', 'none');
				btn?.setAttribute('role', 'menuitem');
				return;
			}

			addClass(li, 'is-dropdown-submenu-parent is-dropdown-submenu-item');
			addClass(sub, 'is-dropdown-submenu');
			if (!li.closest('.is-dropdown-submenu')) addClass(sub, 'first-sub');

			li.setAttribute('role', 'none');
			btn.setAttribute('role', 'menuitem');
			btn.setAttribute('aria-haspopup', 'true');
			btn.setAttribute('aria-expanded', 'false');
			sub.setAttribute('role', 'menu');

			if (useHover) {
				const timers = { openT: undefined, closeT: undefined };
				const enter = () => {
					clearTimeout(timers.closeT);
					timers.openT = setTimeout(() => this.open(li, btn, sub), DELAY.open);
				};
				const leave = () => {
					clearTimeout(timers.openT);
					timers.closeT = setTimeout(() => this.close(li, btn, sub), DELAY.close);
				};

				this._handlers.set(li, { type: 'hover', enter, leave, timers });
				on(li, 'mouseenter', enter);
				on(li, 'mouseleave', leave);
			} else {
				const handler = (e) => {
					e.preventDefault();
					const isOpen = !hasClass(li, OPEN);
					toggleClass(li, OPEN, isOpen);
					btn.setAttribute('aria-expanded', isOpen);
					sub.setAttribute('aria-hidden', !isOpen);
					isOpen ? requestAnimationFrame(() => this.applyAutoPosition(li, sub)) : removeClass(li, 'opens-left opens-right');
					Events.emit('fx:dropdownmenu:toggle', { li, sub, isOpen });
				};
				this._handlers.set(btn, { type: 'click', handler });
				on(btn, 'click', handler);
			}
		});
	},

	open(li, btn, sub) {
		addClass(li, OPEN);
		btn.setAttribute('aria-expanded', 'true');
		sub.setAttribute('aria-hidden', 'false');
		requestAnimationFrame(() => {
			this.applyAutoPosition(li, sub);
			Events.emit('fx:dropdownmenu:open', { li, sub });
		});
	},

	close(li, btn, sub) {
		removeClass(li, `${OPEN} opens-left opens-right`);
		btn.setAttribute('aria-expanded', 'false');
		sub.setAttribute('aria-hidden', 'true');
		Events.emit('fx:dropdownmenu:close', { li, sub });
	},

	applyAutoPosition(li, sub) {
		const r = sub.getBoundingClientRect().right;
		toggleClass(li, 'opens-left', r > window.innerWidth);
		toggleClass(li, 'opens-right', r <= window.innerWidth);
	},

	// AUTOHIDE - collapses overflow items into "More" dropdown
	// Optimized: Cache measurements, debounced resize, minimal DOM access
	initAutoHide(menu) {
		const container = menu.dataset.autohideContainer ? menu.closest(menu.dataset.autohideContainer) : menu.parentElement;
		if (!container) return;

		qsa(':scope > .fx-more', menu).forEach((li) => li.remove());

		let more = null;
		let cachedWidths = null;
		let cachedMoreW = 0;
		let cachedGap = 0;
		let lastContainerWidth = container.clientWidth;

		// Invalidate cache when needed (font load, significant resize)
		const invalidateCache = () => {
			cachedWidths = null;
			cachedMoreW = 0;
		};

		const withVisibleItems = (items, callback) => {
			const hiddenItems = items.filter((li) => hasClass(li, 'hidden!'));
			const previousVisibility = menu.style.visibility;

			menu.style.visibility = 'hidden';
			hiddenItems.forEach((li) => removeClass(li, 'hidden!'));

			try {
				return callback();
			} finally {
				hiddenItems.forEach((li) => addClass(li, 'hidden!'));
				menu.style.visibility = previousVisibility;
			}
		};

		const clearMenu = (el) => {
			el.replaceChildren();
		};

		const sanitizeClone = (el) => {
			el.removeAttribute('id');
			el.removeAttribute('aria-controls');
			el.removeAttribute('aria-labelledby');

			qsa('[id], [aria-controls], [aria-labelledby]', el).forEach((node) => {
				node.removeAttribute('id');
				node.removeAttribute('aria-controls');
				node.removeAttribute('aria-labelledby');
			});
		};

		// Measure all widths once - cached for performance
		const measureWidths = () => {
			const items = [...menu.children].filter((li) => li !== more);
			const style = getComputedStyle(menu);
			cachedGap = parseFloat(style.gap) || parseFloat(style.columnGap) || 0;

			// Measure More button only once
			if (more && cachedMoreW === 0) {
				more.style.cssText = 'visibility:hidden;position:absolute;pointer-events:none;';
				cachedMoreW = more.offsetWidth;
				more.style.cssText = '';
				addClass(more, 'hidden!');
			}

			// Cache item widths - only measure once
			if (!cachedWidths) {
				cachedWidths = withVisibleItems(items, () => items.map((li) => li.offsetWidth));
			}

			return { items, widths: cachedWidths, moreW: cachedMoreW, gap: cachedGap };
		};

		// Recalculate visible items
		const recalculate = () => {
			if (!more) return;

			const dropdown = qs('.submenu', more);
			if (!dropdown) return;

			const { items, widths, moreW, gap } = measureWidths();
			const containerW = container.clientWidth;
			const total = widths.reduce((s, w) => s + w, 0) + gap * (items.length - 1);

			// All items fit
			if (total <= containerW) {
				items.forEach((li) => removeClass(li, 'hidden!'));
				clearMenu(dropdown);
				addClass(more, 'hidden!');
				addClass(menu, 'is-adjusted');
				this.reinitMenu(menu);
				return;
			}

			// Find cut point
			const available = containerW - moreW - gap;
			let used = 0,
				cut = items.length;

			for (let i = 0; i < items.length; i++) {
				const w = widths.at(i) + (i > 0 ? gap : 0);
				if (used + w > available) {
					cut = i;
					break;
				}
				used += w;
			}

			// Batch writes in rAF
			requestAnimationFrame(() => {
				items.forEach((li) => removeClass(li, 'hidden!'));
				clearMenu(dropdown);

				if (cut < items.length) {
					const frag = document.createDocumentFragment();
					for (let i = cut; i < items.length; i++) {
						addClass(items.at(i), 'hidden!');
						const clone = items.at(i).cloneNode(true);
						removeClass(clone, 'hidden!');
						sanitizeClone(clone);
						frag.appendChild(clone);
					}
					dropdown.appendChild(frag);
					removeClass(more, 'hidden!');
				} else {
					addClass(more, 'hidden!');
				}

				addClass(menu, 'is-adjusted');
				this.reinitMenu(menu);
			});
		};

		// Create More button and initialize
		const createMoreAndInit = () => {
			const moreLabel = menu.dataset.moreLabel || 'More';

			more = create('li', { class: 'fx-more has-dropdown hidden!' });

			const moreLink = create('a', {
				href: '#',
				role: 'menuitem',
				'aria-haspopup': 'true',
				'aria-expanded': 'false',
				text: moreLabel,
			});
			const submenu = create('ul', { class: 'submenu vertical menu is-dropdown-submenu' });

			more.append(moreLink, submenu);
			menu.appendChild(more);
			recalculate();
		};

		// Initial check
		const init = () => {
			if (menu.scrollWidth <= container.clientWidth) {
				addClass(menu, 'is-adjusted');
				return;
			}
			createMoreAndInit();
		};

		// Run after fonts load - invalidate cache as font metrics changed
		(document.fonts?.ready || Promise.resolve()).then(() => {
			invalidateCache();
			requestAnimationFrame(init);
		});

		// ResizeObserver with debounce
		if (window.ResizeObserver) {
			let firstCall = true;
			const ro = new ResizeObserver(() => {
				if (firstCall) {
					firstCall = false;
					return;
				}
				clearTimeout(ro._t);
				ro._t = setTimeout(() => {
					// Invalidate cache on significant width change
					const currentWidth = container.clientWidth;
					if (Math.abs(currentWidth - lastContainerWidth) > 50) {
						invalidateCache();
						lastContainerWidth = currentWidth;
					}

					// If no More button yet but now overflows, create it
					if (!more && menu.scrollWidth > container.clientWidth) {
						createMoreAndInit();
					} else if (more) {
						recalculate();
					}
				}, 150);
			});
			ro.observe(container);
			this._observers.set(menu, ro);
		}
	},

	reinitMenu(menu) {
		// destroyAll searches INSIDE root, so we must directly clean up the menu's handlers
		this._destroyMenu(menu);
		this.initMenu(menu);
	},

	_destroyMenu(menu) {
		qsa('li', menu).forEach((li) => {
			const h = this._handlers.get(li);
			if (h?.type === 'hover') {
				off(li, 'mouseenter', h.enter);
				off(li, 'mouseleave', h.leave);
				clearTimeout(h.timers?.openT);
				clearTimeout(h.timers?.closeT);
			}
			this._handlers.delete(li);

			const btn = qs(':scope > a, :scope > button', li);
			const c = this._handlers.get(btn);
			if (c?.type === 'click') off(btn, 'click', c.handler);
			this._handlers.delete(btn);
		});
	},

	destroyAll(root = document) {
		qsa(ROOT, root).forEach((menu) => {
			this._observers.get(menu)?.disconnect();
			this._observers.delete(menu);
			this._destroyMenu(menu);
		});
	},
};

export default FxDropdownMenu;
