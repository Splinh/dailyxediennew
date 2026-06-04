// lightbox/fx-lightbox.js
import './fx-lightbox.scss';

import { $ as qs, $$ as qsa } from '../../dom.js';
import Events from '../../events.js';

import PhotoSwipeLightbox from 'photoswipe/lightbox';
import PhotoSwipe from 'photoswipe';

/** Active lightbox instances keyed by gallery element — WeakMap allows GC when element is removed */
const instances = new WeakMap();
/** Parallel strong-ref set for iteration during explicit teardown */
const trackedEls = new Set();

/**
 * Collect unique data-lightbox group names from elements.
 * @param {Document|Element} root
 * @returns {Set<string>}
 */
function collectGroups(root) {
	const groups = new Set();
	qsa('[data-lightbox]', root).forEach((el) => {
		const group = el.dataset.lightbox;
		if (group) groups.add(group);
	});
	return groups;
}

/**
 * Resolve image dimensions for a link element.
 * PhotoSwipe requires width + height for proper slide sizing.
 *
 * Priority:
 * 1. data-pswp-width / data-pswp-height on the <a>
 * 2. data-large_image_width / data-large_image_height on child <img>
 * 3. naturalWidth / naturalHeight of child <img> (if loaded)
 * 4. Falls back to 0 (PhotoSwipe will auto-measure)
 *
 * @param {HTMLElement} linkEl
 * @returns {{ w: number, h: number }}
 */
function resolveDimensions(linkEl) {
	// 1. Explicit pswp attributes on link
	const pw = linkEl.dataset.pswpWidth;
	const ph = linkEl.dataset.pswpHeight;
	if (pw && ph) return { w: Number(pw), h: Number(ph) };

	// 2. WooCommerce convention on child <img>
	const img = qs('img', linkEl);
	if (img) {
		const lw = img.dataset.large_image_width;
		const lh = img.dataset.large_image_height;
		if (lw && lh) return { w: Number(lw), h: Number(lh) };

		// 3. Natural dimensions (only if image is loaded)
		if (img.naturalWidth && img.naturalHeight) {
			return { w: img.naturalWidth, h: img.naturalHeight };
		}
	}

	return { w: 0, h: 0 };
}

/**
 * Shared domItemData filter for all PhotoSwipeLightbox instances.
 * Resolves dimensions and captions from DOM attributes.
 *
 * @param {Object} itemData
 * @param {HTMLElement} element
 * @param {HTMLElement} linkEl
 * @returns {Object}
 */
function domItemDataFilter(itemData, element, linkEl) {
	if (linkEl) {
		const dims = resolveDimensions(linkEl);
		if (dims.w) itemData.w = dims.w;
		if (dims.h) itemData.h = dims.h;

		// Caption support
		const caption = linkEl.dataset.caption;
		if (caption) itemData.alt = caption;
	}
	return itemData;
}

/**
 * Create and register a PhotoSwipeLightbox instance.
 *
 * @param {Object} options - PhotoSwipeLightbox options
 * @returns {PhotoSwipeLightbox}
 */
function createLightbox(options, galleryEl) {
	// Skip if this gallery element already has a lightbox registered
	const key = galleryEl || options.gallery;
	if (key && typeof key !== 'string' && instances.has(key)) return null;

	const lightbox = new PhotoSwipeLightbox({
		...options,
		pswpModule: PhotoSwipe,
	});
	lightbox.addFilter('domItemData', domItemDataFilter);
	lightbox.init();

	// Track by gallery element for scoped cleanup
	if (key && typeof key !== 'string') {
		const existing = instances.get(key) || [];
		existing.push(lightbox);
		instances.set(key, existing);
		trackedEls.add(key);
	}

	return lightbox;
}

const FxLightbox = {
	/**
	 * Initialize all lightbox bindings in root
	 * @param {Document|Element} root - Root element to search
	 */
	initAll(root = document) {
		// --- Grouped galleries: [data-lightbox="group-name"] ---
		const groups = collectGroups(root);
		groups.forEach((groupName) => {
			const galleryEls = qsa(`[data-lightbox="${CSS.escape(groupName)}"]`, root);
			if (!galleryEls.length) return;

			// Skip groups managed by gallery-thumbs.js (WC gallery has its own PhotoSwipe handler)
			if (galleryEls[0].closest('[data-wc-gallery]')) return;

			// Find common parent for gallery scope
			const galleryParent = galleryEls[0].closest('[data-fx-lightbox]') || galleryEls[0].parentElement || root;

			createLightbox({
				gallery: galleryParent,
				children: `[data-lightbox="${CSS.escape(groupName)}"]`,
			}, galleryParent);
		});

		// --- Legacy containers: [id^="gallery-"] ---
		qsa('[id^="gallery-"]', root).forEach((container) => {
			createLightbox({
				gallery: container,
				children: 'a',
			}, container);
		});

		// --- Legacy individual links: [data-rel="lightbox"] ---
		const relLinks = qsa('[data-rel="lightbox"]', root);
		if (relLinks.length) {
			const relRoot = root === document ? document.body : root;
			createLightbox({
				gallery: relRoot,
				children: '[data-rel="lightbox"]',
			}, relRoot);
		}

		// --- Custom [data-fx-lightbox] containers ---
		qsa('[data-fx-lightbox]', root).forEach((container) => {
			createLightbox({
				gallery: container,
				children: 'a',
			}, container);
		});

		Events.emit('fx:lightbox:init', { root });
	},

	/**
	 * Destroy lightbox instances within root.
	 * @param {Document|Element} root - Root element to scope destruction
	 */
	destroyAll(root = document) {
		if (root === document) {
			// Full teardown — iterate via trackedEls (WeakMap is not iterable)
			trackedEls.forEach((el) => (instances.get(el) || []).forEach((lb) => lb.destroy()));
			trackedEls.clear();
		} else {
			// Scoped teardown — only destroy instances whose gallery is inside root
			for (const el of trackedEls) {
				if (root.contains(el)) {
					(instances.get(el) || []).forEach((lb) => lb.destroy());
					trackedEls.delete(el);
				}
			}
		}
		Events.emit('fx:lightbox:destroy', { root });
	},
};

export default FxLightbox;
