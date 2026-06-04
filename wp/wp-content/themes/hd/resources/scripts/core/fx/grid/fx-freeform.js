// grid/fx-freeform.js
// Free-form grid layout with justified edges (no ragged edges)
import './fx-freeform.scss';

import { $ as qs, $$ as qsa, on, off, addClass, removeClass } from '../../dom.js';
import { createWeakStore } from '../../weak.js';
import Events from '../../events.js';
import { debounce, parseJSON, resolveResponsive } from '../../helpers.js';

const SELECTOR = '[data-fx-freeform]';
const instanceStore = createWeakStore();
const observerStore = createWeakStore();

const parseOptions = (el) => {
	const d = el.dataset;

	// Gutter: number hoặc {default, md, lg}
	let gutter = { default: 8, md: 12, lg: 16 };
	if (d.fxFreeformGutter) {
		const parsed = parseJSON(d.fxFreeformGutter, null);
		if (typeof parsed === 'number') gutter = { default: parsed, md: parsed, lg: parsed };
		else if (parsed) gutter = { ...gutter, ...parsed };
		else {
			const val = +d.fxFreeformGutter;
			if (!isNaN(val)) gutter = { default: val, md: val, lg: val };
		}
	}

	// Row height target
	const targetHeight = d.fxFreeformHeight ? parseJSON(d.fxFreeformHeight, { default: 200, md: 250, lg: 300 }) : { default: 200, md: 250, lg: 300 };

	return {
		gutter,
		targetHeight, // Target row height
		tolerance: +d.fxFreeformTolerance || 0.25, // Allow 25% variation in row height
		maxRows: +d.fxFreeformMaxRows || null, // Optional: limit number of rows
		lastRowBehavior: d.fxFreeformLastRow || 'natural', // 'natural', 'justify', 'hide', 'left'
		itemSelector: d.fxFreeformItem || '.freeform-item',
		animate: d.fxFreeformAnimate !== 'false',
		resizeDebounce: +d.fxFreeformDebounce || 100,
	};
};

/**
 * Justified Layout Algorithm
 * Distributes items in rows with consistent edges
 */
class JustifiedLayout {
	constructor(containerWidth, opts = {}) {
		this.containerWidth = containerWidth;
		this.targetHeight = opts.targetHeight || 250;
		this.tolerance = opts.tolerance || 0.25;
		this.gutter = opts.gutter || 12;
		this.lastRowBehavior = opts.lastRowBehavior || 'natural';
	}

	/**
	 * Calculate layout for items
	 * @param {Array} items - Array of {width, height, element}
	 * @returns {Object} - {boxes: [{left, top, width, height, element}], containerHeight}
	 */
	calculate(items) {
		if (!items.length) return { boxes: [], containerHeight: 0 };

		const rows = [];
		let currentRow = [];
		let currentRowAspectRatio = 0;

		// Target aspect ratio for a full row
		const targetRowWidth = this.containerWidth;
		const minHeight = this.targetHeight * (1 - this.tolerance);
		const maxHeight = this.targetHeight * (1 + this.tolerance);

		items.forEach((item, index) => {
			const aspectRatio = item.width / item.height;
			currentRow.push({ ...item, aspectRatio, index });
			currentRowAspectRatio += aspectRatio;

			// Calculate what height this row would be if we justify it
			const rowWidth = targetRowWidth - this.gutter * (currentRow.length - 1);
			const rowHeight = rowWidth / currentRowAspectRatio;

			// If row height is within tolerance, finalize this row
			if (rowHeight <= maxHeight && rowHeight >= minHeight) {
				rows.push({
					items: [...currentRow],
					height: rowHeight,
					justified: true,
				});
				currentRow = [];
				currentRowAspectRatio = 0;
			} else if (rowHeight < minHeight) {
				// Row is too short, need to break at previous item
				if (currentRow.length > 1) {
					// Move last item to new row
					const lastItem = currentRow.pop();
					const prevAR = currentRowAspectRatio - lastItem.aspectRatio;
					const prevRowWidth = targetRowWidth - this.gutter * (currentRow.length - 1);
					const prevRowHeight = prevRowWidth / prevAR;

					rows.push({
						items: [...currentRow],
						height: Math.max(minHeight, Math.min(maxHeight, prevRowHeight)),
						justified: true,
					});

					currentRow = [lastItem];
					currentRowAspectRatio = lastItem.aspectRatio;
				}
			}
		});

		// Handle last row
		if (currentRow.length > 0) {
			const rowWidth = targetRowWidth - this.gutter * (currentRow.length - 1);
			const rowHeight = rowWidth / currentRowAspectRatio;

			switch (this.lastRowBehavior) {
				case 'justify':
					// Force justify (may stretch images)
					rows.push({
						items: currentRow,
						height: Math.min(maxHeight, rowHeight),
						justified: true,
					});
					break;

				case 'hide':
					// Hide last incomplete row
					break;

				case 'left':
					// Left align with target height
					rows.push({
						items: currentRow,
						height: this.targetHeight,
						justified: false,
					});
					break;

				case 'natural':
				default:
					// Natural: if row would be too tall, use target height
					if (rowHeight > maxHeight * 1.5) {
						rows.push({
							items: currentRow,
							height: this.targetHeight,
							justified: false,
						});
					} else {
						rows.push({
							items: currentRow,
							height: Math.min(maxHeight, rowHeight),
							justified: true,
						});
					}
					break;
			}
		}

		// Convert rows to positioned boxes
		const boxes = [];
		let top = 0;

		rows.forEach((row) => {
			let left = 0;
			const { height, items: rowItems, justified } = row;

			rowItems.forEach((item) => {
				const itemWidth = justified ? height * item.aspectRatio : this.targetHeight * item.aspectRatio;

				boxes.push({
					left,
					top,
					width: itemWidth,
					height: justified ? height : this.targetHeight,
					element: item.element,
					originalIndex: item.index,
				});

				left += itemWidth + this.gutter;
			});

			top += height + this.gutter;
		});

		// Remove last gutter from height
		const containerHeight = top - this.gutter;

		return { boxes, containerHeight: Math.max(0, containerHeight), rows: rows.length };
	}
}

