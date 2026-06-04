// slider/fx-slider.options.js

/**
 * Parse options from data attribute
 * @param {HTMLElement} el - Swiper container
 * @returns {Object}
 */
import { $ as qs } from '../../dom.js';

export const parseOptions = (el) => {
	const json = el ? qs('.swiper-wrapper', el)?.dataset?.swiperOptions : null;
	if (!json) return {};
	try {
		return JSON.parse(json);
	} catch (e) {
		console.warn('[FxSlider] Invalid JSON on element', el, e);
		return {};
	}
};

/**
 * Convert shorthand breakpoints to Swiper format
 * @param {Object} options - Parsed options
 * @returns {import('swiper').SwiperOptions['breakpoints']}
 */
export const getBreakpoints = (options = {}) => {
	if (options.breakpoints) return options.breakpoints;

	const bp = {};
	const map = { xs: 0, sm: 640, md: 768, lg: 1024, xl: 1280, xxl: 1536 };
	Object.entries(map).forEach(([key, val]) => {
		const bpVal = Reflect.get(options, key);
		if (bpVal) Reflect.set(bp, val, bpVal);
	});
	return bp;
};

/**
 * Build Swiper options from parsed config
 * @param {Object} options - Parsed options
 * @param {Object} classes - Generated class names
 * @param {HTMLElement} el - Container element
 * @param {Swiper|null} thumbsSwiper - Thumbs swiper instance
 * @returns {Object}
 */
export const buildSwiperOptions = (options, classes, el, thumbsSwiper = null) => {
	const swiperOptions = {
		spaceBetween: parseInt(options.spaceBetween) || 0,
		slidesPerView: options.slidesPerView === 'auto' ? 'auto' : parseInt(options.slidesPerView) || 1,
		speed: parseInt(options.speed) || 600,
		direction: options.direction || 'horizontal',
		grabCursor: !!options.grabCursor,
		loop: !!options.loop,
		parallax: !!options.parallax,
		autoHeight: !!options.autoHeight,
		rewind: !!options.rewind,
		observer: !!options.observer,
		observeParents: !!options.observeParents,
		watchSlidesProgress: !!options.watchSlidesProgress,
		slideToClickedSlide: !!options.slideToClickedSlide,
		breakpoints: getBreakpoints(options),
	};

	// Thumbs
	if (thumbsSwiper) {
		swiperOptions.thumbs = { swiper: thumbsSwiper };
	}

	// FreeMode
	if (options.freeMode) {
		swiperOptions.freeMode = { enabled: true, sticky: true };
	}

	// CSS Mode
	if (options.cssMode) {
		swiperOptions.cssMode = true;
		swiperOptions.observer = true;
		swiperOptions.observeParents = true;
	}

	// Effect
	if (options.effect) {
		swiperOptions.effect = options.effect;
		if (options.effect === 'fade') {
			swiperOptions.fadeEffect = { crossFade: true };
		}
		if (options.effect === 'coverflow') {
			swiperOptions.coverflowEffect = {
				rotate: parseInt(options.coverflowEffect?.rotate) || 50,
				stretch: parseInt(options.coverflowEffect?.stretch) || 0,
				depth: parseInt(options.coverflowEffect?.depth) || 100,
				modifier: parseInt(options.coverflowEffect?.modifier) || 1,
				slideShadows: !!options.coverflowEffect?.slideShadows,
				scale: parseFloat(options.coverflowEffect?.scale) || 1,
			};
		}
	}

	// Centered
	if (options.centered) {
		swiperOptions.centeredSlides = true;
		swiperOptions.centeredSlidesBounds = options.centeredSlidesBounds ?? true;
	}

	// Autoplay
	if (options.autoplay) {
		swiperOptions.autoplay = {
			delay: parseInt(options.delay) || 6000,
			pauseOnMouseEnter: true,
			disableOnInteraction: options.disableOnInteraction ?? true,
			reverseDirection: !!options.reverseDirection,
		};
	}

	// Marquee (infinite loop)
	if (options.marquee) {
		swiperOptions.loop = true;
		swiperOptions.freeMode = { enabled: true, sticky: false, momentum: false };
		swiperOptions.speed = parseInt(options.speed) || 6000;
		swiperOptions.allowTouchMove = options.allowTouchMove ?? false;
		swiperOptions.autoplay = {
			delay: 0,
			pauseOnMouseEnter: options.pauseOnMouseEnter ?? false,
			disableOnInteraction: false,
			reverseDirection: !!options.reverseDirection,
		};
	}

	// RTL
	if (options.rtl) {
		el.setAttribute('dir', 'rtl');
	}

	// Grid rows
	if (options.rows) {
		swiperOptions.grid = {
			rows: parseInt(options.rows) || 1,
			fill: 'row',
		};
	}

	// Mousewheel / Trackpad
	if (options.mousewheel) {
		swiperOptions.mousewheel = {
			enabled: true,
			forceToAxis: true,
			releaseOnEdges: true,
			sensitivity: parseFloat(options.mousewheel?.sensitivity) || 1,
			thresholdDelta: parseInt(options.mousewheel?.thresholdDelta) || 6,
		};
	}

	return swiperOptions;
};
