// grid/fx-hybrid.js
// Hybrid Layout: Combines Masonry's freedom with Justified's clean edges
// Creates an organic, collage-like feel while maintaining aligned borders

import './fx-hybrid.scss';

import { $ as qs, $$ as qsa, on, off, addClass, removeClass } from '../../dom.js';
import { createWeakStore } from '../../weak.js';
import Events from '../../events.js';
import { debounce, parseJSON, resolveResponsive } from '../../helpers.js';

const SELECTOR = '[data-fx-hybrid]';
const instanceStore = createWeakStore();
const observerStore = createWeakStore();

// Seeded random for consistent layouts

// Pre-compute sin values for common seeds (micro-optimization)
const SIN_CACHE_SIZE = 256;
const sinCache = new Float32Array(SIN_CACHE_SIZE);
for (let i = 0; i < SIN_CACHE_SIZE; i++) {
	Reflect.set(sinCache, i, Math.sin(i) * 10000);
}

const fastSeededRandom = (seed) => {
	const idx = (seed | 0) & 0xff;
	const x = Reflect.get(sinCache, idx) + seed * 0.001;
	return x - Math.floor(x);
};

/**
 * Hybrid Layout Algorithm
 * Combines row-based justified layout with masonry-style variation
 *
 * Key features:
 * - Clean left/right/top edges (like Justified)
 * - Variable row heights with controlled randomness
 * - Occasional "feature" items that span more space
 * - Staggered row offsets for organic feel
 * - Natural bottom edge (no ragged masonry bottom)
 */
class HybridLayout {
	constructor(containerWidth, opts = {}) {
		this.containerWidth = containerWidth;
		this.baseHeight = opts.baseHeight || 200;
		this.gutter = opts.gutter || 12;
		this.heightVariation = opts.heightVariation || 0.4; // 40% variation allowed
		this.randomSeed = opts.randomSeed || Date.now();
		this.featureChance = opts.featureChance || 0.15; // 15% chance of featured item
		this.stagger = opts.stagger || false; // Stagger alternate rows
		this.staggerAmount = opts.staggerAmount || 0.02; // 2% row offset
	}

