// modules/woocommerce/swatches/variation-swatches.js
// Click swatch → sync hidden select → trigger WC variation change

import './variation-swatches.scss';

import { $ as qs, $$ as qsa, on, off, hasClass, addClass, removeClass, toggleClass } from '../../../dom.js';

// ── Shared per-form cache ─────────────────────────────────────
// Avoids parsing the same large JSON once per attribute container.
const formVariationCache = new WeakMap();

/** Per-container cleanup refs for native handlers */
const cleanupRefs = new WeakMap();

function getFormVariations(form) {
	if (formVariationCache.has(form)) return formVariationCache.get(form);

	const json = form.dataset.product_variations;
	if (!json) return null;

	try {
		const data = JSON.parse(json);
		formVariationCache.set(form, data);
		return data;
	} catch {
		return null;
	}
}

// ── Init: Single Product Swatches ─────────────────────────────

/**
 * Initialize swatches within a root element.
 * @param {HTMLElement} root
 */
function initSwatches(root) {
	const containers = qsa('[data-wc-swatches]', root);
	if (!containers.length) return;

	containers.forEach((container) => {
		if (container._hdSwatchInit) return;
		container._hdSwatchInit = true;

		const attribute = container.dataset.attribute;
		const form = container.closest('.variations_form, form.cart');
		if (!form) return;

		const select = qs(`select[data-attribute_name="attribute_${attribute}"], select#${attribute}`, form);
		if (!select) return;

		const clearOnReselect = container.hasAttribute('data-clear-reselect');

		// ── Click handler (stored for cleanup) ──
		const clickHandler = (e) => {
			const btn = e.target.closest('.hd-swatch');
			if (!btn || hasClass(btn, 'is-disabled')) return;

			// P2c: Skip radio-type swatches — handled by initRadioSync.
			if (hasClass(btn, 'hd-swatch--radio')) return;

			const value = btn.dataset.value;
			const wasSelected = hasClass(btn, 'is-selected');

			// Deselect all in this group
			qsa('.hd-swatch', container).forEach((s) => removeClass(s, 'is-selected'));

			if (wasSelected && clearOnReselect) {
				select.value = '';
			} else if (wasSelected && !clearOnReselect) {
				addClass(btn, 'is-selected');
				return;
			} else {
				addClass(btn, 'is-selected');
				select.value = value;
			}

			select.dispatchEvent(new Event('change', { bubbles: true }));
		};
		on(container, 'click', clickHandler);

		// Sync swatch state when WC updates the select
		const observer = new MutationObserver(() => {
			syncSwatchState(container, select);
		});
		observer.observe(select, { childList: true, attributes: true, attributeFilter: ['disabled'] });

		// Store refs for cleanup
		cleanupRefs.set(container, { clickHandler, observer });

		// WC custom events — use single handler, debounced via rAF
		const $form = jQuery?.(form);
		if ($form?.length) {
			let syncPending = false;
			const debouncedSync = () => {
				if (syncPending) return;
				syncPending = true;
				requestAnimationFrame(() => {
					syncSwatchState(container, select);
					syncPending = false;
				});
			};

			$form.on('woocommerce_variation_select_change.hdSwatches wc_variation_form.hdSwatches check_variations.hdSwatches', debouncedSync);

			$form.on('reset_data.hdSwatches', () => {
				qsa('.hd-swatch', container).forEach((s) => {
					removeClass(s, 'is-selected is-disabled');
					s.setAttribute('aria-checked', 'false');
				});
				updateSelectedLabel(container, null);
			});
		}

		applyDisplayLimit(container);
	});
}

/**
 * Sync swatch visual state with hidden select.
 * @param {HTMLElement} container
 * @param {HTMLSelectElement} select
 */
function syncSwatchState(container, select) {
	const availableValues = new Set();
	for (const option of select.options) {
		if (option.value && !option.disabled) {
			availableValues.add(option.value);
		}
	}

	const selectedValue = select.value;

	qsa('.hd-swatch', container).forEach((btn) => {
		const value = btn.dataset.value;
		toggleClass(btn, 'is-disabled', !availableValues.has(value));

		const isSelected = selectedValue === value;
		toggleClass(btn, 'is-selected', isSelected);
		btn.setAttribute('aria-checked', isSelected ? 'true' : 'false');
	});

	updateSelectedLabel(container, select);
}

