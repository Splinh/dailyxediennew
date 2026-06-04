// core/createLoader.js
// Factory function to create a lazy loader

/**
 * @param {Object} config - Module configuration { key: { selector, loader } }
 * @param {string} name - Loader name for logging
 * @param {Object} options - Loader options
 * @param {boolean} [options.debug=false] - Enable debug logging
 */
export function createLoader(config, name = 'Loader', { debug = false } = {}) {
	const loaded = new Map();
	const pending = new Map();

	const log = (...args) => {
		if (debug) console.log(`[${name}]`, ...args);
	};

	const isNeeded = (key, root = document) => {
		if (key === '__proto__' || key === 'constructor' || key === 'prototype') return false;
		const cfg = Reflect.get(config, key);
		if (!cfg) return false;

		const rootMatches = root.nodeType === 1 && root.matches?.(cfg.selector);
		return rootMatches || root.querySelector(cfg.selector) !== null;
	};

	const load = async (key) => {
		if (loaded.has(key)) {
			log(`Cache hit: ${key}`);
			return loaded.get(key);
		}

		// Deduplicate concurrent loads for the same key
		if (pending.has(key)) {
			log(`Waiting: ${key}`);
			return pending.get(key);
		}

		if (key === '__proto__' || key === 'constructor' || key === 'prototype') return null;
		const cfg = Reflect.get(config, key);
		if (!cfg) {
			log(`Not found: ${key}`);
			return null;
		}

		const promise = cfg
			.loader()
			.then((module) => {
				const m = module.default || module;
				loaded.set(key, m);
				pending.delete(key);
				log(`Loaded: ${key}`);
				return m;
			})
			.catch((e) => {
				pending.delete(key);
				console.error(`[${name}] Failed to load: ${key}`, e);
				return null;
			});

		log(`Loading: ${key}`);
		pending.set(key, promise);
		return promise;
	};

	return {
		/**
		 * Initialize all needed modules in the document.
		 * @param {Object} [options] - Options
		 * @param {Element} [options.root=document] - Root element to scan
		 */
		async init({ root = document } = {}) {
			const needed = Object.keys(config).filter((key) => isNeeded(key, root));
			log(`Init - needed modules:`, needed);

			const promises = needed.map(async (key) => {
				const m = await load(key);
				m?.initAll?.(root);
			});

			await Promise.all(promises);
			log(`Init complete`);
		},

		/**
		 * Scan a dynamic root for matching selectors and init needed modules.
		 * Use after AJAX, popup inject, or any dynamic DOM insertion.
		 *
		 * @param {Element} root - The injected DOM subtree to scan.
		 */
		async scan(root) {
			if (!root) return;
			await this.init({ root });
		},

		/**
		 * Destroy a module in a specific root.
		 * @param {string} key - Module key
		 * @param {Element} [root=document] - Root element to destroy in
		 */
		async destroy(key, root = document) {
			const m = loaded.get(key);
			if (m) {
				m.destroyAll?.(root);
				log(`Destroyed: ${key}`);
			}
		},

		/**
		 * Reinitialize a module in a specific root.
		 * @param {string} key - Module key
		 * @param {Element} [root=document] - Root element to reinitialize in
		 */
		async reinit(key, root = document) {
			let m = loaded.get(key);
			if (!m) m = await load(key);
			if (m) {
				m.destroyAll?.(root);
				m.initAll?.(root);
				log(`Reinit: ${key}`);
			}
		},

		/**
		 * Load a module directly (bypasses selector check).
		 * Use when you know you need a module regardless of current DOM state.
		 * @param {string} key - Module key
		 * @returns {Promise<Object|null>} Module instance or null
		 */
		load,

		/**
		 * Get list of currently loaded module keys.
		 * @returns {string[]} Array of loaded module keys
		 */
		get loaded() {
			return [...loaded.keys()];
		},

		/**
		 * Get list of all available module keys.
		 * @returns {string[]} Array of available module keys
		 */
		get available() {
			return Object.keys(config);
		},

		/**
		 * Get the current configuration object.
		 * @returns {Object} Current configuration
		 */
		get config() {
			return config;
		},

		/**
		 * Enable/disable debug mode at runtime.
		 * @param {boolean} enabled - Enable or disable debug mode
		 */
		setDebug(enabled) {
			debug = enabled;
			log(`Debug mode: ${enabled ? 'ON' : 'OFF'}`);
		},
	};
}
