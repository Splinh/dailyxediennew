/**
 * Shared jQuery utility functions.
 *
 * @package HD Toolkit
 */

const $ = window.jQuery;

/**
 * Register shared jQuery plugins (only once).
 */
export function registerJQueryPlugins() {
	if ($.fn.fadeOutAndRemove) return;

	/**
	 * Fade out element and remove from DOM.
	 *
	 * @param {number} speed - Animation speed in ms.
	 * @returns {jQuery}
	 */
	$.fn.fadeOutAndRemove = function (speed = 400) {
		return this.fadeOut(speed, function () {
			$(this).remove();
		});
	};

	/**
	 * Serialize form to object (supports nested arrays and objects).
	 *
	 * @returns {Object}
	 */
	$.fn.serializeObject = function () {
		const obj = {};
		const array = this.serializeArray();

		$.each(array, function () {
			const name = this.name;
			const value = this.value || '';

			// Detect explicit array notation: name ends with []
			// e.g., "redirect_from[]" should always produce an array.
			const forceArray = name.endsWith('[]');

			// Parse field name to handle nested structures
			// e.g., "contact_items[0][id]" becomes ['contact_items', '0', 'id']
			const keys = name.match(/[^\[\]]+/g) || [name];

			// Build nested structure
			let current = obj;
			for (let i = 0; i < keys.length; i++) {
				const key = keys[i];
				const isLast = i === keys.length - 1;

				if (isLast) {
					if (forceArray) {
						// Always push to array for [] fields.
						if (!Array.isArray(current[key])) {
							current[key] = current[key] !== undefined ? [current[key]] : [];
						}
						current[key].push(value);
					} else if (current[key] !== undefined) {
						// Duplicate key without []: convert to array.
						if (!Array.isArray(current[key])) {
							current[key] = [current[key]];
						}
						current[key].push(value);
					} else {
						current[key] = value;
					}
				} else {
					// Intermediate key: ensure object/array exists
					const nextKey = keys[i + 1];
					const isNextNumeric = !isNaN(parseInt(nextKey, 10));

					if (current[key] === undefined) {
						current[key] = isNextNumeric ? [] : {};
					}
					current = current[key];
				}
			}
		});

		return obj;
	};
}

// Auto-register when imported
registerJQueryPlugins();
