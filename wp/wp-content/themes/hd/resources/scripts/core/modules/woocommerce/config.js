// modules/woocommerce/config.js
// WooCommerce lazy-load config — each sub-module keyed by its data-wc-* selector.

export default {
	wcGallery: {
		selector: '[data-wc-gallery]',
		loader: () => import('./gallery/gallery-thumbs.js'),
	},
	wcGalleryTracking: {
		selector: '[data-wc-gallery][data-tracking]',
		loader: () => import('./gallery/gallery-tracking.js'),
	},
	wcSwatches: {
		selector: '[data-wc-swatches]',
		loader: () => import('./swatches/variation-swatches.js'),
	},
	wcQuickView: {
		selector: '[data-wc-quickview]',
		loader: () => import('./quickview/quickview.js'),
	},
	wcFilter: {
		selector: '[data-wc-filter]',
		loader: () => import('./filter/filter.js'),
	},
};
