// utils/global.js — Global DOM enhancements (IIFE, runs once)

(() => {
	if (window.__globalInit) return;
	window.__globalInit = true;

	const currentDomain = window.location.hostname;
	const invalidHref = /^(#|mailto:|tel:|javascript:|data:|blob:)/i;
	const blankSelector = 'a._blank, a.blank, a[target="_blank"]';
	const hiddenControlSelector = '[aria-hidden="true"] a, [aria-hidden="true"] button, a[aria-hidden="true"], button[aria-hidden="true"]';

	/**
	 * Run a callback on root itself when it matches, plus matching descendants.
	 * @param {Element|Document} root - Root to scan
	 * @param {string} selector - CSS selector
	 * @param {Function} callback - Node callback
	 */
	function eachMatch(root, selector, callback) {
		if (root instanceof Element && root.matches(selector)) {
			callback(root);
		}

		root.querySelectorAll(selector).forEach(callback);
	}

	/**
	 * Process external links — add target="_blank" + rel="noopener noreferrer nofollow".
	 * @param {Element|Document} root - Root to scan
	 */
	function processLinks(root = document) {
		eachMatch(root, blankSelector, applyTargetRel);
		eachMatch(root, 'a[href]', (el) => {
			const href = el.getAttribute('href')?.trim();
			if (!href || invalidHref.test(href)) return;

			try {
				const url = new URL(href, window.location.href);
				if (url.hostname && url.hostname !== currentDomain) {
					applyTargetRel(el);
				}
			} catch {}
		});
	}

	/**
	 * Apply security attrs to a link element.
	 * @param {HTMLAnchorElement} el
	 */
	function applyTargetRel(el) {
		if (el.target !== '_blank') el.target = '_blank';

		const existing = el.getAttribute('rel') || '';
		const parts = existing.split(/\s+/).filter(Boolean);
		let changed = false;

		for (const val of ['noopener', 'noreferrer', 'nofollow']) {
			if (!parts.includes(val)) {
				parts.push(val);
				changed = true;
			}
		}

		if (changed) el.setAttribute('rel', parts.join(' '));
	}

	/**
	 * Apply a11y fixes within a root.
	 * @param {Element|Document} root - Root to scan
	 */
	function applyA11y(root = document) {
		eachMatch(root, 'ul.submenu[role="menubar"]', (menu) => {
			menu.setAttribute('role', 'menu');
		});

		eachMatch(root, hiddenControlSelector, (el) => {
			if (el.getAttribute('tabindex') !== '-1') {
				el.setAttribute('tabindex', '-1');
			}
		});
	}

	/**
	 * MutationObserver — process ONLY added nodes (not full DOM re-scan).
	 */
	let mutationTimer;
	let pendingNodes = [];

	function handleMutations(records) {
		for (const record of records) {
			for (const node of record.addedNodes) {
				if (node.nodeType === Node.ELEMENT_NODE) pendingNodes.push(node);
			}
		}

		clearTimeout(mutationTimer);
		mutationTimer = setTimeout(() => {
			const nodes = pendingNodes.splice(0);
			nodes.forEach((n) => {
				processLinks(n);
				applyA11y(n);
				wrapTables(n);
			});
		}, 200);
	}

	/**
	 * Wrap tables in scroll container.
	 * @param {Element|Document} root - Root to scan
	 */
	function wrapTables(root = document) {
		eachMatch(root, '.entry-content table', (tbl) => {
			if (tbl.parentElement?.classList.contains('table-scroll')) return;

			const wrap = document.createElement('div');
			wrap.className = 'table-scroll';
			tbl.parentNode.insertBefore(wrap, tbl);
			wrap.appendChild(tbl);
		});
	}

	/**
	 * Footer column toggles.
	 */
	function initFooterToggles() {
		document.querySelectorAll('#footer-columns .toggle-title').forEach((link) => {
			link.addEventListener('click', function (e) {
				e.preventDefault();
				this.classList.toggle('active');
			});
		});
	}

	// ── Run ──
	const run = () => {
		processLinks();
		applyA11y();
		wrapTables();
		initFooterToggles();

		const observer = new MutationObserver(handleMutations);
		observer.observe(document.body, { childList: true, subtree: true });
	};

	document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', run, { once: true }) : run();
})();
