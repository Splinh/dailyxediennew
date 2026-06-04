// grid/fx-masonry.helper.js
import { $$ as qsa, on } from '../../dom.js';
import Events from '../../events.js';

/**
 * Infinite Scroll helper cho FxMasonry
 * @param {Object} masonry - FxMasonry instance
 * @param {Object} options
 */
export const createInfiniteScroll = (masonry, options = {}) => {
	const opts = {
		rootMargin: '200px',
		loadMore: async () => [],
		hasMore: () => true,
		onLoadStart: () => {},
		onLoadEnd: () => {},
		onError: (e) => console.error('[FxMasonry]', e),
		onComplete: () => {},
		...options,
	};

	let loading = false,
		hasMore = true,
		observer = null,
		sentinel = null;

	const createSentinel = () => {
		sentinel = Object.assign(document.createElement('div'), {
			className: 'fx-masonry-sentinel',
			ariaHidden: 'true',
		});
		sentinel.style.cssText = 'height:1px;width:100%;pointer-events:none';
		masonry.el.parentElement?.appendChild(sentinel);
	};

	const complete = () => {
		hasMore = false;
		destroy();
		opts.onComplete();
		Events.emit('fx:masonry:loadComplete', { instance: masonry });
	};

	const loadMore = async () => {
		if (loading || !hasMore) return;
		loading = true;
		opts.onLoadStart();
		Events.emit('fx:masonry:loadStart', { instance: masonry });

		try {
			const items = await opts.loadMore();
			items?.length && masonry.appendItems(items, true);
			hasMore = opts.hasMore();
			!hasMore && complete();
		} catch (e) {
			opts.onError(e);
			Events.emit('fx:masonry:loadError', { instance: masonry, error: e });
		} finally {
			loading = false;
			opts.onLoadEnd();
			Events.emit('fx:masonry:loadEnd', { instance: masonry });
		}
	};

	const start = () => {
		if (!('IntersectionObserver' in window)) return;
		sentinel || createSentinel();
		observer = new IntersectionObserver(([e]) => e.isIntersecting && hasMore && !loading && loadMore(), { rootMargin: opts.rootMargin });
		observer.observe(sentinel);
		Events.emit('fx:masonry:infiniteScrollStart', { instance: masonry });
	};

	const pause = () => observer?.disconnect();
	const resume = () => sentinel && hasMore && observer?.observe(sentinel);
	const destroy = () => {
		observer?.disconnect();
		observer = null;
		sentinel?.remove();
		sentinel = null;
	};
	const reset = () => ((hasMore = true), (loading = false));

	return {
		start,
		pause,
		resume,
		destroy,
		reset,
		loadMore,
		complete,
		get loading() {
			return loading;
		},
		get hasMore() {
			return hasMore;
		},
	};
};

/**
 * Image watcher - relayout khi images load xong
 */
export const createImageWatcher = (masonry, { debounceMs = 100 } = {}) => {
	let tid = null,
		watching = false;

	const relayout = () => {
		tid && clearTimeout(tid);
		tid = setTimeout(() => masonry.layout(), debounceMs);
	};

	const watch = (items = masonry.items) => {
		watching = true;
		items.forEach((item) =>
			qsa('img', item).forEach((img) => {
				if (img.complete) return;
				on(img, 'load', relayout, { once: true });
				on(img, 'error', relayout, { once: true });
			}),
		);
	};

	const stop = () => {
		watching = false;
		tid && (clearTimeout(tid), (tid = null));
	};

	const onAppend = ({ items }) => watching && watch(items);
	Events.on('fx:masonry:append', onAppend);

	return {
		watch,
		stop,
		get watching() {
			return watching;
		},
		destroy: () => (stop(), Events.off('fx:masonry:append', onAppend)),
	};
};

// Re-export from shared utils
export { debounce, throttleRAF } from '../../helpers.js';
