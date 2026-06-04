// dropdown/fx-dropdown.js
import './fx-dropdown.scss';

import { $ as qs, $$ as qsa, on, off, closest, trigger, hasClass, addClass, removeClass, uid } from '../../dom.js';
import Events from '../../events.js';
import { createWeakStore } from '../../weak.js';

const DATA_TOGGLE = 'data-fx-dropdown-toggle';
const DATA_DROPDOWN = 'data-fx-dropdown';
const OPEN = 'is-open';
const ALIGN_CLASSES = 'alignment-left alignment-right alignment-center alignment-full';

const toggleRegistry = createWeakStore(); // btn -> { clickHandler, hover }
const dropdownToButton = createWeakStore(); // dropdown -> btn
let docHandler = null;

// Helpers
function close(dropdown) {
	if (!hasClass(dropdown, OPEN)) return;

	removeClass(dropdown, OPEN);

	const btn = dropdownToButton.get(dropdown);
	if (btn) {
		removeClass(btn, 'hover');
		btn.setAttribute('aria-expanded', 'false');
	}

	Events.emit('fx:dropdown:close', { el: dropdown });
	trigger(dropdown, 'fx.dropdown.closed', { el: dropdown });
}

function open(dropdown, btn = null) {
	if (hasClass(dropdown, OPEN)) return;

	closeAll(dropdown);

	// Apply auto-alignment before adding OPEN class to avoid animation jump
	applyAutoAlignment(dropdown);

	addClass(dropdown, OPEN);

	const activeBtn = btn || dropdownToButton.get(dropdown);
	if (activeBtn) {
		addClass(activeBtn, 'hover');
		activeBtn.setAttribute('aria-expanded', 'true');
	}

	if (dropdown.dataset.autoFocus === 'true') {
		qs('input, textarea, select, [contenteditable]', dropdown)?.focus();
	}

	Events.emit('fx:dropdown:open', { btn: activeBtn, el: dropdown });
	trigger(dropdown, 'fx.dropdown.opened', { btn: activeBtn, el: dropdown });
}

function closeAll(except = null) {
	qsa(`[${DATA_DROPDOWN}]`).forEach((el) => {
		if (el !== except) close(el);
	});
}

function applyAutoAlignment(dropdown) {
	// Skip if HTML set a fixed alignment class (flagged during initAll)
	if (dropdown.dataset.fxFixedAlignment === 'true') {
		return;
	}

	// Reset previous auto-alignment
	removeClass(dropdown, ALIGN_CLASSES);

	// Step 1: try center
	addClass(dropdown, 'alignment-center');
	let rect = dropdown.getBoundingClientRect();
	if (rect.left >= 0 && rect.right <= window.innerWidth) return;

	// Step 2: overflow right → try right
	if (rect.right > window.innerWidth) {
		removeClass(dropdown, ALIGN_CLASSES);
		addClass(dropdown, 'alignment-right');
		rect = dropdown.getBoundingClientRect();
		if (rect.left >= 0) return;
	}

	// Step 3: overflow left → try left
	if (rect.left < 0) {
		removeClass(dropdown, ALIGN_CLASSES);
		addClass(dropdown, 'alignment-left');
		rect = dropdown.getBoundingClientRect();
		if (rect.right <= window.innerWidth) return;
	}

	// Step 4: still overflows → full
	removeClass(dropdown, ALIGN_CLASSES);
	addClass(dropdown, 'alignment-full');
}

const FxDropdown = {
	initAll(root = document) {
		qsa('[' + DATA_TOGGLE + ']', root).forEach((btn) => {
			// Cleanup existing registration on this button
			const existing = toggleRegistry.get(btn);
			if (existing) {
				off(btn, 'click', existing.clickHandler);
				if (existing.hover) {
					const { wrapper, dropdown, enter, leave, timers } = existing.hover;
					off(wrapper, 'mouseenter', enter);
					off(wrapper, 'mouseleave', leave);
					off(dropdown, 'mouseenter', enter);
					off(dropdown, 'mouseleave', leave);
					clearTimeout(timers.openT);
					clearTimeout(timers.closeT);
				}
				toggleRegistry.delete(btn);
			}

			// Resolve dropdown panel
			const sel = btn.getAttribute(DATA_TOGGLE);
			const dropdown = sel ? qs(sel) : (btn.parentElement ? qs(`[${DATA_DROPDOWN}]`, btn.parentElement) : null) || closest(btn, `[${DATA_DROPDOWN}]`);
			if (!dropdown) return;

			dropdownToButton.set(dropdown, btn);

			// Flag fixed alignment set in HTML so auto-alignment skips it
			if (
				dropdown.classList.contains('alignment-left') ||
				dropdown.classList.contains('alignment-right') ||
				dropdown.classList.contains('alignment-center') ||
				dropdown.classList.contains('alignment-full')
			) {
				dropdown.dataset.fxFixedAlignment = 'true';
			}

			// ARIA
			if (!dropdown.id) {
				dropdown.id = uid('fx-dropdown');
			}
			btn.setAttribute('aria-controls', dropdown.id);
			btn.setAttribute('aria-expanded', hasClass(dropdown, OPEN) ? 'true' : 'false');

			// Click handler
			const clickHandler = (e) => {
				e.preventDefault();
				hasClass(dropdown, OPEN) ? close(dropdown) : open(dropdown, btn);
			};

			on(btn, 'click', clickHandler);

			// Hover support
			const wrapper = btn.parentElement;
			const isHoverEnabled = btn.dataset.hover === 'true' || dropdown.dataset.hover === 'true' || (wrapper && wrapper.dataset.hover === 'true');

			let hover = null;
			if (wrapper && isHoverEnabled) {
				const timers = { openT: undefined, closeT: undefined };
				const enter = () => {
					clearTimeout(timers.closeT);
					if (!hasClass(dropdown, OPEN)) {
						timers.openT = setTimeout(() => open(dropdown, btn), 80);
					}
				};
				const leave = () => {
					clearTimeout(timers.openT);
					timers.closeT = setTimeout(() => close(dropdown), 150);
				};

				on(wrapper, 'mouseenter', enter);
				on(wrapper, 'mouseleave', leave);
				on(dropdown, 'mouseenter', enter);
				on(dropdown, 'mouseleave', leave);

				hover = { wrapper, dropdown, enter, leave, timers };
			}

			toggleRegistry.set(btn, { clickHandler, hover });
		});

		// Document click (singleton)
		if (!docHandler) {
			docHandler = (e) => {
				const inside = e.target.closest(`[${DATA_DROPDOWN}], [${DATA_TOGGLE}]`);
				if (!inside) closeAll();
			};
			on(document, 'click', docHandler);
		}
	},

	destroyAll(root = document) {
		qsa(`[${DATA_TOGGLE}]`, root).forEach((btn) => {
			const existing = toggleRegistry.get(btn);
			if (existing) {
				off(btn, 'click', existing.clickHandler);
				if (existing.hover) {
					const { wrapper, dropdown, enter, leave, timers } = existing.hover;
					off(wrapper, 'mouseenter', enter);
					off(wrapper, 'mouseleave', leave);
					off(dropdown, 'mouseenter', enter);
					off(dropdown, 'mouseleave', leave);
					clearTimeout(timers.openT);
					clearTimeout(timers.closeT);
				}
				toggleRegistry.delete(btn);
			}
		});

		if (docHandler && root === document) {
			off(document, 'click', docHandler);
			docHandler = null;
		}
	},
};

export default FxDropdown;
