// core/weak.js - Global WeakMap store helper

/**
 * WeakMap wrapper for JS projects with strict tooling.
 */
export const createWeakStore = () => {
	const map = new WeakMap();

	/**
	 * @param {any} v
	 * @returns {v is object}
	 */
	const isObject = (v) => v !== null && typeof v === 'object';

	return {
		/**
		 * Check if key exists
		 * @param {object} key
		 * @returns {boolean}
		 */
		has(key) {
			return isObject(key) && map.has(key);
		},

		/**
		 * Get value by key
		 * @param {object} key
		 * @returns {any}
		 */
		get(key) {
			return isObject(key) ? map.get(key) : undefined;
		},

		/**
		 * Set value for key
		 * @param {object} key
		 * @param {any} value
		 */
		set(key, value) {
			if (isObject(key)) map.set(key, value);
		},

		/**
		 * Delete key
		 * @param {object} key
		 */
		delete(key) {
			if (isObject(key)) map.delete(key);
		},

		/**
		 * Get existing value or create new one using factory
		 * @param {object} key
		 * @param {() => any} factory - Function to create value if not exists
		 * @returns {any}
		 */
		getOrCreate(key, factory) {
			if (!isObject(key)) return undefined;
			if (map.has(key)) return map.get(key);

			const value = factory();
			map.set(key, value);
			return value;
		},

		/**
		 * Run cleanup function on value and delete key
		 * @param {object} key
		 * @param {(value: any) => void} fn - Cleanup function
		 */
		cleanup(key, fn) {
			if (!isObject(key) || !map.has(key)) return;
			fn(map.get(key));
			map.delete(key);
		},
	};
};
