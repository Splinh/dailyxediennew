// modules/woocommerce/gallery/gallery-tracking.js
// Multi-provider tracking dispatcher for gallery interactions.
//
// Listens for `hd:gallery:interact` custom events dispatched by gallery-thumbs.js
// and routes to registered tracking providers (GTM, fbq, ttq).
//
// Per-gallery config via data-tracking attribute:
//   <div data-wc-gallery data-tracking='{"gtm":"view_item_gallery","fbq":"ViewContent","ttq":"ViewContent"}'>
//
// Provider config can be a string (event name) or object (event + extra params):
//   data-tracking='{"gtm":{"event":"view_item_gallery","currency":"VND"}}'

/** @type {Map<string, function(*, Object): void>} */
const providers = new Map();

/**
 * Register a tracking provider.
 *
 * @param {string} key — Provider key matching data-tracking keys.
 * @param {function(config: string|Object, detail: Object): void} handler
 */
export function registerProvider(key, handler) {
	providers.set(key, handler);
}

// ── Built-in Providers ──────────────────────────────────────────

// Google Tag Manager / GA4 (dataLayer)
registerProvider('gtm', (config, detail) => {
	if (!window.dataLayer) return;

	const eventName = typeof config === 'string' ? config : config?.event || 'gallery_interact';
	const extra = typeof config === 'object' ? { ...config } : {};
	delete extra.event;

	window.dataLayer.push({
		event: eventName,
		action: detail.action,
		product_id: detail.productId,
		image_index: detail.imageIndex,
		...extra,
	});
});

// Facebook Pixel (fbq)
registerProvider('fbq', (config, detail) => {
	if (typeof window.fbq !== 'function') return;

	const eventName = typeof config === 'string' ? config : config?.event || 'ViewContent';
	const extra = typeof config === 'object' ? { ...config } : {};
	delete extra.event;

	window.fbq('track', eventName, {
		content_type: 'product',
		content_ids: [detail.productId],
		content_name: detail.action,
		...extra,
	});
});

// TikTok Pixel (ttq)
registerProvider('ttq', (config, detail) => {
	if (!window.ttq?.track) return;

	const eventName = typeof config === 'string' ? config : config?.event || 'ViewContent';
	const extra = typeof config === 'object' ? { ...config } : {};
	delete extra.event;

	window.ttq.track(eventName, {
		content_type: 'product',
		content_id: detail.productId,
		description: detail.action,
		...extra,
	});
});

// ── Event Handler ───────────────────────────────────────────────

/**
 * Route `hd:gallery:interact` event to registered providers.
 *
 * @param {CustomEvent} e
 */
function handleGalleryInteract(e) {
	const { tracking, ...detail } = e.detail || {};

	if (!tracking || typeof tracking !== 'object') return;

	for (const [key, config] of Object.entries(tracking)) {
		const handler = providers.get(key);
		if (!handler) continue;

		try {
			handler(config, detail);
		} catch (err) {
			// eslint-disable-next-line no-console
			console.warn(`[gallery-tracking] ${key} error:`, err);
		}
	}
}

// ── Module API (createLoader compatible) ────────────────────────

export default {
	initAll() {
		document.addEventListener('hd:gallery:interact', handleGalleryInteract);
	},

	destroyAll() {
		document.removeEventListener('hd:gallery:interact', handleGalleryInteract);
	},
};
