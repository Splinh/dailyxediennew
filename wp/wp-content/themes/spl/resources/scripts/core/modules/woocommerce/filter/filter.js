// modules/woocommerce/filter/filter.js
// AJAX Product Filter — fetch filtered products, sync URL, update counts.
import FxModal from '../../../fx/modal/fx-modal.js';
import { $ as qs, $$ as qsa, on, off, hasClass, addClass, removeClass, toggleClass, create } from '../../../dom.js';

import './filter.scss';

/** Per-container abort controllers — prevents cross-container request cancellation */
const containerRequests = new WeakMap();

/**
 * Debounce utility.
 * @param {Function} fn
 * @param {number} ms
 * @returns {Function}
 */
function debounce(fn, ms) {
	let timer;
	return function (...args) {
		clearTimeout(timer);
		timer = setTimeout(() => fn.apply(this, args), ms);
	};
}

/**
 * Initialize AJAX filter within a root element.
 * @param {HTMLElement} root
 */
function initFilter(root) {
	const container = getFilterContainer(root);
	if (!container || container._hdFilterInited) return;
	container._hdFilterInited = true;

	// Read trigger mode from data attribute
	const triggerMode = container.dataset.trigger || 'auto';
	const isDesktop = window.innerWidth >= 768;

	// Determine if auto-submit is active
	const autoSubmit = triggerMode === 'auto' || (triggerMode === 'hybrid' && isDesktop);

	// Store debounced refs for exact-match removal in destroyFilter
	const debouncedChange = autoSubmit ? debounce(handleFilterChange, 300) : null;

	if (debouncedChange) {
		on(container, 'change', debouncedChange);
	}

	// Manual mode: show Apply button
	if (!autoSubmit) {
		initManualApply(container);
	}

	// Bind swatch button clicks
	on(container, 'click', handleSwatchClick);

	// Bind search input
	const searchInput = qs('.hd-filter__search-input', container);
	let debouncedSearch = null;
	if (searchInput && autoSubmit) {
		debouncedSearch = debounce(handleFilterChange, 500);
		on(searchInput, 'input', debouncedSearch);
	}

	// Bind reset buttons + chip remove via delegation (survives AJAX replacement)
	on(container, 'click', handleDelegatedClick);

	// Init popover toggles (horizontal layout)
	const popovers = initPopovers(container);

	// Init more/less toggles
	initMoreLess(container);

	// Init inline term search
	initTermSearch(container);

	// Store cleanup refs
	container._hdFilterCleanup = { debouncedChange, debouncedSearch, searchInput, popovers };

	// Parse initial URL params → pre-fill filters + update active tags
	restoreFromUrl(container);

	// Mobile: floating filter button → full-screen popup
	if (!isDesktop) {
		initMobilePopup(container);
	}
}

/**
 * Handle swatch button click — toggle active state + trigger change.
 * @param {MouseEvent} e
 */
function handleSwatchClick(e) {
	const btn = e.target.closest('.hd-filter__swatch');
	if (!toggleSwatch(btn)) return;

	// Trigger change event for the filter container
	btn.closest('[data-wc-filter]')?.dispatchEvent(new Event('change', { bubbles: true }));
}

/**
 * Handle filter change — collect values, fetch, update DOM.
 * @param {Event} e
 */
