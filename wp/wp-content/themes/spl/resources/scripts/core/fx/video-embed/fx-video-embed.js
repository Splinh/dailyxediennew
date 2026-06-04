// fx/video-embed/fx-video-embed.js

import { $$ as qsa, on, off, create, css, hasClass, addClass, removeClass } from '../../dom.js';
import { createWeakStore } from '../../weak.js';

const SELECTOR = '[data-fx-video]';
const ACTIVATED = 'is-video-active';

const store = createWeakStore();

/** @type {IntersectionObserver|null} */
let observer = null;

/**
 * Supported video providers.
 * Each provider has a `match` and `createEl` function.
 * - match(type): returns true if the type string belongs to this provider
 * - createEl(url, muted): returns an HTMLElement (iframe or video) to embed
 */
const providers = {
	youtube: {
		match: (type) => type === 'youtube',

		/**
		 * @param {string} url
		 * @param {boolean} muted
		 * @returns {HTMLIFrameElement|null}
		 */
		createEl(url, muted = false) {
			const videoId = extractYouTubeId(url);
			if (!videoId) return null;

			const params = new URLSearchParams({
				autoplay: '1',
				rel: '0',
			});

			if (muted) {
				params.set('mute', '1');
			}

			const iframe = create('iframe', {
				class: 'w-full h-full',
				src: `https://www.youtube.com/embed/${videoId}?${params}`,
				allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture',
				allowfullscreen: '',
				frameborder: '0',
			});

			return iframe;
		},
	},

	/**
	 * Native <video> for self-hosted .mp4 / .webm / .ogg files
	 */
	video: {
		match: (type) => type === 'video',

		/**
		 * @param {string} url
		 * @param {boolean} muted
		 * @returns {HTMLVideoElement}
		 */
		createEl(url, muted = false) {
			const video = create('video', {
				class: 'w-full h-full',
				src: url,
				controls: '',
				autoplay: '',
				playsinline: '',
			});

			if (muted) {
				video.muted = true;
			}

			return video;
		},
	},
};

/**
 * Extract YouTube video ID from various URL formats
 * Supports:
 *   - https://www.youtube.com/watch?v=VIDEO_ID
 *   - https://youtu.be/VIDEO_ID
 *   - https://www.youtube.com/embed/VIDEO_ID
 *   - https://www.youtube.com/shorts/VIDEO_ID
 *
 * @param {string} url
 * @returns {string|null}
 */
const extractYouTubeId = (url) => {
	if (!url) return null;

	try {
		const u = new URL(url);

		// youtube.com/watch?v=xxx
		if (u.searchParams.has('v')) {
			return u.searchParams.get('v');
		}

		// youtu.be/xxx or youtube.com/embed/xxx or youtube.com/shorts/xxx
		const match = u.pathname.match(/\/(embed|shorts|v)?\/?([a-zA-Z0-9_-]{11})/);
		if (match) return match[2];

		// youtu.be/xxx (short URL without prefix)
		if (u.hostname === 'youtu.be') {
			return u.pathname.slice(1).split(/[?&/]/)[0] || null;
		}
	} catch {
		// Fallback regex for malformed URLs
		const fallback = url.match(/(?:v=|\/embed\/|\/shorts\/|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
		return fallback ? fallback[1] : null;
	}

	return null;
};

/**
 * Activate a video container — create the embed, overlay, and fade in.
 *
 * @param {HTMLElement} container
 * @param {boolean} muted - true = muted (auto-scroll), false = with sound (click)
 */
const activate = (container, muted) => {
	if (hasClass(container, ACTIVATED)) return;

	const type = container.dataset.fxVideoType;
	const url = container.dataset.fxVideoUrl;

	if (!type || !url) return;

	// Find the matching provider
	const provider = Object.values(providers).find((p) => p.match(type));
	if (!provider) {
		console.warn(`[FxVideoEmbed] Unsupported video type: "${type}"`);
		return;
	}

	const el = provider.createEl(url, muted);
	if (!el) {
		console.warn(`[FxVideoEmbed] Could not create embed for: "${url}"`);
		return;
	}

	// Mark activated immediately to prevent double-trigger
	addClass(container, ACTIVATED);
	removeClass(container, 'cursor-pointer');
	off(container, 'click', handleClick);

	// Stop observing if it was being watched for auto-play
	if (observer) {
		observer.unobserve(container);
	}

	// Overlay the embed on top of the thumbnail (invisible until loaded)
	css(el, {
		position: 'absolute',
		inset: '0',
		zIndex: '10',
		opacity: '0',
	});

	container.appendChild(el);

	// Once ready, fade in the embed and remove old content
	const onReady = () => {
		el.style.transition = 'opacity 0.3s ease';
		el.style.opacity = '1';

		// After fade-in completes, clean up old children
		setTimeout(() => {
			Array.from(container.children).forEach((child) => {
				if (child !== el) child.remove();
			});

			// Reset inline overlay styles — let CSS classes handle sizing
			el.style.position = '';
			el.style.inset = '';
			el.style.zIndex = '';
			el.style.transition = '';
			el.style.opacity = '';
		}, 320);
	};

	if (el.tagName === 'IFRAME') {
		on(el, 'load', onReady, { once: true });
	} else {
		// <video> fires loadeddata when first frame is available
		on(el, 'loadeddata', onReady, { once: true });
	}
};

/**
 * Handle manual click → play with sound
 * @param {Event} e
 */
const handleClick = (e) => {
	activate(e.currentTarget, false);
};

/**
 * Create IntersectionObserver for auto-play containers
 * @returns {IntersectionObserver}
 */
const getObserver = () => {
	if (!observer) {
		observer = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						activate(entry.target, true);
					}
				});
			},
			{
				rootMargin: '0px 0px 50px 0px',
				threshold: 0.2,
			},
		);
	}

	return observer;
};

const FxVideoEmbed = {
	initAll(root = document) {
		qsa(SELECTOR, root).forEach((container) => {
			// Skip already activated containers
			if (hasClass(container, ACTIVATED)) return;

			// Always bind click (manual trigger with sound)
			on(container, 'click', handleClick);
			store.set(container, true);

			// If data-fx-video-autoplay is present, also observe for scroll-based trigger (muted)
			if (container.hasAttribute('data-fx-video-autoplay')) {
				getObserver().observe(container);
			}
		});
	},

	destroyAll(root = document) {
		qsa(SELECTOR, root).forEach((container) => {
			off(container, 'click', handleClick);

			// Only unobserve this container, don't kill the global observer
			if (observer) {
				observer.unobserve(container);
			}

			// Remove activated state so re-init works
			removeClass(container, ACTIVATED);
			store.delete(container);
		});

		// Only disconnect if destroying from document root (full teardown)
		if (root === document && observer) {
			observer.disconnect();
			observer = null;
		}
	},
};

export default FxVideoEmbed;
