// core/fx/index.js
// FX UI Components Loader

import { createLoader } from '../createLoader.js';
import Events from '../events.js';

const config = {
	smoothScroll: {
		selector: '[data-fx-scroll]',
		loader: () => import('./smoothscroll/fx-smoothscroll.js'),
	},
	tabs: {
		selector: '[data-fx-tabs]',
		loader: () => import('./tabs/fx-tabs.js'),
	},
	offCanvas: {
		selector: '[data-fx-off-canvas], [data-open], [data-close]',
		loader: () => import('./offcanvas/fx-offcanvas.js'),
	},
	dropdown: {
		selector: '[data-fx-dropdown], [data-fx-dropdown-toggle]',
		loader: () => import('./dropdown/fx-dropdown.js'),
	},
	dropdownMenu: {
		selector: '[data-fx-dropdown-menu]',
		loader: () => import('./dropdown/fx-dropdown-menu.js'),
	},
	accordion: {
		selector: '[data-fx-accordion]',
		loader: () => import('./accordion/fx-accordion.js'),
	},
	accordionMenu: {
		selector: '[data-fx-accordion-menu]',
		loader: () => import('./accordion/fx-accordion-menu.js'),
	},
	share: {
		selector: '[data-fx-share]',
		loader: () => import('./share/fx-share.js'),
	},
	modal: {
		selector: '[data-fx-modal]',
		loader: () => import('./modal/fx-modal.js'),
	},
	lightbox: {
		selector: '[data-lightbox], [id^="gallery-"] a, [data-rel="lightbox"], [data-fx-lightbox]',
		loader: () => import('./lightbox/fx-lightbox.js'),
	},
	slider: {
		selector: '[data-fx-slider]',
		loader: () => import('./slider/fx-slider.js'),
	},
	sticky: {
		selector: '[data-fx-sticky]',
		loader: () => import('./sticky/fx-sticky.js'),
	},
	scrollTop: {
		selector: '[data-fx-scroll-top]',
		loader: () => import('./scroll-top/fx-scroll-top.js'),
	},
	masonry: {
		selector: '[data-fx-masonry]',
		loader: () => import('./grid/fx-masonry.js'),
	},
	freeform: {
		selector: '[data-fx-freeform]',
		loader: () => import('./grid/fx-freeform.js'),
	},
	hybrid: {
		selector: '[data-fx-hybrid]',
		loader: () => import('./grid/fx-hybrid.js'),
	},
	videoEmbed: {
		selector: '[data-fx-video]',
		loader: () => import('./video-embed/fx-video-embed.js'),
	},
	lottie: {
		selector: '[data-fx-lottie]',
		loader: () => import('./animation/fx-lottie.js'),
	},
	magnetic: {
		selector: '[data-fx-magnetic]',
		loader: () => import('./animation/fx-magnetic.js'),
	},
	counter: {
		selector: '[data-fx-counter]',
		loader: () => import('./animation/fx-counter.js'),
	},
	scrollspy: {
		selector: '[data-fx-scrollspy]',
		loader: () => import('./scrollspy/fx-scrollspy.js'),
	},
};

const FX = createLoader(config, 'FX');

// Event system
FX.on = Events.on.bind(Events);
FX.off = Events.off.bind(Events);
FX.emit = Events.emit.bind(Events);

export default FX;
