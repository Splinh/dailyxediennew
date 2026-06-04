// share/fx-share.js
import './fx-share.scss';

import { $ as qs, $$ as qsa } from '../../dom.js';
import { createWeakStore } from '../../weak.js';
import Events from '../../events.js';

import { SocialShare } from 'ensemble-social-share';

const SELECTOR = '[data-fx-share]';

const instanceStore = createWeakStore();

/**
 * Default share options
 */
const DEFAULT_OPTIONS = {
	layout: 'h',
	intents: [
		'facebook',
		'x',
		'linkedin',
		'threads',
		'bluesky',
		'reddit',
		'mastodon',
		'quora',
		'whatsapp',
		'messenger',
		'telegram',
		'skype',
		'viber',
		'line',
		'snapchat',
		'send-email',
		'copy-link',
		'web-share',
		'print',
	],
	onIntent: (self, event, intent, data) => {
		// Fix Facebook share URL (library uses dialog/share which requires app_id)
		if (intent === 'facebook') {
			event.preventDefault();
			const url = encodeURIComponent(data.url || window.location.href);
			const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
			window.open(shareUrl, '_blank', 'noopener,noreferrer,width=600,height=400,scrollbars=yes');
			return;
		}

		if (intent === 'print') {
			setTimeout(window.print, 200);
		}
		Events.emit('fx:share:intent', { intent, data });
	},
};

/**
 * Fix missing title attribute for print button (library bug workaround)
 * @param {HTMLElement} container - Share container element
 */
const fixPrintButtonTitle = (container) => {
	// Try immediate fix first
	const tryFix = () => {
		const button = qs('.share-intent-print', container);
		if (button && (!button.title || button.title === 'undefined')) {
			button.setAttribute('title', 'Print');
			return true;
		}
		return false;
	};

	// If already in DOM, fix immediately
	if (tryFix()) return;

	// Otherwise observe for DOM changes
	const observer = new MutationObserver(() => {
		if (tryFix()) {
			observer.disconnect();
		}
	});

	observer.observe(container, { childList: true, subtree: true });

	// Fallback: disconnect after 3s if button never appears (reduced from 5s)
	setTimeout(() => observer.disconnect(), 3000);
};

const FxShare = {
	/**
	 * Initialize all share components in root
	 * @param {Document|Element} root - Root element to search
	 */
	initAll(root = document) {
		qsa(SELECTOR, root).forEach(
			/** @param {HTMLElement} container */
			(container) => {
				// Skip if already initialized
				if (instanceStore.has(container)) return;

				// Parse options from data attributes
				const layout = container.dataset.layout || DEFAULT_OPTIONS.layout;
				const intentsAttr = container.dataset.intents;
				const intents = intentsAttr ? intentsAttr.split(',').map((s) => s.trim()) : DEFAULT_OPTIONS.intents;

				const options = {
					...DEFAULT_OPTIONS,
					layout,
					intents,
				};

				// Create instance
				const instance = new SocialShare(container, options);
				instanceStore.set(container, instance);

				// Fix print button title if needed
				if (intents.includes('print')) {
					fixPrintButtonTitle(container);
				}

				Events.emit('fx:share:init', { container, instance });
			},
		);
	},

	/**
	 * Destroy all share components in root
	 * @param {Document|Element} root - Root element to search
	 */
	destroyAll(root = document) {
		qsa(SELECTOR, root).forEach(
			/** @param {HTMLElement} container */
			(container) => {
				instanceStore.cleanup(container, (instance) => {
					// SocialShare library doesn't have destroyed method
					// Just clear the container
					container.innerHTML = '';
				});
			},
		);
	},
};

export default FxShare;