async function handleFilterChange(e) {
	const container = e?.target?.closest?.('[data-wc-filter]') || qs('[data-wc-filter]');
	if (!container) return;

	const filters = collectFilters(container);
	const grid = qs('.products');

	// Show loading state
	addClass(grid, 'is-loading');

	// Cancel previous request for this specific container
	containerRequests.get(container)?.abort();
	const myController = new AbortController();
	containerRequests.set(container, myController);

	try {
		const res = await fetch(`${window.hdConfig?.restApiUrl || '/wp-json/hd/v1/'}wc-filter/products`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
				'X-WP-Nonce': window.hdConfig?.restToken || '',
			},
			body: JSON.stringify({
				filters,
				page: 1,
				per_page: 12,
				preset_id: parseInt(container.dataset.preset, 10) || 0,
			}),
			signal: myController.signal,
		});

		const json = await res.json();
		if (!json.success) return;

		// Replace product grid
		if (grid && json.data?.products_html) {
			grid.outerHTML = json.data.products_html;
		}

		// Replace pagination
		const pagination = qs('.woocommerce-pagination');
		if (json.data?.pagination_html) {
			if (pagination) {
				pagination.outerHTML = json.data.pagination_html;
			} else {
				// Append after product grid
				const newGrid = qs('.products');
				if (newGrid) {
					newGrid.insertAdjacentHTML('afterend', json.data.pagination_html);
				}
			}
		} else if (pagination) {
			pagination.remove();
		}

		// Update result count
		const countEl = qs('.woocommerce-result-count');
		if (countEl && json.data?.result_count !== undefined) {
			countEl.textContent = `${json.data.result_count} sản phẩm`;
		}

		// Update filter counts
		if (json.data?.counts) {
			updateCounts(container, json.data.counts);
		}

		// Sync URL
		history.pushState(null, '', buildUrl(filters));

		// Update active filters display
		updateActiveFilters(container);

		// Update chips HTML from server
		if (json.data?.chips_html !== undefined) {
			const filterRoot = container.closest('.hd-filter') || container;
			const chipsContainer = qs('[data-filter-chips]', filterRoot);
			if (chipsContainer) {
				chipsContainer.outerHTML = json.data.chips_html;
			} else if (json.data.chips_html) {
				container.insertAdjacentHTML('afterbegin', json.data.chips_html);
			}
		}

		// Emit event for other modules (e.g., lazy load reinit)
		document.dispatchEvent(
			new CustomEvent('hd:filter:updated', {
				detail: { resultCount: json.data?.result_count ?? 0 },
			}),
		);
	} catch (err) {
		if (err.name === 'AbortError') return;
		console.error('[hd-filter]', err);
	} finally {
		removeClass(qs('.products'), 'is-loading');
		if (containerRequests.get(container) === myController) containerRequests.delete(container);
	}
}

/**
 * Collect all active filter values from the container.
 * @param {HTMLElement} container
 * @returns {Object}
 */
function collectFilters(container) {
	const filters = {};

	// Checkboxes
	qsa('.hd-filter__input:checked', container).forEach((input) => {
		const name = input.name.replace(/^hd_/, '').replace(/\[\]$/, '');
		if (!filters[name]) filters[name] = [];
		filters[name].push(input.value);
	});

	// Swatch buttons (active state)
	qsa('.hd-filter__swatch.is-active', container).forEach((btn) => {
		const name = btn.dataset.filter;
		if (!name) return;
		if (!filters[name]) filters[name] = [];
		filters[name].push(btn.dataset.value);
	});

	// Search input
	qsa('.hd-filter__search-input', container).forEach((input) => {
		const name = input.name.replace(/^hd_/, '');
		if (input.value.trim()) {
			filters[name] = [input.value.trim()];
		}
	});

	// Select dropdowns (e.g., SortFilter)
	qsa('select[name^="hd_"]', container).forEach((select) => {
		const name = select.name.replace(/^hd_/, '');
		if (select.value && select.value !== 'default') {
			filters[name] = [select.value];
		}
	});

	// Range sliders
	qsa('.hd-filter__slider', container).forEach((slider) => {
		const name = slider.dataset.filter;
		if (!name) return;
		const minInput = qs('.hd-filter__range--min', slider);
		const maxInput = qs('.hd-filter__range--max', slider);
		if (minInput && maxInput) {
			const value = getRangeSliderValue(slider);
			if (value && value !== getRangeSliderDefaultValue(slider)) {
				filters[name] = [value];
			}
		}
	});

	return filters;
}

