// utils/dark.js — Dark mode toggle (IIFE, runs once)

(() => {
	if (window.__darkInit) return;
	window.__darkInit = true;

	const root = document.documentElement;

	/**
	 * Safe localStorage access (incognito / blocked storage).
	 * @param {string} key
	 * @param {string} [value]
	 * @returns {string|null}
	 */
	const storage = (key, value) => {
		try {
			if (value !== undefined) {
				localStorage.setItem(key, value);
				return value;
			}
			return localStorage.getItem(key);
		} catch {
			return null;
		}
	};

	// Apply saved theme immediately (before paint → prevent FOUC)
	if (storage('theme') === 'dark') {
		root.classList.add('dark');
	}

	const run = () => {
		// Icon visibility is handled by CSS (hidden dark:block / block dark:hidden)
		const button = document.querySelector('.dark-mode');
		if (!button) return;

		button.addEventListener('click', () => {
			const isDark = root.classList.toggle('dark');
			storage('theme', isDark ? 'dark' : 'light');
		});
	};

	document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', run, { once: true }) : run();
})();
