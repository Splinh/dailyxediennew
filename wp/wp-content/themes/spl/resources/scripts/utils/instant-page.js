/**
 * Instant Page Prefetch — client-side fallback for browsers without Speculation Rules.
 *
 * On pointer hover or touchstart, injects a <link rel="prefetch"> so the
 * browser fetches the page HTML in advance. When the user clicks, the response
 * is already in the HTTP cache → near-instant navigation.
 *
 * If the browser supports Speculation Rules API (Chromium 121+), this module
 * does nothing — the native prerender is strictly superior.
 *
 * @module instant-page
 */

const EXCLUSIONS = [
	'/wp-admin',
	'/wp-login.php',
	'/cart',
	'/checkout',
	'/my-account',
	'action=logout',
	'add-to-cart=',
	'removed_item=',
];

const FILE_EXTS = /\.(pdf|zip|rar|doc|docx|xls|xlsx|csv)(\?|#|$)/i;

const MAX_PREFETCH = 3;
const HOVER_DELAY_MS = 65;

/** @type {Set<string>} */
const prefetched = new Set();
let activeCount = 0;
let hoverTimer = 0;

/**
 * Check if a URL should be excluded from prefetching.
 *
 * @param {string} href
 * @returns {boolean}
 */
function isExcluded(href) {
	if (FILE_EXTS.test(href)) return true;

	const path = href.replace(location.origin, '');
	return EXCLUSIONS.some((ex) => path.includes(ex));
}

/**
 * Prefetch a URL by injecting a <link rel="prefetch">.
 *
 * @param {string} url
 */
function prefetch(url) {
	if (prefetched.has(url) || activeCount >= MAX_PREFETCH) return;

	prefetched.add(url);
	activeCount++;

	const link = document.createElement('link');
	link.rel = 'prefetch';
	link.href = url;
	link.as = 'document';
	link.onload = link.onerror = () => {
		activeCount--;
	};

	document.head.appendChild(link);
}

/**
 * Handle pointer enter on anchor elements.
 *
 * @param {PointerEvent|TouchEvent} e
 */
function onPointerIn(e) {
	const anchor = e.target?.closest?.('a[href]');
	if (!anchor) return;

	const href = anchor.href;

	// Only same-origin, http(s) links.
	if (!href || anchor.origin !== location.origin) return;

	// Skip hash-only, tel, mailto.
	if (anchor.protocol !== 'https:' && anchor.protocol !== 'http:') return;

	// Skip excluded URLs.
	if (isExcluded(href)) return;

	// Debounce hover to avoid rapid-fire prefetches.
	clearTimeout(hoverTimer);
	hoverTimer = setTimeout(() => prefetch(href), HOVER_DELAY_MS);
}

/**
 * Cancel pending prefetch on pointer leave.
 */
function onPointerOut() {
	clearTimeout(hoverTimer);
}

/**
 * Initialize instant page prefetching.
 *
 * Skips initialization if the browser supports Speculation Rules API
 * (Chromium 121+), since native prerender is superior.
 */
export function initInstantPage() {
	// Skip if browser has native Speculation Rules support.
	if (
		HTMLScriptElement.supports &&
		HTMLScriptElement.supports('speculationrules')
	) {
		return;
	}

	// Skip if user prefers reduced data usage.
	if (navigator.connection?.saveData) return;

	document.addEventListener('pointerover', onPointerIn, { passive: true });
	document.addEventListener('pointerout', onPointerOut, { passive: true });

	// Touchstart for mobile (fires before pointerover on some devices).
	document.addEventListener(
		'touchstart',
		(e) => {
			const anchor = e.target?.closest?.('a[href]');
			if (anchor && anchor.origin === location.origin && !isExcluded(anchor.href)) {
				prefetch(anchor.href);
			}
		},
		{ passive: true },
	);
}

// Auto-init on load.
initInstantPage();