/**
 * Update the "selected label" text next to attribute label.
 * @param {HTMLElement} container
 * @param {HTMLSelectElement|null} select
 */
function updateSelectedLabel(container, select) {
	if (!container.hasAttribute('data-show-label')) return;

	const row = container.closest('tr, .variations .value');
	const label = row?.querySelector('th label') || row?.previousElementSibling?.querySelector('label') || row?.closest('.variations tr')?.querySelector('label');
	if (!label) return;

	const oldSpan = label.querySelector('.hd-swatch-selected-label');
	if (oldSpan) oldSpan.remove();

	if (!select) return;

	const selectedOption = select.options.item(select.selectedIndex);
	if (selectedOption?.value) {
		const separator = container.dataset.labelSeparator || ':';
		const span = document.createElement('span');
		span.className = 'hd-swatch-selected-label';
		span.textContent = ` ${separator} ${selectedOption.textContent.trim()}`;
		label.appendChild(span);
	}
}

/**
 * Apply display limit — hide overflow swatches with "+N more" toggle.
 * @param {HTMLElement} container
 */
function applyDisplayLimit(container) {
	const limit = parseInt(container.dataset.displayLimit, 10);
	if (!limit || limit <= 0) return;
	if (qs('.hd-swatch-more', container)) return;

	const swatches = qsa('.hd-swatch', container);
	if (swatches.length <= limit) return;

	let isExpanded = false;
	const overflowCount = swatches.length - limit;

	swatches.forEach((s, i) => {
		if (i >= limit) addClass(s, 'is-overflow-hidden');
	});

	const moreBtn = document.createElement('button');
	moreBtn.type = 'button';
	moreBtn.className = 'hd-swatch-more';
	moreBtn.textContent = `+${overflowCount}`;
	moreBtn.setAttribute('aria-label', `Show ${overflowCount} more options`);
	container.appendChild(moreBtn);

	on(moreBtn, 'click', () => {
		isExpanded = !isExpanded;
		swatches.forEach((s, i) => {
			if (i >= limit) toggleClass(s, 'is-overflow-hidden', !isExpanded);
		});
		moreBtn.textContent = isExpanded ? '−' : `+${overflowCount}`;
	});
}

// ── Init: Archive Swatches ────────────────────────────────────

/**
 * Initialize archive swatch click/hover interaction.
 * @param {HTMLElement} root
 */
function initArchiveSwatches(root) {
	const groups = qsa('.hd-archive-swatches__group', root);
	if (!groups.length) return;

	groups.forEach((group) => {
		if (group._hdArchiveInit) return;
		group._hdArchiveInit = true;

		const isImageSwap = group.hasAttribute('data-image-swap');
		const card = group.closest('.product, .wc-block-grid__product');
		if (!card) return;

		// Cache card image reference
		let cardImg = null;
		const getCardImg = () => cardImg || (cardImg = qs('img.wp-post-image, .attachment-woocommerce_thumbnail', card));

		const swapImage = (swatch) => {
			if (!isImageSwap || !swatch?.dataset.imageSrc) return;
			const img = getCardImg();
			if (!img) return;

			if (!card._originalImage) {
				card._originalImage = { src: img.src, srcset: img.srcset || '', sizes: img.sizes || '' };
			}

			img.src = swatch.dataset.imageSrc;
			if (swatch.dataset.imageSrcset) img.srcset = swatch.dataset.imageSrcset;
			if (swatch.dataset.imageSizes) img.sizes = swatch.dataset.imageSizes;
		};

		const restoreImage = () => {
			if (!card._originalImage) return;
			const selected = qs('.hd-archive-swatch.is-selected[data-image-src]', group);
			if (selected) {
				swapImage(selected);
				return;
			}
			const img = getCardImg();
			if (!img) return;
			img.src = card._originalImage.src;
			img.srcset = card._originalImage.srcset;
			img.sizes = card._originalImage.sizes;
		};

		on(group, 'click', (e) => {
			const swatch = e.target.closest('.hd-archive-swatch:not(.hd-archive-swatch--more)');
			if (!swatch) return;
			qsa('.hd-archive-swatch.is-selected', group).forEach((s) => s.classList.remove('is-selected'));
			swatch.classList.add('is-selected');
			swapImage(swatch);
		});

		if (isImageSwap) {
			on(group, 'mouseover', (e) => {
				const swatch = e.target.closest('.hd-archive-swatch[data-image-src]');
				if (swatch) swapImage(swatch);
			});
			on(group, 'mouseout', (e) => {
				if (e.target.closest('.hd-archive-swatch[data-image-src]')) restoreImage();
			});
		}
	});
}