	calculate(items) {
		const len = items.length;
		if (!len) return { boxes: [], containerHeight: 0 };

		const boxes = new Array(len); // Pre-allocate
		let boxCount = 0;
		let top = 0;
		let rowIndex = 0;

		// Use typed array for featured flags (faster than Set for iteration)
		const featured = new Uint8Array(len);
		const seed = this.randomSeed;
		const featureChance = this.featureChance;
		for (let i = 0; i < len; i++) {
			if (fastSeededRandom(seed + i * 7) < featureChance) {
				Reflect.set(featured, i, 1);
			}
		}

		// Pre-calculate constants
		const baseHeight = this.baseHeight;
		const containerWidth = this.containerWidth;
		const gutter = this.gutter;
		const heightVariation = this.heightVariation;
		const minHeight = baseHeight * (1 - heightVariation);
		const maxHeight = baseHeight * (1 + heightVariation);
		const stagger = this.stagger;
		const staggerAmount = this.staggerAmount;

		// Row tracking - avoid creating arrays when possible
		let rowStartIdx = 0;
		let rowItemCount = 0;
		let currentRowAR = 0;

		// Pre-calculate aspect ratios (avoid repeated division)
		const aspectRatios = new Float32Array(len);
		for (let i = 0; i < len; i++) {
			let ar = items.at(i).width / items.at(i).height;
			if (featured.at(i)) ar *= 1.3;
			Reflect.set(aspectRatios, i, ar);
		}

		for (let i = 0; i < len; i++) {
			const ar = aspectRatios.at(i);
			rowItemCount++;
			currentRowAR += ar;

			const rowWidth = containerWidth - gutter * (rowItemCount - 1);
			const rowHeight = rowWidth / currentRowAR;

			const shouldFinalize = (rowHeight <= maxHeight && rowHeight >= minHeight) || (rowHeight < minHeight && rowItemCount > 1);

			if (shouldFinalize) {
				let endIdx = i;
				let finalAR = currentRowAR;
				let finalCount = rowItemCount;

				if (rowHeight < minHeight && rowItemCount > 1) {
					// Exclude last item from this row
					endIdx = i - 1;
					finalAR -= ar;
					finalCount--;
					i--; // Re-process this item
				}

				const finalRowWidth = containerWidth - gutter * (finalCount - 1);
				let finalRowHeight = finalRowWidth / finalAR;

				// Clamp height
				if (finalRowHeight < minHeight) finalRowHeight = minHeight;
				else if (finalRowHeight > maxHeight) finalRowHeight = maxHeight;

				// Row variation
				const rowVar = 1 + (fastSeededRandom(seed + rowIndex * 13) - 0.5) * 0.1;
				const adjustedHeight = finalRowHeight * rowVar;

				// Stagger offset
				const staggerOffset = stagger && rowIndex & 1 ? containerWidth * staggerAmount : 0;

				// Add boxes for this row
				boxCount = this._addRowToBoxesOptimized(
					boxes,
					boxCount,
					items,
					aspectRatios,
					featured,
					rowStartIdx,
					endIdx,
					adjustedHeight,
					top,
					staggerOffset,
					rowIndex,
					gutter,
					containerWidth,
					seed,
				);

				top += adjustedHeight + gutter;
				rowStartIdx = endIdx + 1;
				rowItemCount = 0;
				currentRowAR = 0;
				rowIndex++;
			}
		}

		// Handle last row
		if (rowItemCount > 0) {
			const rowWidth = containerWidth - gutter * (rowItemCount - 1);
			let rowHeight = rowWidth / currentRowAR;

			if (rowHeight > maxHeight * 1.3) {
				rowHeight = baseHeight;
			} else if (rowHeight > maxHeight) {
				rowHeight = maxHeight;
			}

			boxCount = this._addRowToBoxesOptimized(boxes, boxCount, items, aspectRatios, featured, rowStartIdx, len - 1, rowHeight, top, 0, rowIndex, gutter, containerWidth, seed);
			top += rowHeight + gutter;
		}

		// Trim boxes array
		boxes.length = boxCount;

		return {
			boxes,
			containerHeight: top > gutter ? top - gutter : 0,
			rowCount: rowIndex + 1,
		};
	}

	// Optimized version that works with indices instead of creating row arrays
	_addRowToBoxesOptimized(boxes, boxCount, items, aspectRatios, featured, startIdx, endIdx, height, top, staggerOffset, rowIndex, gutter, containerWidth, seed) {
		const rowLen = endIdx - startIdx + 1;

		// Calculate total AR for this row
		let totalAR = 0;
		for (let i = startIdx; i <= endIdx; i++) {
			totalAR += aspectRatios.at(i);
		}

		const rowWidth = containerWidth - gutter * (rowLen - 1);
		const scale = rowWidth / totalAR;
		let left = staggerOffset;

		for (let i = startIdx; i <= endIdx; i++) {
			const localIdx = i - startIdx;
			const item = items.at(i);
			const isFeatured = featured.at(i) === 1;
			let width = aspectRatios.at(i) * scale;

			// Micro-variation for middle items only
			if (localIdx > 0 && localIdx < rowLen - 1) {
				const microVar = 1 + (fastSeededRandom(seed + i * 17 + rowIndex) - 0.5) * 0.04;
				width *= microVar;
			}

			// Vertical nudge for middle items
			let itemTop = top;
			if (localIdx > 0 && localIdx < rowLen - 1) {
				itemTop += (fastSeededRandom(seed + i * 23) - 0.5) * (gutter * 0.3);
			}

			Reflect.set(boxes, boxCount++, {
				left: left > 0 ? left : 0,
				top: itemTop,
				width,
				height: isFeatured ? height * 1.05 : height,
				element: item.element,
				originalIndex: i,
				featured: isFeatured,
			});

			left += width + gutter;
		}

		// Fix right edge alignment
		if (boxCount > 0 && rowLen > 0) {
			const lastBox = boxes.at(boxCount - 1);
			const overflow = lastBox.left + lastBox.width - containerWidth;
			if (overflow > 1 || overflow < -1) {
				lastBox.width -= overflow;
			}
		}

		return boxCount;
	}


}