/**
 * Build URL from filter object.
 * @param {Object} filters
 * @returns {string}
 */
function buildUrl(filters) {
	const url = new URL(window.location.href);

	// Remove all hd_ params
	Array.from(url.searchParams.keys())
		.filter((k) => k.startsWith('hd_'))
		.forEach((k) => url.searchParams.delete(k));

	// Add active filters
	Object.entries(filters).forEach(([key, values]) => {
		if (values.length) {
			url.searchParams.set(`hd_${key}`, values.join(','));
		}
	});

	return url.toString();
}

/**
 * Restore filter state from URL params.
 * @param {HTMLElement} container
 */
function restoreFromUrl(container) {
	const url = new URL(window.location.href);

	url.searchParams.forEach((value, key) => {
		if (!key.startsWith('hd_')) return;

		const filterId = key.replace(/^hd_/, '');
		const escapedFilter = CSS.escape(filterId);
		const values = value.split(',');

		values.forEach((v) => {
			// Checkboxes
			const input = qs(`.hd-filter__input[name="hd_${escapedFilter}[]"][value="${CSS.escape(v)}"]`, container);
			if (input) {
				input.checked = true;
				addClass(input.closest('.hd-filter__item'), 'is-active');
			}

			// Swatch buttons
			const btn = qs(`.hd-filter__swatch[data-filter="${escapedFilter}"][data-value="${CSS.escape(v)}"]`, container);
			if (btn) {
				addClass(btn, 'is-active');
			}
		});

		// Search input
		const searchInput = qs(`.hd-filter__search-input[name="hd_${escapedFilter}"]`, container);
		if (searchInput) {
			searchInput.value = value;
		}

		// Select dropdowns (SortFilter)
		const select = qs(`select[name="hd_${escapedFilter}"]`, container);
		if (select) {
			select.value = value;
		}

		// Range sliders
		const slider = qs(`.hd-filter__slider[data-filter="${escapedFilter}"]`, container);
		if (slider && value.includes('-')) {
			const [min, max] = value.split('-', 2);
			const minInput = qs('.hd-filter__range--min', slider);
			const maxInput = qs('.hd-filter__range--max', slider);

			if (minInput && maxInput) {
				minInput.value = min;
				maxInput.value = max;
				updateRangeDisplay(slider);
			}
		}
	});
}

/**
 * Update filter option counts from API response.
 * @param {HTMLElement} container
 * @param {Object} counts { filterId: { value: count } }
 */
function updateCounts(container, counts) {
	Object.entries(counts).forEach(([filterId, valueCounts]) => {
		const group = qs(`[data-filter-group="${CSS.escape(filterId)}"]`, container);
		if (!group) return;

		const adoptive = group.dataset.adoptive || group.closest('.hd-filter__group')?.dataset.adoptive || 'show';

		Object.entries(valueCounts).forEach(([value, count]) => {
			// Update count text
			const item = qs(`.hd-filter__input[value="${CSS.escape(value)}"]`, group)?.closest('.hd-filter__item');
			if (!item) return;

			const countEl = qs('.hd-filter__count', item);
			if (countEl) {
				countEl.textContent = `(${count})`;
			}

			// Adoptive behavior
			if (count === 0) {
				if (adoptive === 'hide') {
					item.style.display = 'none';
				} else if (adoptive === 'disable') {
					addClass(item, 'is-disabled');
					const input = qs('.hd-filter__input', item);
					if (input) input.disabled = true;
				}
			} else {
				item.style.display = '';
				removeClass(item, 'is-disabled');
				const input = qs('.hd-filter__input', item);
				if (input) input.disabled = false;
			}
		});
	});
}

/**
 * Update active filters display (tags).
 * @param {HTMLElement} container
 */
