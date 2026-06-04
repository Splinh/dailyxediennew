// slider/fx-slider.controls.js

import { $ as qs, create, addClass } from '../../dom.js';

/**
 * Get or create swiper controls container
 * @param {HTMLElement} el - Swiper container
 * @returns {HTMLElement}
 */
export const getControls = (el) => {
	const wrapper = el.closest('.closest-swiper') || el.parentElement;
	const existing = wrapper ? qs('.swiper-controls', wrapper) : null;
	if (existing) return existing;

	const div = create('div', { class: 'swiper-controls' });
	el.after(div);
	return div;
};

/**
 * Build navigation buttons
 * @param {Object} options - Parsed options
 * @param {Object} classes - Generated class names
 * @param {HTMLElement} controls - Controls container
 * @param {DocumentFragment} fragment - Fragment to append to
 * @returns {Object|null} - Navigation config for Swiper
 */
export const buildNavigation = (options, classes, controls, fragment) => {
	if (!options.navigation) return null;

	let btnPrev = controls ? qs('.swiper-button-prev', controls) : null;
	let btnNext = controls ? qs('.swiper-button-next', controls) : null;

	if (btnPrev && btnNext) {
		addClass(btnPrev, classes.prev);
		addClass(btnNext, classes.next);
	} else {
		btnPrev = create('button', {
			type: 'button',
			class: `swiper-button swiper-button-prev ${classes.prev}`,
			'aria-label': 'Previous slide',
			html: `<span class="size-5 flex"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg></span>`,
		});
		btnNext = create('button', {
			type: 'button',
			class: `swiper-button swiper-button-next ${classes.next}`,
			'aria-label': 'Next slide',
			html: `<span class="size-5 flex"><svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg></span>`,
		});
		fragment.append(btnPrev, btnNext);
	}

	if (typeof options.navigation === 'object' && options.navigation !== null) {
		const { nextEl, prevEl, ...rest } = options.navigation;
		return { nextEl: '.' + classes.next, prevEl: '.' + classes.prev, ...rest };
	}

	return {
		nextEl: '.' + classes.next,
		prevEl: '.' + classes.prev,
	};
};

/**
 * Build pagination
 * @param {Object} options - Parsed options
 * @param {Object} classes - Generated class names
 * @param {HTMLElement} controls - Controls container
 * @param {DocumentFragment} fragment - Fragment to append to
 * @returns {Object|null} - Pagination config for Swiper
 */
export const buildPagination = (options, classes, controls, fragment) => {
	if (!options.pagination) return null;

	let pagination = controls ? qs('.swiper-pagination', controls) : null;
	if (pagination) {
		addClass(pagination, classes.pagination);
	} else {
		pagination = create('div', { class: 'swiper-pagination ' + classes.pagination });
		fragment.append(pagination);
	}

	const isObj = typeof options.pagination === 'object' && options.pagination !== null;
	const type = isObj ? options.pagination.type || 'bullets' : options.pagination;

	// Strip managed keys — el is scoped, type is handled above
	const { el: _el, type: _type, ...rest } = isObj ? options.pagination : {};

	return {
		el: '.' + classes.pagination,
		clickable: true,
		// String shorthand: auto-enable dynamicBullets for convenience
		...(!isObj && type === 'bullets' && { dynamicBullets: true, type: 'bullets' }),
		...(type === 'fraction' && { type: 'fraction' }),
		...(type === 'progressbar' && { type: 'progressbar' }),
		...(type === 'custom' && { renderBullet: (i, cls) => '<span class="' + cls + '">' + (i + 1) + '</span>' }),
		...rest,
	};
};

/**
 * Build scrollbar
 * @param {Object} options - Parsed options
 * @param {Object} classes - Generated class names
 * @param {HTMLElement} controls - Controls container
 * @param {DocumentFragment} fragment - Fragment to append to
 * @returns {Object|null} - Scrollbar config for Swiper
 */
export const buildScrollbar = (options, classes, controls, fragment) => {
	if (!options.scrollbar) return null;

	let scrollbar = controls ? qs('.swiper-scrollbar', controls) : null;
	if (scrollbar) {
		addClass(scrollbar, classes.scrollbar);
	} else {
		scrollbar = create('div', { class: 'swiper-scrollbar ' + classes.scrollbar });
		fragment.append(scrollbar);
	}

	return {
		el: '.' + classes.scrollbar,
		hide: true,
		draggable: true,
	};
};

/**
 * Build autoplay progress indicator
 * @param {Object} options - Parsed options
 * @param {Object} classes - Generated class names
 * @param {HTMLElement} controls - Controls container
 * @param {DocumentFragment} fragment - Fragment to append to
 * @returns {Object|null} - Event handlers for Swiper
 */
export const buildAutoplayProgress = (options, classes, controls, fragment) => {
	if (!options.autoplayProgress) return null;

	const progress = create('div', { class: `swiper-autoplay-progress ${classes.progress}`, html: `<svg viewBox="0 0 48 48"><circle cx="24" cy="24" r="20"></circle></svg><span></span>` });
	fragment.append(progress);

	let lastSecond = -1;
	const spanEl = qs('span', progress);

	return {
		autoplayTimeLeft(s, time, progressValue) {
			const currentSecond = Math.ceil(time / 1000);
			if (currentSecond !== lastSecond) {
				lastSecond = currentSecond;
				progress.style.setProperty('--progress', 1 - progressValue);
				spanEl.textContent = `${currentSecond}s`;
			}
		},
	};
};
