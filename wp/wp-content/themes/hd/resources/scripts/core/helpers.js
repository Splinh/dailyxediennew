// core/helpers.js — Shared utilities for FX & Modules

/**
 * Debounce function
 * @param {Function} fn - Function to debounce
 * @param {number} ms - Delay in milliseconds
 * @returns {Function}
 */
export const debounce = (fn, ms) => {
	let t;
	return (...a) => (clearTimeout(t), (t = setTimeout(() => fn(...a), ms)));
};

/**
 * Throttle using requestAnimationFrame
 * @param {Function} fn - Function to throttle
 * @returns {Function}
 */
export const throttleRAF = (fn) => {
	let s = false;
	return (...a) => s || ((s = true), requestAnimationFrame(() => (fn(...a), (s = false))));
};

/**
 * Safe JSON parse with fallback
 * @param {string} str - JSON string
 * @param {*} fallback - Fallback value on parse error
 * @returns {*}
 */
export const parseJSON = (str, fallback) => {
	try {
		return JSON.parse(str) ?? fallback;
	} catch {
		return fallback;
	}
};

/**
 * Resolve responsive value from config object {default, md, lg}
 * @param {Object|number} config - Responsive config or single number
 * @param {number} [defaultVal=12] - Default value
 * @returns {number}
 */
export const resolveResponsive = (config, defaultVal = 12) => {
	if (typeof config === 'number') return config;
	const w = window.innerWidth;
	if (w >= 1024 && config.lg !== undefined) return config.lg;
	if (w >= 768 && config.md !== undefined) return config.md;
	return config.default ?? defaultVal;
};