function updateActiveFilters(container) {
	const activeBar = qs('[data-filter-active]', container);
	if (!activeBar) return;

	const filters = collectFilters(container);
	const fragment = document.createDocumentFragment();

	Object.entries(filters).forEach(([filterId, values]) => {
		const group = qs(`[data-filter-group="${CSS.escape(filterId)}"]`, container);
		const groupTitle = group ? qs('.hd-filter__title', group)?.textContent || filterId : filterId;

		values.forEach((value) => {
			// Find display label
			const input = qs(`.hd-filter__input[value="${CSS.escape(value)}"]`, container);
			const inputLabel = input?.closest('.hd-filter__label');
			const label = inputLabel ? qs('.hd-filter__text', inputLabel)?.textContent || value : value;

			const tag = create('span', { class: 'hd-filter__tag' });
			tag.dataset.removeFilter = filterId;
			tag.dataset.removeValue = value;
			tag.append(`${groupTitle}: ${label}`);

			const removeBtn = create('button', { type: 'button', class: 'hd-filter__tag-remove', text: '×' });
			removeBtn.setAttribute('aria-label', 'Remove');

			on(removeBtn, 'click', () => {
				clearFilterValue(container, filterId, value);
				handleFilterChange({ target: container });
			});

			tag.append(removeBtn);
			fragment.append(tag);
		});
	});

	activeBar.replaceChildren(fragment);
}

/**
 * Handle delegated clicks for chip remove + reset (survives AJAX DOM replacement).
 * @param {MouseEvent} e
 */
function handleDelegatedClick(e) {
	// Chip remove
	const chipRemoveBtn = e.target.closest('.hd-filter__chip-remove');
	if (chipRemoveBtn) {
		e.preventDefault();
		const chip = chipRemoveBtn.closest('.hd-filter__chip');
		if (chip) {
			handleChipRemove({ target: chip, preventDefault() {} });
		}
		return;
	}

	// Reset button
	const resetBtn = e.target.closest('[data-filter-reset]');
	if (resetBtn) {
		handleReset(e);
	}
}

/**
 * Handle reset all filters.
 */
function handleReset(e) {
	const container = e?.target?.closest('[data-wc-filter]') || qs('[data-wc-filter]');
	if (!container) return;

	// Uncheck all
	qsa('.hd-filter__input:checked', container).forEach((input) => {
		input.checked = false;
		removeClass(input.closest('.hd-filter__item'), 'is-active');
	});

	// Deactivate all swatches
	removeClass(qsa('.hd-filter__swatch.is-active', container), 'is-active');

	// Clear search inputs
	qsa('.hd-filter__search-input', container).forEach((input) => {
		input.value = '';
	});

	// Reset select dropdowns (SortFilter)
	qsa('select[name^="hd_"]', container).forEach((select) => {
		select.selectedIndex = 0;
	});

	// Reset range sliders
	qsa('.hd-filter__slider', container).forEach((slider) => {
		resetRangeSlider(slider);
	});

	// Trigger filter update (direct call — works in both auto and manual modes)
	handleFilterChange({ target: container });
}

/**
 * Handle chip remove click.
 * @param {MouseEvent} e
 */
function handleChipRemove(e) {
	e.preventDefault();
	const chip = e.target.closest('.hd-filter__chip');
	if (!chip) return;

	const filterId = chip.dataset.filter;
	const value = chip.dataset.value;
	const container = chip.closest('[data-wc-filter]') || qs('[data-wc-filter]');
	if (!container) return;

	clearFilterValue(container, filterId, value);
	handleFilterChange({ target: container });
}

/**
 * Clear a single active filter value across supported control types.
 * @param {HTMLElement} container
 * @param {string} filterId
 * @param {string} value
 */
