// slider/fx-slider.js
import './fx-slider.scss';

import { $ as qs, $$ as qsa, on, uid, addClass } from '../../dom.js';
import { createWeakStore } from '../../weak.js';
import Events from '../../events.js';

import Swiper from 'swiper';
import { Autoplay, Navigation, Pagination, Scrollbar, Thumbs, FreeMode, Grid, EffectFade, EffectCoverflow, Mousewheel } from 'swiper/modules';

import { parseOptions, buildSwiperOptions } from './fx-slider.options.js';
import { getControls, buildNavigation, buildPagination, buildScrollbar, buildAutoplayProgress } from './fx-slider.controls.js';

const SELECTOR = '[data-fx-slider]';

const instanceStore = createWeakStore();
const observerStore = createWeakStore();
const autoplayObserverStore = createWeakStore();

// Track elements with autoplay for tab visibility management
const autoplayElements = new Set();
const inViewport = new WeakMap();

/**
 * Default Swiper options
 */
const defaultOptions = {
	allowTouchMove: true,
	threshold: 5,
	wrapperClass: 'swiper-wrapper',
	slideClass: 'swiper-slide',
	slideActiveClass: 'swiper-slide-active',
};

/**
 * Generate unique class names for each instance
 * @returns {Object}
 */
const generateClasses = () => {
	const id = uid('sw');
	return {
		id,
		swiper: `swiper-${id}`,
		next: `swiper-next-${id}`,
		prev: `swiper-prev-${id}`,
		pagination: `swiper-pagination-${id}`,
		scrollbar: `swiper-scrollbar-${id}`,
		progress: `swiper-autoplay-progress-${id}`,
	};
};

/**
 * Initialize a single Swiper instance
 * @param {HTMLElement} el - Swiper container
 * @returns {Swiper|null}
 */
const initSwiper = (el) => {
	if (!el || instanceStore.has(el)) return null;

	const classes = generateClasses();
	addClass(el, classes.swiper);

	const options = parseOptions(el);
	if (!options || Object.keys(options).length === 0) {
		console.warn('[FxSlider] Skipped:', el, '(no valid options)');
		return null;
	}

	// Thumbs swiper
	let thumbsSwiper = null;

	if (options.thumbs?.swiper) {
		const thumbsOption = options.thumbs.swiper;
		if (thumbsOption instanceof Swiper) {
			thumbsSwiper = thumbsOption;
		} else if (typeof thumbsOption === 'string') {
			const thumbsEl = qs(thumbsOption);
			if (thumbsEl) {
				thumbsSwiper = instanceStore.get(thumbsEl) || initSwiper(thumbsEl);
			}
		}
		if (thumbsSwiper) {
			options.thumbs.swiper = thumbsSwiper;
		}
	}

	// Build modules dynamically based on what this slider actually needs
	const modules = getRequiredModules(options);

	// Build base options
	const swiperOptions = {
		modules,
		...defaultOptions,
		...buildSwiperOptions(options, classes, el, thumbsSwiper),
	};

	// Build controls
	const controls = getControls(el);
	const fragment = document.createDocumentFragment();

	const navigation = buildNavigation(options, classes, controls, fragment);
	if (navigation) swiperOptions.navigation = navigation;

	const pagination = buildPagination(options, classes, controls, fragment);
	if (pagination) swiperOptions.pagination = pagination;

	const scrollbar = buildScrollbar(options, classes, controls, fragment);
	if (scrollbar) swiperOptions.scrollbar = scrollbar;

	const progressHandler = buildAutoplayProgress(options, classes, controls, fragment);
	if (progressHandler) swiperOptions.on = progressHandler;

	// Append controls to DOM
	if (fragment.childNodes.length) {
		controls.append(fragment);
	}

	// Create instance
	const instance = new Swiper(`.${classes.swiper}`, swiperOptions);
	el.swiper = instance;
	instanceStore.set(el, instance);

	// Viewport-based autoplay control
	setupAutoplayObserver(el, instance);

	Events.emit('fx:slider:init', { el, instance });

	return instance;
};