/**
 * FxHybrid - Organic hybrid grid layout
 */
class FxHybrid {
	constructor(el, opts = {}) {
		this.el = el;
		this.opts = {
			gutter: { default: 8, md: 12, lg: 16 },
			baseHeight: { default: 180, md: 220, lg: 260 },
			heightVariation: 0.35,
			featureChance: 0.12,
			stagger: false,
			staggerAmount: 0.015,
			randomSeed: null, // null = use Date.now() for different layouts each time
			itemSelector: '.hybrid-item',
			animate: true,
			resizeDebounce: 100,
			...opts,
		};
		this.items = [];
		this.boxes = [];
		this._resizeHandler = null;
		this._seed = this.opts.randomSeed || Date.now();
		this._init();
	}

	_init() {
		addClass(this.el, 'fx-hybrid');
		this.el.style.position = 'relative';
		this.items = qsa(this.opts.itemSelector, this.el);

		this._waitForImages().then(() => {
			this.layout();
			addClass(this.el, 'fx-hybrid--ready');
		});

		this._resizeHandler = debounce(() => {
			this.layout();
			Events.emit('fx:hybrid:resize', { el: this.el, instance: this });
		}, this.opts.resizeDebounce);

		on(window, 'resize', this._resizeHandler, { passive: true });
		Events.emit('fx:hybrid:init', { el: this.el, instance: this });
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
		const img = qs('img', item);
		if (img && img.naturalWidth && img.naturalHeight) {
			return { width: img.naturalWidth, height: img.naturalHeight, element: item };
		}

		const w = +item.dataset.width || item.offsetWidth || 1;
		const h = +item.dataset.height || item.offsetHeight || 1;
		return { width: w, height: h, element: item };
	}

	getGutter() {
		return resolveResponsive(this.opts.gutter, 12);
	}

	getBaseHeight() {
		return resolveResponsive(this.opts.baseHeight, 200);
	}

	layout() {
		const containerWidth = this.el.offsetWidth;
		const gutter = this.getGutter();
		const baseHeight = this.getBaseHeight();

		const itemData = this.items.map((item) => this._getItemDimensions(item));

		const layout = new HybridLayout(containerWidth, {
			baseHeight,
			gutter,
			heightVariation: this.opts.heightVariation,
			featureChance: this.opts.featureChance,
			stagger: this.opts.stagger,
			staggerAmount: this.opts.staggerAmount,
			randomSeed: this._seed,
		});

		const { boxes, containerHeight, rowCount } = layout.calculate(itemData);
		this.boxes = boxes;

		// Apply positions with slight transform for organic feel
		boxes.forEach((box, i) => {
			const style = box.element.style;
			style.position = 'absolute';
			style.left = `${box.left}px`;
			style.top = `${box.top}px`;
			style.width = `${box.width}px`;
			style.height = `${box.height}px`;

			// Add featured class
			if (box.featured) {
				addClass(box.element, 'hybrid-item--featured');
			} else {
				removeClass(box.element, 'hybrid-item--featured');
			}
		});

		this.el.style.height = `${containerHeight}px`;

		Events.emit('fx:hybrid:layout', {
			el: this.el,
			instance: this,
			boxes,
			containerHeight,
			rowCount,
		});
	}

	// Shuffle the layout with a new random seed
	shuffle() {
		this._seed = Date.now();
		this.layout();
		Events.emit('fx:hybrid:shuffle', { el: this.el, instance: this });
	}