function clearFilterValue(container, filterId, value) {
	if (!filterId) return;

	const escapedFilter = CSS.escape(filterId);
	const escapedValue = CSS.escape(value || '');

	const input = qs(`.hd-filter__input[name="hd_${escapedFilter}[]"][value="${escapedValue}"]`, container);
	if (input) {
		input.checked = false;
		removeClass(input.closest('.hd-filter__item'), 'is-active');
	}

	removeClass(qs(`.hd-filter__swatch[data-filter="${escapedFilter}"][data-value="${escapedValue}"]`, container), 'is-active');

	const searchInput = qs(`.hd-filter__search-input[name="hd_${escapedFilter}"]`, container);
	if (searchInput && (!value || searchInput.value === value)) {
		searchInput.value = '';
	}

	const select = qs(`select[name="hd_${escapedFilter}"]`, container);
	if (select && (!value || select.value === value)) {
		select.selectedIndex = 0;
	}

	const slider = qs(`.hd-filter__slider[data-filter="${escapedFilter}"]`, container);
	if (slider && (!value || getRangeSliderValue(slider) === value)) {
		resetRangeSlider(slider);
	}
}

/**
 * Get current min-max string from a range slider.
 * @param {HTMLElement} slider
 * @returns {string}
 */
function getRangeSliderValue(slider) {
	const minInput = qs('.hd-filter__range--min', slider);
	const maxInput = qs('.hd-filter__range--max', slider);

	return minInput && maxInput ? `${minInput.value}-${maxInput.value}` : '';
}

/**
 * Get the configured min-max default string from a range slider.
 * @param {HTMLElement} slider
 * @returns {string}
 */
function getRangeSliderDefaultValue(slider) {
	const minInput = qs('.hd-filter__range--min', slider);
	const maxInput = qs('.hd-filter__range--max', slider);
	if (!minInput || !maxInput) return '';

	const min = minInput.getAttribute('min') || slider.dataset.min || minInput.defaultValue || '';
	const max = maxInput.getAttribute('max') || slider.dataset.max || maxInput.defaultValue || '';

	return `${min}-${max}`;
}

/**
 * Reset a range slider to its configured limits.
 * @param {HTMLElement} slider
 */
function resetRangeSlider(slider) {
	const minInput = qs('.hd-filter__range--min', slider);
	const maxInput = qs('.hd-filter__range--max', slider);
	if (!minInput || !maxInput) return;

	const [min, max] = getRangeSliderDefaultValue(slider).split('-', 2);
	minInput.value = min || '';
	maxInput.value = max || '';
	updateRangeDisplay(slider);
}

/**
 * Update visible labels for a range slider.
 * @param {HTMLElement|null} slider
 */
function updateRangeDisplay(slider) {
	if (!slider) return;

	const minInput = qs('.hd-filter__range--min', slider);
	const maxInput = qs('.hd-filter__range--max', slider);
	const minLabel = qs('.hd-filter__slider-min', slider);
	const maxLabel = qs('.hd-filter__slider-max', slider);

	if (minInput && minLabel) {
		minLabel.textContent = formatPriceValue(minInput.value);
	}
	if (maxInput && maxLabel) {
		maxLabel.textContent = formatPriceValue(maxInput.value);
	}
}

/**
 * Format a numeric price value for slider labels.
 * @param {string} value
 * @returns {string}
 */
function formatPriceValue(value) {
	if (!value) return '';

	const number = Number(value);
	return Number.isFinite(number) ? `${new Intl.NumberFormat().format(number)}₫` : value;
}

/**
 * Init manual Apply button for non-auto modes.
 * @param {HTMLElement} container
 */
function initManualApply(container) {
	const actions = qs('.hd-filter__actions', container);
	if (!actions) return;

	const applyBtn = create('button', { type: 'button', class: 'hd-filter__apply', text: 'Áp dụng' });
	on(applyBtn, 'click', () => handleFilterChange({ target: container }));
	actions.prepend(applyBtn);
}

/**
 * Init popover toggles for horizontal layout.
 * @param {HTMLElement} container
 */