// ── Init: Linkable URL ────────────────────────────────────────

/**
 * Linkable variation URL — update browser URL with selected attributes.
 * @param {HTMLElement} root
 */
function initLinkableUrl(root) {
	if (!document.body.classList.contains('single-product')) return;

	const containers = qsa('[data-wc-swatches][data-linkable-url]', root);
	if (!containers.length) return;

	qsa('.variations_form', root).forEach((form) => {
		if (form._hdLinkableInit) return;
		form._hdLinkableInit = true;

		const $form = jQuery?.(form);
		if (!$form?.length) return;

		$form.on('found_variation.hdLinkable', (_, variation) => {
			if (!variation) return;
			const url = new URL(window.location.href);
			Object.entries(variation.attributes || {}).forEach(([key, val]) => {
				val ? url.searchParams.set(key, val) : url.searchParams.delete(key);
			});
			history.replaceState(null, '', url.toString());
		});

		$form.on('reset_data.hdLinkable', () => {
			const url = new URL(window.location.href);
			[...url.searchParams.keys()].forEach((key) => {
				if (key.startsWith('attribute_')) url.searchParams.delete(key);
			});
			history.replaceState(null, '', url.toString());
		});
	});
}

// ── Init: Variation Image Preview ─────────────────────────────

/**
 * Hover preview — temporarily swap gallery main image.
 * Uses locked state machine to avoid conflicts with Gallery's variation swap.
 * @param {HTMLElement} root
 */
function initVariationPreview(root) {
	if (!document.body.classList.contains('single-product')) return;

	const containers = qsa('[data-wc-swatches][data-preview-attribute]', root);
	if (!containers.length) return;

	containers.forEach((container) => {
		if (container._hdPreviewInit) return;
		container._hdPreviewInit = true;

		const form = container.closest('.variations_form');
		if (!form) return;

		// Shared cache — parsed once per form, not per container
		const variations = getFormVariations(form);
		if (!variations) return;

		const attrName = 'attribute_' + container.dataset.attribute;

		// Pre-build lookup map: value → variation image (O(1) vs O(n) on every hover)
		const imageMap = new Map();
		let anyValueImage = null;
		for (const v of variations) {
			const val = v.attributes[attrName];
			if (val === '') {
				// "Any" match — fallback for unspecified attribute
				if (!anyValueImage && v.image?.src) anyValueImage = v.image;
			} else if (val && v.image?.src && !imageMap.has(val)) {
				imageMap.set(val, v.image);
			}
		}

		// No images to preview for this attribute
		if (!imageMap.size && !anyValueImage) return;

		let saved = null;
		let locked = false;

		// Cache product element (stable across hover events)
		const product = form.closest('.product');
		if (!product) return;

		const getMainImg = () =>
			qs('.hd-gallery__slider .swiper-slide-active .hd-gallery-zoom__img', product) || qs('.hd-gallery-zoom__img', product) || qs('.woocommerce-product-gallery__image img', product);

		on(container, 'mouseover', (e) => {
			const swatch = e.target.closest('.hd-swatch');
			if (!swatch || hasClass(swatch, 'is-selected') || hasClass(swatch, 'is-disabled') || locked) return;

			const varImage = imageMap.get(swatch.dataset.value) || anyValueImage;
			if (!varImage) return;

			const img = getMainImg();
			if (!img) return;

			if (!saved) saved = { src: img.src, srcset: img.srcset || '' };

			img.src = varImage.src;
			if (varImage.srcset) img.srcset = varImage.srcset;
		});

		on(container, 'mouseleave', () => {
			if (locked || !saved) {
				saved = null;
				locked = false;
				return;
			}
			const img = getMainImg();
			if (img) {
				img.src = saved.src;
				img.srcset = saved.srcset;
			}
			saved = null;
		});

		on(container, 'click', (e) => {
			if (e.target.closest('.hd-swatch')) {
				locked = true;
				saved = null;
			}
		});

		const $form = jQuery?.(form);
		if ($form?.length) {
			$form.on('found_variation.hdPreview reset_data.hdPreview', () => {
				saved = null;
			});
		}
	});
}