const FxSlider = {
	/**
	 * Initialize all sliders in root (with lazy loading via IntersectionObserver)
	 * @param {Document|Element} root - Root element to search
	 */
	initAll(root = document) {
		qsa(SELECTOR, root).forEach(
			/** @param {HTMLElement} el */
			(el) => {
				if (instanceStore.has(el)) return;

				// Lazy init with IntersectionObserver
				if ('IntersectionObserver' in window) {
					const observer = new IntersectionObserver(
						([entry], obs) => {
							if (entry.isIntersecting) {
								initSwiper(entry.target);
								obs.unobserve(entry.target);
								observerStore.delete(entry.target);
							}
						},
						{ rootMargin: '100px' },
					);

					observer.observe(el);
					observerStore.set(el, observer);
				} else {
					// Fallback: init immediately
					initSwiper(el);
				}
			},
		);
	},

	/**
	 * Destroy all sliders in root
	 * @param {Document|Element} root - Root element to search
	 */
	destroyAll(root = document) {
		qsa(SELECTOR, root).forEach(
			/** @param {HTMLElement} el */
			(el) => {
				// Disconnect lazy-init observer
				observerStore.cleanup(el, (obs) => obs.disconnect());

				// Disconnect autoplay viewport observer
				autoplayObserverStore.cleanup(el, (obs) => obs.disconnect());
				autoplayElements.delete(el);
				inViewport.delete(el);

				// Destroy swiper instance
				instanceStore.cleanup(el, (instance) => {
					instance.destroy(true, true);
					delete el['swiper'];
				});
			},
		);

		Events.emit('fx:slider:destroy');
	},

	/**
	 * Expose Swiper class for direct access
	 */
	Swiper,

	/**
	 * Manually init a specific element
	 * @param {HTMLElement} el - Element to init
	 * @returns {Swiper|null}
	 */
	init: initSwiper,
};

/**
 * Determine which Swiper modules are actually needed
 * @param {Object} options - Parsed slider options
 * @returns {Array}
 */
const getRequiredModules = (options) => {
	const modules = [];
	if (options.autoplay || options.marquee) modules.push(Autoplay);
	if (options.navigation) modules.push(Navigation);
	if (options.pagination) modules.push(Pagination);
	if (options.thumbs) modules.push(Thumbs);
	if (options.freeMode || options.marquee) modules.push(FreeMode);
	if (options.rows) modules.push(Grid);
	if (options.scrollbar) modules.push(Scrollbar);
	if (options.effect === 'fade') modules.push(EffectFade);
	if (options.effect === 'coverflow') modules.push(EffectCoverflow);
	if (options.mousewheel) modules.push(Mousewheel);
	return modules;
};

/**
 * Freeze autoplay.
 * For marquee (delay: 0), autoplay.stop() only clears the JS timer
 * but the CSS transition keeps running — acceptable natural drift.
 *
 * @param {Swiper} instance - Swiper instance
 */
const freezeAutoplay = (instance) => {
	instance.autoplay.stop();
};

/**
 * Resume autoplay from frozen state.
 *
 * @param {Swiper} instance - Swiper instance
 */
const resumeAutoplay = (instance) => {
	instance.autoplay.start();
};

/**
 * Persistent IntersectionObserver for autoplay viewport control.
 * Starts autoplay only when slider is in viewport AND tab is visible.
 * Stops autoplay when slider leaves viewport.
 *
 * @param {HTMLElement} el - Swiper container
 * @param {Swiper} instance - Swiper instance
 */
const setupAutoplayObserver = (el, instance) => {
	if (!instance.params.autoplay) return;

	autoplayElements.add(el);

	// Freeze immediately — let the observer trigger it when ready
	freezeAutoplay(instance);

	const observer = new IntersectionObserver(
		([entry]) => {
			if (instance.destroyed) return;

			inViewport.set(el, entry.isIntersecting);

			if (entry.isIntersecting && !document.hidden) {
				resumeAutoplay(instance);
			} else {
				freezeAutoplay(instance);
			}
		},
		{ threshold: 0 },
	);

	observer.observe(el);
	autoplayObserverStore.set(el, observer);
};

/**
 * Tab visibility handler.
 * Pauses all autoplay when tab is hidden, resumes only in-viewport sliders when visible.
 */
on(document, 'visibilitychange', () => {
	autoplayElements.forEach((el) => {
		const instance = instanceStore.get(el);
		if (!instance?.params?.autoplay || instance.destroyed) return;

		if (document.hidden) {
			freezeAutoplay(instance);
		} else if (inViewport.get(el)) {
			resumeAutoplay(instance);
		}
	});
});

export default FxSlider;