function initPopovers(container) {
	const triggers = qsa('.hd-filter__popover-trigger', container);
	const triggerHandlers = triggers.map((trigger) => {
		const handler = (e) => {
			e.stopPropagation();
			togglePopover(trigger, container);
		};

		on(trigger, 'click', handler);
		return handler;
	});

	// Close popover on outside click
	const documentClick = (e) => {
		if (!e.target.closest('.hd-filter__popover-wrap')) {
			closePopovers(container);
		}
	};
	on(document, 'click', documentClick);

	// Close on ESC
	const documentKeydown = (e) => {
		if (e.key === 'Escape') {
			closePopovers(container);
		}
	};
	on(document, 'keydown', documentKeydown);

	return { triggers, triggerHandlers, documentClick, documentKeydown };
}

/**
 * Init more/less toggle for groups.
 * @param {HTMLElement} container
 */
function initMoreLess(container) {
	qsa('[data-more-less]', container).forEach((group) => {
		const threshold = parseInt(group.dataset.moreLess, 10) || 5;
		const items = qsa('.hd-filter__item', group);
		if (items.length <= threshold) return;

		// Wrap overflow items
		const overflow = create('div', { class: 'hd-filter__more-less-hidden' });
		items.forEach((item, i) => {
			if (i >= threshold) overflow.appendChild(item);
		});

		const list = qs('.hd-filter__list, .hd-filter__body', group);
		if (!list) return;
		list.appendChild(overflow);

		// Toggle button
		const hiddenCount = items.length - threshold;
		const toggleBtn = create('button', { type: 'button', class: 'hd-filter__more-less-toggle', text: `Show more (${hiddenCount})` });

		on(toggleBtn, 'click', () => {
			toggleClass(overflow, 'is-expanded');
			const expanded = hasClass(overflow, 'is-expanded');
			toggleBtn.textContent = expanded ? 'Show less' : `Show more (${hiddenCount})`;
		});

		list.appendChild(toggleBtn);
	});
}

/**
 * Init inline term search for filter groups.
 * @param {HTMLElement} container
 */
function initTermSearch(container) {
	qsa('[data-searchable]', container).forEach((input) => {
		on(input, 'input', () => {
			filterTermItems(input);
		});
	});
}

/**
 * Find a filter container even when the root is the container itself.
 * @param {Document|Element} root
 * @returns {HTMLElement|null}
 */
function getFilterContainer(root = document) {
	if (root?.matches?.('[data-wc-filter]')) return root;
	return root ? qs('[data-wc-filter]', root) : null;
}

/**
 * Toggle a swatch/button filter option.
 * @param {HTMLElement|null} btn
 * @returns {boolean}
 */
function toggleSwatch(btn) {
	if (!btn || btn.disabled) return false;

	toggleClass(btn, 'is-active');
	return true;
}

/**
 * Toggle a horizontal popover panel.
 * @param {HTMLElement} trigger
 * @param {HTMLElement} container
 */
function togglePopover(trigger, container) {
	const wrap = trigger.closest('.hd-filter__popover-wrap');
	const panel = wrap ? qs('.hd-filter__popover-panel', wrap) : null;
	if (!panel) return;

	const isOpen = hasClass(panel, 'is-open');

	closePopovers(container);

	if (!isOpen) {
		addClass(panel, 'is-open');
		addClass(trigger, 'is-active');
	}
}

/**
 * Close all open popover panels in a filter container.
 * @param {HTMLElement} container
 */
function closePopovers(container) {
	qsa('.hd-filter__popover-panel.is-open', container).forEach((p) => {
		removeClass(p, 'is-open');
		const openWrap = p.closest('.hd-filter__popover-wrap');
		if (openWrap) {
			removeClass(qs('.hd-filter__popover-trigger', openWrap), 'is-active');
		}
	});
}

/**
 * Filter visible term items for an inline term-search input.
 * @param {HTMLInputElement} input
 */
