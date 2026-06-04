// core/modules/form/form-logic.js
// Conditional show/hide fields based on data-show-if / data-hide-if attributes.
//
// HTML API:
//   <form data-form="contact" data-logic>
//     <select name="subject">...</select>
//     <div data-show-if="subject:other" hidden>...</div>
//     <div data-hide-if="subject:other">...</div>
//     <div data-show-if="subject:support,department:tech" hidden>...</div>
//   </form>

import './form-logic.scss';

/**
 * Parse a condition string into an array of { field, value } objects.
 *
 * @param {string} condStr — e.g. "subject:other,department:tech"
 * @returns {{ field: string, value: string }[]}
 */
function parseConditions(condStr) {
	return condStr
		.split(',')
		.map((pair) => {
			const [field, ...rest] = pair.split(':');
			return { field: field.trim(), value: rest.join(':').trim() };
		})
		.filter((c) => c.field && c.value);
}

/**
 * Get current value of a form field by name.
 *
 * @param {HTMLFormElement} form
 * @param {string} fieldName
 * @returns {string}
 */
function getFieldValue(form, fieldName) {
	const el = form.querySelector(`[name="${fieldName}"]`);
	if (!el) return '';

	// Radio buttons — find the checked one.
	if (el.type === 'radio') {
		const checked = form.querySelector(`[name="${fieldName}"]:checked`);
		return checked ? checked.value : '';
	}

	// Checkboxes — return comma-joined values for checked items.
	if (el.type === 'checkbox') {
		const boxes = form.querySelectorAll(`[name="${fieldName}"]:checked`);
		return boxes.length ? [...boxes].map((b) => b.value).join(',') : '';
	}

	return el.value;
}

/**
 * Resolve and cache field elements for a set of conditions.
 * Avoids repeated querySelector calls on every evaluate() invocation.
 *
 * @param {HTMLFormElement} form
 * @param {{ field: string, value: string }[]} conditions
 * @returns {{ field: string, value: string, el: Element|null }[]}
 */
function resolveElements(form, conditions) {
	return conditions.map((c) => ({
		...c,
		el: form.querySelector(`[name="${c.field}"]`),
	}));
}

/**
 * Check if all conditions in a rule are satisfied.
 * Uses cached element references for fast reads.
 *
 * @param {HTMLFormElement} form
 * @param {{ field: string, value: string, el: Element|null }[]} conditions
 * @returns {boolean}
 */
function evaluateConditions(form, conditions) {
	return conditions.every(({ field, value, el }) => {
		// Fallback to querySelector for dynamically added fields (e.g. repeater).
		const current = el ? getFieldValueFromEl(form, field, el) : getFieldValue(form, field);

		// Support pipe-separated OR values: "subject:support|sales"
		if (value.includes('|')) {
			return value.split('|').some((v) => v.trim() === current);
		}

		return current === value;
	});
}

/**
 * Read value from a cached element reference.
 *
 * @param {HTMLFormElement} form
 * @param {string} fieldName
 * @param {Element} el
 * @returns {string}
 */
function getFieldValueFromEl(form, fieldName, el) {
	if (el.type === 'radio') {
		const checked = form.querySelector(`[name="${fieldName}"]:checked`);
		return checked ? checked.value : '';
	}

	if (el.type === 'checkbox') {
		const boxes = form.querySelectorAll(`[name="${fieldName}"]:checked`);
		return boxes.length ? [...boxes].map((b) => b.value).join(',') : '';
	}

	return el.value;
}

/**
 * Toggle visibility and disabled state of a conditional block.
 *
 * @param {HTMLElement} block
 * @param {boolean} show
 */
function toggleBlock(block, show) {
	if (show) {
		block.removeAttribute('hidden');
		block.querySelectorAll('input, select, textarea').forEach((el) => {
			el.removeAttribute('disabled');
		});
	} else {
		block.setAttribute('hidden', '');
		block.querySelectorAll('input, select, textarea').forEach((el) => {
			el.setAttribute('disabled', '');
		});
	}
}

/**
 * Initialize conditional logic for a single form.
 *
 * @param {HTMLFormElement} form
 */
function initLogic(form) {
	if (form._hdLogicInited) return;
	form._hdLogicInited = true;

	const showBlocks = form.querySelectorAll('[data-show-if]');
	const hideBlocks = form.querySelectorAll('[data-hide-if]');

	if (!showBlocks.length && !hideBlocks.length) return;

	// Pre-parse rules.
	const rules = [];

	showBlocks.forEach((block) => {
		rules.push({
			block,
			conditions: resolveElements(form, parseConditions(block.dataset.showIf)),
			mode: 'show',
		});
	});

	hideBlocks.forEach((block) => {
		rules.push({
			block,
			conditions: resolveElements(form, parseConditions(block.dataset.hideIf)),
			mode: 'hide',
		});
	});

	// Debounce via rAF — coalesces change+input double-fire and rapid keystrokes.
	let rafId;
	const evaluate = () => {
		rules.forEach(({ block, conditions, mode }) => {
			const match = evaluateConditions(form, conditions);
			toggleBlock(block, mode === 'show' ? match : !match);
		});
	};

	const scheduleEvaluate = () => {
		cancelAnimationFrame(rafId);
		rafId = requestAnimationFrame(evaluate);
	};

	// Initial evaluation (synchronous — no debounce needed).
	evaluate();

	// Re-evaluate on any input change (event delegation).
	form.addEventListener('change', scheduleEvaluate);
	form.addEventListener('input', scheduleEvaluate);

	// Store cleanup reference.
	form._hdLogicCleanup = () => {
		cancelAnimationFrame(rafId);
		form.removeEventListener('change', scheduleEvaluate);
		form.removeEventListener('input', scheduleEvaluate);
	};
}

/**
 * Destroy conditional logic for a form.
 *
 * @param {HTMLFormElement} form
 */
function destroyLogic(form) {
	form._hdLogicCleanup?.();
	form._hdLogicInited = false;
}

// -- Module API (createLoader compatible) --

export default {
	initAll(root = document) {
		root.querySelectorAll('[data-form][data-logic]').forEach(initLogic);
	},

	destroyAll(root = document) {
		root.querySelectorAll('[data-form][data-logic]').forEach(destroyLogic);
	},
};
