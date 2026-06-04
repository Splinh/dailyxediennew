// woocommerce.js — WooCommerce-specific custom scripts
// Conditionally loaded on WC pages (not a chunk — a separate entry point).
// Module chunks (gallery, swatches, etc.) are lazy-loaded via core/modules/woocommerce/.
// CSS: woocommerce.scss is a separate Vite entry, enqueued by PHP independently.

const run = () => {
	// ── Vanilla JS (no jQuery dependency) ──

	// ── jQuery-dependent WC integration ──
	if (window.jQuery) {
		jQuery(() => {
			// jQuery-dependent code here
		});
	}
};

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', run, { once: true }) : run();