function filterTermItems(input) {
	const query = input.value.toLowerCase().trim();
	const body = input.closest('.hd-filter__body') || input.parentElement;
	if (!body) return;

	qsa('.hd-filter__item', body).forEach((item) => {
		const text = item.textContent.toLowerCase();
		item.style.display = !query || text.includes(query) ? '' : 'none';
	});
}

/**
 * Destroy filter listeners.
 * @param {HTMLElement} root
 */
function destroyFilter(root) {
	const container = getFilterContainer(root);
	if (!container) return;

	const refs = container._hdFilterCleanup;
	if (refs) {
		if (refs.debouncedChange) {
			off(container, 'change', refs.debouncedChange);
		}
		if (refs.searchInput && refs.debouncedSearch) {
			off(refs.searchInput, 'input', refs.debouncedSearch);
		}
		if (refs.popovers) {
			refs.popovers.triggers.forEach((trigger, i) => {
				off(trigger, 'click', refs.popovers.triggerHandlers.at(i));
			});
			off(document, 'click', refs.popovers.documentClick);
			off(document, 'keydown', refs.popovers.documentKeydown);
		}
	}

	off(container, 'click', handleSwatchClick);
	off(container, 'click', handleDelegatedClick);
	container._hdFilterInited = false;
	delete container._hdFilterCleanup;
}

// ============================================================================
// MOBILE FILTER POPUP
// ============================================================================

/**
 * Initialize mobile filter experience.
 * Hides sidebar, shows floating "Lọc" button → FxModal fullscreen popup.
 *
 * @param {HTMLElement} container
 */
function initMobilePopup(container) {
	if (container._mobileInitDone) return;
	container._mobileInitDone = true;

	// Hide the desktop sidebar
	addClass(container, 'hd-filter--mobile-hidden');

	// Create floating trigger button
	const triggerBtn = create('button', { type: 'button', class: 'hd-filter-mobile-trigger' });
	triggerBtn.append(createFilterIcon(), createTextSpan('Lọc'));
	document.body.appendChild(triggerBtn);

	on(triggerBtn, 'click', () => {
		openMobileFilter(container);
	});
}

/** Singleton modal for mobile filter */
const filterModal = new FxModal();

/**
 * Open mobile filter as FxModal fullscreen popup.
 * @param {HTMLElement} container
 */
function openMobileFilter(container) {
	// Clone filter content for popup
	const clone = container.cloneNode(true);
	removeClass(clone, 'hd-filter--mobile-hidden');
	addClass(clone, 'hd-filter--mobile-popup');

	// Add apply + reset footer
	const footer = create('div', { class: 'hd-filter-mobile__footer' });
	const resetBtn = create('button', { type: 'button', class: 'hd-filter-mobile__reset', text: 'Xóa tất cả' });
	resetBtn.dataset.filterMobileReset = '';

	const applyBtn = create('button', { type: 'button', class: 'hd-filter-mobile__apply', text: 'Áp dụng' });
	applyBtn.dataset.filterMobileApply = '';

	footer.append(resetBtn, applyBtn);
	clone.appendChild(footer);

	filterModal.show(clone, {
		className: 'hd-filter-mobile-modal',
		onOpen: (dialog) => {
			const contentEl = qs('.hd-modal__content', dialog);
			if (!contentEl) return;

			// Sync current filter state to clone
			syncFilterState(container, contentEl);
			initMobileClone(contentEl);

			// Bind apply button
			on(qs('[data-filter-mobile-apply]', contentEl), 'click', () => {
				syncFilterState(contentEl, container);
				handleFilterChange({ target: container });
				filterModal.close();
			});

			// Bind reset button
			on(qs('[data-filter-mobile-reset]', contentEl), 'click', () => {
				// Reset original container
				handleReset({ target: container });
				filterModal.close();
			});
		},
	});
}

/**
 * Initialize interaction on cloned mobile filter content.
 * @param {HTMLElement} root
 */