// ── Init: Radio Swatches ──────────────────────────────────────

/**
 * Radio swatch sync — radio change → update hidden WC select.
 * @param {HTMLElement} root
 */
function initRadioSync(root) {
	const containers = qsa('.hd-swatches--radio', root);
	if (!containers.length) return;

	containers.forEach((container) => {
		if (container._hdRadioInit) return;
		container._hdRadioInit = true;

		const attribute = container.dataset.attribute;
		const form = container.closest('.variations_form, form.cart');
		if (!form || !attribute) return;

		const select = qs(`select[data-attribute_name="attribute_${attribute}"], select#${attribute}`, form);
		if (!select) return;

		on(container, 'change', (e) => {
			if (e.target.type !== 'radio') return;
			select.value = e.target.value;
			select.dispatchEvent(new Event('change', { bubbles: true }));
		});

		// Sync radio disabled state when WC updates the select options.
		const radioObserver = new MutationObserver(() => {
			const availableValues = new Set();
			for (const option of select.options) {
				if (option.value && !option.disabled) availableValues.add(option.value);
			}
			qsa('input[type="radio"]', container).forEach((radio) => {
				const label = radio.closest('.hd-swatch--radio');
				if (label) toggleClass(label, 'is-disabled', !availableValues.has(radio.value));
			});
		});
		radioObserver.observe(select, { childList: true, attributes: true, attributeFilter: ['disabled'] });

		const $form = jQuery?.(form);
		if ($form?.length) {
			$form.on('reset_data.hdRadio', () => {
				qsa('input[type="radio"]', container).forEach((radio) => {
					radio.checked = false;
				});
				qsa('.hd-swatch--radio', container).forEach((label) => {
					removeClass(label, 'is-disabled');
				});
			});
		}
	});
}

// ── Cleanup ───────────────────────────────────────────────────

function destroySwatches(root) {
	qsa('.variations_form', root).forEach((form) => {
		jQuery?.(form)?.off('.hdSwatches').off('.hdLinkable').off('.hdPreview').off('.hdRadio');
		delete form._hdLinkableInit;
		formVariationCache.delete(form);
	});

	qsa('[data-wc-swatches]', root).forEach((c) => {
		// Remove native handlers and observer
		const refs = cleanupRefs.get(c);
		if (refs) {
			if (refs.clickHandler) off(c, 'click', refs.clickHandler);
			if (refs.observer) refs.observer.disconnect();
			cleanupRefs.delete(c);
		}
		delete c._hdSwatchInit;
		delete c._hdPreviewInit;
	});
	qsa('.hd-archive-swatches__group', root).forEach((g) => {
		delete g._hdArchiveInit;
	});
	qsa('.hd-swatches--radio', root).forEach((c) => {
		delete c._hdRadioInit;
	});
}

// ── Self re-init after AJAX filter replaces product grid ──────
// Archive swatches bind per-element; after filter AJAX replaces the grid,
// new product cards need re-binding. QuickView uses document-level delegation
// and doesn't need this.
on(document, 'hd:filter:updated', () => {
	const grid = document.querySelector('.products');
	if (grid) initArchiveSwatches(grid);
});

// ── Public API ────────────────────────────────────────────────

export default {
	initAll(root = document) {
		initSwatches(root);
		initArchiveSwatches(root);
		initRadioSync(root);
		initLinkableUrl(root);
		initVariationPreview(root);
	},

	destroyAll(root = document) {
		destroySwatches(root);
	},
};
