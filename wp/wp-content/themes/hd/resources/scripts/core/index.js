// core/index.js
// Master loader - aggregates all lazy loaders

import FX from './fx/index.js';
import Modules from './modules/index.js';

/** All loader instances */
const loaders = [FX, Modules];

/**
 * Initialize all loaders on page load.
 *
 * @param {Object} options - { root: Document|Element }
 */
async function initAll(options = {}) {
	await Promise.all(loaders.map((l) => l.init(options)));
}

/**
 * Scan a dynamic root (AJAX content, popup, etc.) for all registered
 * selectors across FX and Modules, then load + init only what's needed.
 *
 * Usage from any JS:
 *   document.dispatchEvent(new CustomEvent('core:scan', { detail: { root: el } }));
 *
 * Or import directly:
 *   import { scan } from './core/index.js';
 *   await scan(el);
 *
 * @param {Element} root - The injected DOM subtree.
 */
async function scan(root) {
	if (!root) return;
	await Promise.all(loaders.map((l) => l.scan(root)));
}

// ─── Global Event: core:scan ──────────────────────────
// Always available because this master loader is the app entry point.
// Any code injecting dynamic content can trigger a re-scan:
//
//   document.dispatchEvent(new CustomEvent('core:scan', { detail: { root: container } }));
//
document.addEventListener('core:scan', (e) => {
	const root = e.detail?.root;
	if (root) scan(root);
});

export { FX, Modules, initAll, scan };