function initMobileClone(root) {
	const popupFilter = getFilterContainer(root);
	if (!popupFilter || popupFilter._hdMobileCloneInited) return;
	popupFilter._hdMobileCloneInited = true;

	on(popupFilter, 'click', (e) => {
		const swatch = e.target.closest('.hd-filter__swatch');
		if (swatch && popupFilter.contains(swatch)) {
			toggleSwatch(swatch);
			return;
		}

		const trigger = e.target.closest('.hd-filter__popover-trigger');
		if (trigger && popupFilter.contains(trigger)) {
			e.stopPropagation();
			togglePopover(trigger, popupFilter);
		}
	});

	on(popupFilter, 'input', (e) => {
		if (e.target.matches('[data-searchable]')) {
			filterTermItems(e.target);
		}
	});
}

/**
 * Sync filter state between two containers (original ↔ clone).
 * @param {HTMLElement} source
 * @param {HTMLElement} target
 */
function syncFilterState(source, target) {
	const sourceFilter = getFilterContainer(source);
	const targetFilter = getFilterContainer(target);
	if (!sourceFilter || !targetFilter) return;

	// Sync checkboxes
	qsa('.hd-filter__input', sourceFilter).forEach((input) => {
		const match = qs(`.hd-filter__input[name="${CSS.escape(input.name)}"][value="${CSS.escape(input.value)}"]`, targetFilter);
		if (match) {
			match.checked = input.checked;
			toggleClass(match.closest('.hd-filter__item'), 'is-active', input.checked);
		}
	});

	// Sync swatch buttons
	qsa('.hd-filter__swatch', sourceFilter).forEach((btn) => {
		const filter = btn.dataset.filter;
		const value = btn.dataset.value;
		if (!filter || value === undefined) return;

		const match = qs(`.hd-filter__swatch[data-filter="${CSS.escape(filter)}"][data-value="${CSS.escape(value)}"]`, targetFilter);
		if (match) {
			toggleClass(match, 'is-active', hasClass(btn, 'is-active'));
		}
	});

	// Sync search inputs
	qsa('.hd-filter__search-input', sourceFilter).forEach((input) => {
		const match = qs(`.hd-filter__search-input[name="${CSS.escape(input.name)}"]`, targetFilter);
		if (match) {
			match.value = input.value;
		}
	});

	// Sync select dropdowns
	qsa('select[name^="hd_"]', sourceFilter).forEach((select) => {
		const match = qs(`select[name="${CSS.escape(select.name)}"]`, targetFilter);
		if (match) {
			match.value = select.value;
		}
	});

	// Sync range sliders
	qsa('.hd-filter__range', sourceFilter).forEach((input) => {
		const match = qs(`.hd-filter__range[name="${CSS.escape(input.name)}"]`, targetFilter);
		if (match) {
			match.value = input.value;
			updateRangeDisplay(match.closest('.hd-filter__slider'));
		}
	});
}

/**
 * Create the mobile filter icon without inline HTML.
 * @returns {SVGElement}
 */
function createFilterIcon() {
	const ns = 'http://www.w3.org/2000/svg';
	const svg = document.createElementNS(ns, 'svg');
	svg.setAttribute('width', '16');
	svg.setAttribute('height', '16');
	svg.setAttribute('viewBox', '0 0 24 24');
	svg.setAttribute('fill', 'none');
	svg.setAttribute('stroke', 'currentColor');
	svg.setAttribute('stroke-width', '2');
	svg.setAttribute('stroke-linecap', 'round');
	svg.setAttribute('stroke-linejoin', 'round');

	const polygon = document.createElementNS(ns, 'polygon');
	polygon.setAttribute('points', '22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3');
	svg.appendChild(polygon);

	return svg;
}

/**
 * Create a span with text content.
 * @param {string} text
 * @returns {HTMLSpanElement}
 */
function createTextSpan(text) {
	return create('span', { text });
}

export default {
	initAll(root = document) {
		initFilter(root);
	},

	destroyAll(root = document) {
		destroyFilter(root);
	},
};
