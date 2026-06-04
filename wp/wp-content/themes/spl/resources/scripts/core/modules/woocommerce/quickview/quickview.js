// modules/woocommerce/quickview/quickview.js
// Quick View popup — fetch product HTML via REST, display in FxModal

import FxModal from '../../../fx/modal/fx-modal.js';

import { $ as qs, on, off, addClass, removeClass } from '../../../dom.js';

import './quickview.scss';

/** Singleton modal instance (created once, reused per open/close) */
const modal = new FxModal();

/** Track roots that already have the click listener to prevent double binding */
const boundRoots = new WeakSet();

/**
 * Initialize Quick View buttons within a root element.
 * @param {HTMLElement} root
 */
function initQuickView(root) {
	if (boundRoots.has(root)) return;
	boundRoots.add(root);
	on(root, 'click', handleClick);
}

/**
 * Handle Quick View button click (delegated).
 * @param {MouseEvent} e
 */
async function handleClick(e) {
	const btn = e.target.closest('[data-wc-quickview]');
	if (!btn || btn.classList.contains('is-loading')) return;

	e.preventDefault();
	e.stopPropagation();

	const productId = btn.dataset.productId;
	if (!productId) return;

	addClass(btn, 'is-loading');

	try {
		const res = await fetch(`${window.hdConfig?.restApiUrl || '/wp-json/hd/v1/'}wc-quickview/${productId}`, {
			method: 'GET',
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
			},
		});

		const json = await res.json();
		removeClass(btn, 'is-loading');

		if (!json.success || !json.data?.html) return;

		// Show popup via FxModal
		modal.show(json.data.html, {
			className: 'hd-quickview-modal',
			onOpen: (dialog) => {
				const contentEl = dialog.querySelector('.hd-modal__content');
				if (!contentEl) return;

				// WC variation form init (always, WC core dependency)
				const form = qs('.variations_form', contentEl);
				if (form) {
					jQuery?.(form)?.wc_variation_form();
				}

				// Intercept add-to-cart form submit (AJAX, no reload)
				initAjaxAddToCart(contentEl);

				// Scan popup DOM — auto-inits ALL registered modules (gallery, swatches, etc.)
				document.dispatchEvent(
					new CustomEvent('core:scan', {
						detail: { root: contentEl },
					}),
				);
			},
		});
	} catch (err) {
		console.error('[QuickView]', err);
		removeClass(btn, 'is-loading');
	}
}

/**
 * Intercept add-to-cart form in Quick View popup.
 * All submits go through AJAX (no reload, update mini-cart).
 *
 * @param {HTMLElement} popupEl
 */
function initAjaxAddToCart(popupEl) {
	const form = qs('form.cart', popupEl);
	if (!form) return;

	on(form, 'submit', async (e) => {
		e.preventDefault();

		const btn = qs('.single_add_to_cart_button', form);
		if (!btn) return;

		// Save original text
		if (!btn.dataset.originalText) {
			btn.dataset.originalText = btn.textContent;
		}

		addClass(btn, 'is-loading');

		const formData = new FormData(form);

		// Include submitter value — WC simple form uses `name="add-to-cart" value="$id"`
		// on the submit button, which FormData(form) alone doesn't capture.
		const submitter = e.submitter;
		if (submitter?.name && !formData.has(submitter.name)) {
			formData.append(submitter.name, submitter.value);
		}

		try {
			const wcAjaxUrl = window.wc_add_to_cart_params?.wc_ajax_url?.replace('%%endpoint%%', 'hd_quickview_add_cart') || '/?wc-ajax=hd_quickview_add_cart';

			const res = await fetch(wcAjaxUrl, {
				method: 'POST',
				body: formData,
				headers: {
					'X-WP-Nonce': window.hdConfig?.restToken || '',
				},
			});

			const data = await res.json();
			removeClass(btn, 'is-loading');

			if (!data.success && data.data?.error) {
				// Show WC error notices
				const info = qs('.hd-quickview__info', popupEl);
				if (info) {
					const errors = data.data.error;
					const msg = Array.isArray(errors)
						? errors
								.map((n) => (typeof n === 'object' ? n.notice || '' : n))
								.filter(Boolean)
								.join(', ')
						: String(errors);
					const errorEl = document.createElement('div');
					errorEl.className = 'woocommerce-error';
					errorEl.setAttribute('role', 'alert');
					errorEl.textContent = msg;
					info.prepend(errorEl);
					setTimeout(() => errorEl.remove(), 5000);
				}
				return;
			}

			// Trigger WC standard event — cart-fragments.js handles
			// fragment replacement, WC Blocks handles store invalidation,
			// and analytics/offcanvas hooks fire correctly.
			jQuery?.(document.body)?.trigger('added_to_cart', [
				data.fragments ?? null,
				data.cart_hash ?? '',
				jQuery?.(btn),
			]);

			// Success feedback — role="status" announces to screen readers politely
			btn.textContent = '✓ Đã thêm';
			btn.setAttribute('role', 'status');
			setTimeout(() => {
				btn.textContent = btn.dataset.originalText || 'Thêm giỏ hàng';
				btn.removeAttribute('role');
			}, 2000);
		} catch {
			removeClass(btn, 'is-loading');
		}
	});
}

/**
 * Clean up Quick View event listeners.
 * @param {HTMLElement} root
 */
function destroyQuickView(root) {
	off(root, 'click', handleClick);
	boundRoots.delete(root);
}

export default {
	initAll(root = document) {
		initQuickView(root);
	},

	destroyAll(root = document) {
		destroyQuickView(root);
	},
};
