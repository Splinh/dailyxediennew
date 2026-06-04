// core/modules/form/form-repeater.js
// Repeater field — clone/remove groups of inputs.
//
// HTML API:
//   <div data-repeater data-repeater-min="1" data-repeater-max="5">
//     <div data-repeater-row>
//       <input name="items[0][name]">
//       <button type="button" data-repeater-remove>✕</button>
//     </div>
//     <button type="button" data-repeater-add>+ Thêm</button>
//   </div>

import './form-repeater.scss';

/**
 * Re-index all row input names to sequential [0], [1], [2]...
 *
 * @param {HTMLElement} container
 */
function reindexRows(container) {
	const rows = container.querySelectorAll('[data-repeater-row]');
	rows.forEach((row, i) => {
		row.querySelectorAll('[name]').forEach((el) => {
			el.name = el.name.replace(/\[\d+\]/, `[${i}]`);
		});
	});
}

/**
 * Update add/remove button visibility based on min/max constraints.
 *
 * @param {HTMLElement} container
 * @param {number} min
 * @param {number} max
 */
function updateButtons(container, min, max) {
	const rows = container.querySelectorAll('[data-repeater-row]');
	const count = rows.length;
	const addBtn = container.querySelector('[data-repeater-add]');

	// Toggle add button.
	if (addBtn) {
		addBtn.hidden = count >= max;
	}

	// Toggle remove buttons.
	rows.forEach((row) => {
		const removeBtn = row.querySelector('[data-repeater-remove]');
		if (removeBtn) {
			removeBtn.hidden = count <= min;
		}
	});
}

/**
 * Initialize repeater for a single container.
 *
 * @param {HTMLElement} container
 */
function initRepeater(container) {
	if (container._hdRepeaterInited) return;
	container._hdRepeaterInited = true;

	const min = parseInt(container.dataset.repeaterMin, 10) || 1;
	const max = parseInt(container.dataset.repeaterMax, 10) || 10;

	// Store template from the first row.
	const firstRow = container.querySelector('[data-repeater-row]');
	if (!firstRow) return;

	const template = firstRow.cloneNode(true);

	// Clear template values.
	template.querySelectorAll('input, select, textarea').forEach((el) => {
		if (el.type === 'checkbox' || el.type === 'radio') {
			el.checked = false;
		} else {
			el.value = '';
		}
	});

	const addBtn = container.querySelector('[data-repeater-add]');

	// Add row handler.
	function addRow() {
		const rows = container.querySelectorAll('[data-repeater-row]');
		if (rows.length >= max) return;

		const newRow = template.cloneNode(true);
		const index = rows.length;

		// Set correct index.
		newRow.querySelectorAll('[name]').forEach((el) => {
			el.name = el.name.replace(/\[\d+\]/, `[${index}]`);
		});

		// Insert before add button.
		if (addBtn) {
			container.insertBefore(newRow, addBtn);
		} else {
			container.appendChild(newRow);
		}

		updateButtons(container, min, max);
	}

	// Remove row handler (event delegation).
	function handleRemove(e) {
		const btn = e.target.closest('[data-repeater-remove]');
		if (!btn || !container.contains(btn)) return;

		const rows = container.querySelectorAll('[data-repeater-row]');
		if (rows.length <= min) return;

		const row = btn.closest('[data-repeater-row]');
		if (row) {
			row.remove();
			reindexRows(container);
			updateButtons(container, min, max);
		}
	}

	if (addBtn) {
		addBtn.addEventListener('click', addRow);
	}
	container.addEventListener('click', handleRemove);

	// Initial state.
	updateButtons(container, min, max);

	// Store cleanup.
	container._hdRepeaterCleanup = () => {
		if (addBtn) addBtn.removeEventListener('click', addRow);
		container.removeEventListener('click', handleRemove);
	};
}

/**
 * Destroy repeater.
 *
 * @param {HTMLElement} container
 */
function destroyRepeater(container) {
	container._hdRepeaterCleanup?.();
	container._hdRepeaterInited = false;
}

// -- Module API (createLoader compatible) --

export default {
	initAll(root = document) {
		root.querySelectorAll('[data-repeater]').forEach(initRepeater);
	},

	destroyAll(root = document) {
		root.querySelectorAll('[data-repeater]').forEach(destroyRepeater);
	},
};