	appendItems(newItems, animate = true) {
		const shouldAnimate = animate && this.opts.animate;

		newItems.forEach((item) => {
			if (shouldAnimate) {
				Object.assign(item.style, { opacity: '0', transform: 'scale(0.9) translateY(20px)' });
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
							transition: 'opacity 0.5s ease-out, transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)',
							opacity: '1',
							transform: 'scale(1) translateY(0)',
						});
						setTimeout(() => Object.assign(item.style, { transition: '', opacity: '', transform: '' }), 500);
					}, i * 60),
				);
			} else {
				newItems.forEach((item) => (item.style.visibility = ''));
			}

			Events.emit('fx:hybrid:append', {
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
		Events.emit('fx:hybrid:remove', { el: this.el, instance: this, removedCount: items.length });
	}

	refresh() {
		this.items = qsa(this.opts.itemSelector, this.el);
		this._waitForImages().then(() => {
			this.layout();
			Events.emit('fx:hybrid:refresh', { el: this.el, instance: this });
		});
	}

	update(opts) {
		Object.assign(this.opts, opts);
		if (opts.randomSeed !== undefined) {
			this._seed = opts.randomSeed || Date.now();
		}
		this.layout();
		Events.emit('fx:hybrid:update', { el: this.el, instance: this });
	}

	destroy() {
		this._resizeHandler && off(window, 'resize', this._resizeHandler);
		removeClass(this.el, 'fx-hybrid fx-hybrid--ready');
		Object.assign(this.el.style, { position: '', height: '' });
		this.items.forEach((item) => {
			Object.assign(item.style, { position: '', width: '', height: '', left: '', top: '' });
			removeClass(item, 'hybrid-item--featured');
		});
		Events.emit('fx:hybrid:destroy', { el: this.el });
	}

	getState() {
		return {
			gutter: this.getGutter(),
			baseHeight: this.getBaseHeight(),
			itemCount: this.items.length,
			height: this.el.offsetHeight,
			seed: this._seed,
			boxes: this.boxes,
		};
	}
}

const parseOptions = (el) => {
	const d = el.dataset;

	let gutter = { default: 8, md: 12, lg: 16 };
	if (d.fxHybridGutter) {
		const parsed = parseJSON(d.fxHybridGutter, null);
		if (typeof parsed === 'number') gutter = { default: parsed, md: parsed, lg: parsed };
		else if (parsed) gutter = { ...gutter, ...parsed };
	}

	let baseHeight = { default: 180, md: 220, lg: 260 };
	if (d.fxHybridHeight) {
		const parsed = parseJSON(d.fxHybridHeight, null);
		if (typeof parsed === 'number') baseHeight = { default: parsed, md: parsed, lg: parsed };
		else if (parsed) baseHeight = { ...baseHeight, ...parsed };
	}

	return {
		gutter,
		baseHeight,
		heightVariation: +d.fxHybridVariation || 0.35,
		featureChance: +d.fxHybridFeature || 0.12,
		stagger: d.fxHybridStagger === 'true',
		staggerAmount: +d.fxHybridStaggerAmount || 0.015,
		randomSeed: d.fxHybridSeed ? +d.fxHybridSeed : null,
		itemSelector: d.fxHybridItem || '.hybrid-item',
		animate: d.fxHybridAnimate !== 'false',
		resizeDebounce: +d.fxHybridDebounce || 100,
	};
};

const initHybrid = (el) => {
	if (!el || instanceStore.has(el)) return instanceStore.get(el) || null;
	const instance = new FxHybrid(el, parseOptions(el));
	instanceStore.set(el, instance);
	return instance;
};

const FxHybridAPI = {
	initAll(root = document) {
		qsa(SELECTOR, root).forEach((el) => {
			if (instanceStore.has(el)) return;

			if ('IntersectionObserver' in window) {
				const obs = new IntersectionObserver(
					([e], o) => {
						if (e.isIntersecting) {
							initHybrid(e.target);
							o.unobserve(e.target);
							observerStore.delete(e.target);
						}
					},
					{ rootMargin: '100px' },
				);
				obs.observe(el);
				observerStore.set(el, obs);
			} else {
				initHybrid(el);
			}
		});
	},

	destroyAll(root = document) {
		qsa(SELECTOR, root).forEach((el) => {
			observerStore.cleanup(el, (o) => o.disconnect());
			instanceStore.cleanup(el, (i) => i.destroy());
		});
		Events.emit('fx:hybrid:destroyAll');
	},

	init: initHybrid,
	getInstance: (el) => instanceStore.get(el) || null,
	FxHybrid,
	HybridLayout,
};

export default FxHybridAPI;