/**
 * FxFreeform - Free-form justified grid layout
 */
class FxFreeform {
	constructor(el, opts = {}) {
		this.el = el;
		this.opts = {
			gutter: { default: 8, md: 12, lg: 16 },
			targetHeight: { default: 200, md: 250, lg: 300 },
			tolerance: 0.25,
			maxRows: null,
			lastRowBehavior: 'natural',
			itemSelector: '.freeform-item',
			animate: true,
			resizeDebounce: 100,
			...opts,
		};
		this.items = [];
		this.boxes = [];
		this._resizeHandler = null;
		this._pendingImages = 0;
		this._init();
	}

	_init() {
		addClass(this.el, 'fx-freeform');
		this.el.style.position = 'relative';
		this.items = qsa(this.opts.itemSelector, this.el);

		// Wait for images to load before initial layout
		this._waitForImages().then(() => {
			this.layout();
			addClass(this.el, 'fx-freeform--ready');
		});

		this._resizeHandler = debounce(() => {
			this.layout();
			Events.emit('fx:freeform:resize', { el: this.el, instance: this });
		}, this.opts.resizeDebounce);

		on(window, 'resize', this._resizeHandler, { passive: true });
		Events.emit('fx:freeform:init', { el: this.el, instance: this });
	}

	_waitForImages(items = this.items) {
		const images = items.flatMap((item) => qsa('img', item));
		const unloaded = images.filter((img) => !img.complete || !img.naturalWidth);

		if (!unloaded.length) return Promise.resolve();

		return Promise.all(
			unloaded.map(
				(img) =>
					new Promise((resolve) => {
						on(img, 'load', resolve, { once: true });
						on(img, 'error', resolve, { once: true });
					}),
			),
		);
	}

	_getItemDimensions(item) {
		// Try to get dimensions from images
		const img = qs('img', item);
		if (img && img.naturalWidth && img.naturalHeight) {
			return { width: img.naturalWidth, height: img.naturalHeight, element: item };
		}

		// Try data attributes
		const w = +item.dataset.width || item.offsetWidth || 1;
		const h = +item.dataset.height || item.offsetHeight || 1;
		return { width: w, height: h, element: item };
	}

	getGutter() {
		return resolveResponsive(this.opts.gutter, 12);
	}

	getTargetHeight() {
		return resolveResponsive(this.opts.targetHeight, 250);
	}

