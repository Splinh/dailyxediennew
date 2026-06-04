// core/modules/form/form-tracking.js
// Multi-provider tracking dispatcher for form submissions.
//
// Listens for `hd:form:success` custom events dispatched by form.js
// and routes to registered tracking providers.
//
// Per-form config via data-tracking attribute:
//   <form data-form="contact" data-tracking='{"gtm":"generate_lead","fbq":"Lead","ttq":"SubmitForm"}'>
//
// Provider config can be a string (event name) or object (event + extra params):
//   data-tracking='{"gtm":{"event":"generate_lead","value":100}}'

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

	const eventName = typeof config === 'string' ? config : config?.event || 'form_submit';
	const extra = typeof config === 'object' ? { ...config } : {};
	delete extra.event;

	window.dataLayer.push({
		event: eventName,
		form_type: detail.formType,
		form_id: detail.formId,
		page_url: detail.pageUrl,
		...extra,
	});
});

// Facebook Pixel (fbq)
registerProvider('fbq', (config, detail) => {
	if (typeof window.fbq !== 'function') return;

	const eventName = typeof config === 'string' ? config : config?.event || 'Lead';
	const extra = typeof config === 'object' ? { ...config } : {};
	delete extra.event;

	window.fbq('track', eventName, {
		content_name: detail.formType,
		content_category: detail.formId,
		...extra,
	});
});

// TikTok Pixel (ttq)
registerProvider('ttq', (config, detail) => {
	if (!window.ttq?.track) return;

	const eventName = typeof config === 'string' ? config : config?.event || 'SubmitForm';
	const extra = typeof config === 'object' ? { ...config } : {};
	delete extra.event;

	window.ttq.track(eventName, {
		content_type: detail.formType,
		content_id: detail.formId,
		...extra,
	});
});

// ── Event Handler ───────────────────────────────────────────────

/**
 * Route `hd:form:success` event to registered providers.
 *
 * @param {CustomEvent} e
 */
function handleFormSuccess(e) {
	const { tracking, ...detail } = e.detail || {};

	if (!tracking || typeof tracking !== 'object') return;

	for (const [key, config] of Object.entries(tracking)) {
		const handler = providers.get(key);
		if (!handler) continue;

		try {
			handler(config, detail);
		} catch (err) {
			// eslint-disable-next-line no-console
			console.warn(`[form-tracking] ${key} error:`, err);
		}
	}
}

// ── Module API (createLoader compatible) ────────────────────────

export default {
	initAll() {
		document.addEventListener('hd:form:success', handleFormSuccess);
	},

	destroyAll() {
		document.removeEventListener('hd:form:success', handleFormSuccess);
	},
};