	layout() {
		const containerWidth = this.el.offsetWidth;
		const gutter = this.getGutter();
		const targetHeight = this.getTargetHeight();

		const itemData = this.items.map((item) => this._getItemDimensions(item));

		const layout = new JustifiedLayout(containerWidth, {
			targetHeight,
			gutter,
			tolerance: this.opts.tolerance,
			lastRowBehavior: this.opts.lastRowBehavior,
		});

		const { boxes, containerHeight, rows } = layout.calculate(itemData);
		this.boxes = boxes;

		// Apply positions
		boxes.forEach((box) => {
			Object.assign(box.element.style, {
				position: 'absolute',
				left: `${box.left}px`,
				top: `${box.top}px`,
				width: `${box.width}px`,
				height: `${box.height}px`,
			});
		});

		this.el.style.height = `${containerHeight}px`;

		Events.emit('fx:freeform:layout', {
			el: this.el,
			instance: this,
			boxes,
			containerHeight,
			rows,
		});
	}

	appendItems(newItems, animate = true) {
		const shouldAnimate = animate && this.opts.animate;

		newItems.forEach((item) => {
			if (shouldAnimate) {
				Object.assign(item.style, { opacity: '0', transform: 'scale(0.9)' });
			}
			item.style.visibility = 'hidden';
			this.el.appendChild(item);
			this.items.push(item);
		});

		this._waitForImages(newItems).then(() => {
			this.layout();

			if (shouldAnimate) {
				newItems.forEach((item, i) =>
					setTimeout(() => {
						item.style.visibility = '';
						Object.assign(item.style, {
							transition: 'opacity 0.4s ease-out, transform 0.4s ease-out',
							opacity: '1',
							transform: 'scale(1)',
						});
						setTimeout(() => Object.assign(item.style, { transition: '', opacity: '', transform: '' }), 400);
					}, i * 50),
				);
			} else {
				newItems.forEach((item) => (item.style.visibility = ''));
			}

			Events.emit('fx:freeform:append', {
				el: this.el,
				instance: this,
				items: newItems,
				totalItems: this.items.length,
			});
		});

		return newItems;
	}

	removeItems(items, relayout = true) {
		items.forEach((item) => {
			const idx = this.items.indexOf(item);
			if (idx > -1) {
				this.items.splice(idx, 1);
				item.remove();
			}
		});
		relayout && this.layout();
		Events.emit('fx:freeform:remove', { el: this.el, instance: this, removedCount: items.length });
	}

	refresh() {
		this.items = qsa(this.opts.itemSelector, this.el);
		this._waitForImages().then(() => {
			this.layout();
			Events.emit('fx:freeform:refresh', { el: this.el, instance: this });
		});
	}

	update(opts) {
		Object.assign(this.opts, opts);
		this.layout();
		Events.emit('fx:freeform:update', { el: this.el, instance: this });
	}

	destroy() {
		this._resizeHandler && off(window, 'resize', this._resizeHandler);
		removeClass(this.el, 'fx-freeform fx-freeform--ready');
		Object.assign(this.el.style, { position: '', height: '' });
		this.items.forEach((item) => Object.assign(item.style, { position: '', width: '', height: '', left: '', top: '' }));
		Events.emit('fx:freeform:destroy', { el: this.el });
	}

	getState() {
		return {
			gutter: this.getGutter(),
			targetHeight: this.getTargetHeight(),
			itemCount: this.items.length,
			height: this.el.offsetHeight,
			boxes: this.boxes,
		};
	}
}

const initFreeform = (el) => {
	if (!el || instanceStore.has(el)) return instanceStore.get(el) || null;
	const instance = new FxFreeform(el, parseOptions(el));
	instanceStore.set(el, instance);
	return instance;
};

const FxFreeformAPI = {
	initAll(root = document) {
		qsa(SELECTOR, root).forEach((el) => {
			if (instanceStore.has(el)) return;

			if ('IntersectionObserver' in window) {
				const obs = new IntersectionObserver(
					([e], o) => {
						if (e.isIntersecting) {
							initFreeform(e.target);
							o.unobserve(e.target);
							observerStore.delete(e.target);
						}
					},
					{ rootMargin: '100px' },
				);
				obs.observe(el);
				observerStore.set(el, obs);
			} else {
				initFreeform(el);
			}
		});
	},

	destroyAll(root = document) {
		qsa(SELECTOR, root).forEach((el) => {
			observerStore.cleanup(el, (o) => o.disconnect());
			instanceStore.cleanup(el, (i) => i.destroy());
		});
		Events.emit('fx:freeform:destroyAll');
	},

	init: initFreeform,
	getInstance: (el) => instanceStore.get(el) || null,
	FxFreeform,
	JustifiedLayout,
};

export default FxFreeformAPI;
