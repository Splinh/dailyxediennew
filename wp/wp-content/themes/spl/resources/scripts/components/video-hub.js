/**
 * DailyXeDien Video Hub Script
 * - Swiper Slider for TikTok & Shorts 9:16 Zone
 * - Live Category Tabs & Search Filter
 * - Universal Smart Lightbox Modal (16:9 Cinema & 9:16 Vertical)
 *
 * @package SPL
 * @author  SPL
 */

import Swiper from 'swiper';
import { Navigation, FreeMode } from 'swiper/modules';

document.addEventListener('DOMContentLoaded', () => {
	initShortsSwiper();
	initVideoFilters();
	initUniversalModal();
});

/**
 * 1. Initialize Swiper for TikTok & Shorts 9:16 Carousel
 */
function initShortsSwiper() {
	const container = document.querySelector('.vh-shorts-swiper');
	if (!container) return;

	new Swiper(container, {
		modules: [Navigation, FreeMode],
		slidesPerView: 2.2,
		spaceBetween: 12,
		grabCursor: true,
		watchSlidesProgress: true,
		navigation: {
			nextEl: '.vh-shorts-next',
			prevEl: '.vh-shorts-prev',
		},
		breakpoints: {
			480: {
				slidesPerView: 2.8,
				spaceBetween: 14,
			},
			640: {
				slidesPerView: 3.5,
				spaceBetween: 16,
			},
			992: {
				slidesPerView: 4.5,
				spaceBetween: 18,
			},
			1200: {
				slidesPerView: 5.5,
				spaceBetween: 20,
			},
		},
	});
}

/**
 * 2. Category Tabs & Live Search Filter
 */
function initVideoFilters() {
	const tabItems = document.querySelectorAll('.vh-tab-item');
	const searchInput = document.querySelector('.vh-search-input');
	const ratioSelect = document.querySelector('.vh-ratio-select');
	const cards = document.querySelectorAll('.vh-gallery-section .vh-card');
	const emptyState = document.querySelector('.vh-empty-state');

	if (!cards.length) return;

	let currentCategory = 'all';
	let currentKeyword = '';
	let currentRatio = 'all';

	function applyFilters() {
		let visibleCount = 0;

		cards.forEach((card) => {
			const cardCats = (card.getAttribute('data-categories') || '').toLowerCase();
			const cardRatio = (card.getAttribute('data-orientation') || '').toLowerCase();
			const cardText = (card.textContent || '').toLowerCase();

			// Check Category
			const matchCat =
				currentCategory === 'all' || cardCats.includes(currentCategory.toLowerCase());

			// Check Ratio
			const matchRatio =
				currentRatio === 'all' ||
				(currentRatio === '16_9' && cardRatio.includes('horizontal')) ||
				(currentRatio === '9_16' && cardRatio.includes('vertical'));

			// Check Keyword
			const matchKeyword =
				!currentKeyword || cardText.includes(currentKeyword.toLowerCase());

			if (matchCat && matchRatio && matchKeyword) {
				card.style.display = 'flex';
				visibleCount++;
			} else {
				card.style.display = 'none';
			}
		});

		if (emptyState) {
			emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
		}
	}

	// Tab click
	tabItems.forEach((tab) => {
		tab.addEventListener('click', () => {
			tabItems.forEach((t) => t.classList.remove('is-active'));
			tab.classList.add('is-active');
			currentCategory = tab.getAttribute('data-category') || 'all';
			applyFilters();
		});
	});

	// Search input
	if (searchInput) {
		let debounceTimer;
		searchInput.addEventListener('input', (e) => {
			clearTimeout(debounceTimer);
			debounceTimer = setTimeout(() => {
				currentKeyword = e.target.value.trim();
				applyFilters();
			}, 200);
		});
	}

	// Ratio select
	if (ratioSelect) {
		ratioSelect.addEventListener('change', (e) => {
			currentRatio = e.target.value;
			applyFilters();
		});
	}
}

/**
 * 3. Universal Smart Lightbox Modal
 */
function initUniversalModal() {
	const modal = document.querySelector('.vh-modal');
	if (!modal) return;

	const container = modal.querySelector('.vh-modal-container');
	const frame = modal.querySelector('.vh-modal-frame');
	const modalTitle = modal.querySelector('.vh-modal-title');
	const modalProdLink = modal.querySelector('.vh-modal-prod-link');
	const closeBtn = modal.querySelector('.vh-modal-close');
	const backdrop = modal.querySelector('.vh-modal-backdrop');

	function openVideoModal(triggerEl) {
		const embedUrl = triggerEl.getAttribute('data-embed-url') || '';
		const source = triggerEl.getAttribute('data-source') || 'youtube';
		const orientation = triggerEl.getAttribute('data-orientation') || 'horizontal_16_9';
		const title = triggerEl.getAttribute('data-title') || '';
		const prodUrl = triggerEl.getAttribute('data-prod-url') || '';
		const prodName = triggerEl.getAttribute('data-prod-name') || '';
		const iframeRaw = triggerEl.getAttribute('data-iframe') || '';

		if (!embedUrl && !iframeRaw) return;

		// Reset frame content
		frame.innerHTML = '';

		// Set Orientation Class
		if (orientation.includes('vertical') || orientation.includes('9_16')) {
			container.classList.remove('is-horizontal');
			container.classList.add('is-vertical');
		} else {
			container.classList.remove('is-vertical');
			container.classList.add('is-horizontal');
		}

		// Set Title
		if (modalTitle) {
			modalTitle.textContent = title;
		}

		// Set Product Link if available
		if (modalProdLink) {
			if (prodUrl) {
				modalProdLink.href = prodUrl;
				modalProdLink.innerHTML = `Xem trang sản phẩm &rarr;`;
				modalProdLink.style.display = 'inline-flex';
			} else {
				modalProdLink.style.display = 'none';
			}
		}

		// Render Media (Iframe or Video)
		if (iframeRaw) {
			frame.innerHTML = iframeRaw;
		} else if (source === 'mp4_direct') {
			const video = document.createElement('video');
			video.controls = true;
			video.autoplay = true;
			video.playsInline = true;
			const sourceEl = document.createElement('source');
			sourceEl.src = embedUrl;
			sourceEl.type = 'video/mp4';
			video.appendChild(sourceEl);
			frame.appendChild(video);
		} else {
			// YouTube / TikTok / Facebook Embed Iframe
			const iframe = document.createElement('iframe');
			iframe.src = embedUrl;
			iframe.setAttribute('allowfullscreen', '1');
			iframe.setAttribute(
				'allow',
				'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share'
			);
			iframe.setAttribute('loading', 'lazy');
			frame.appendChild(iframe);
		}

		// Show Modal
		modal.classList.add('is-open');
		document.body.style.overflow = 'hidden';
	}

	function closeVideoModal() {
		modal.classList.remove('is-open');
		document.body.style.overflow = '';

		// Stop video & clear memory after animation
		setTimeout(() => {
			if (frame) frame.innerHTML = '';
		}, 250);
	}

	// Attach click to all video triggers across the page
	document.addEventListener('click', (e) => {
		const trigger = e.target.closest('[data-video-trigger]');
		if (trigger) {
			if (e.target.closest('a')) {
				return;
			}
			e.preventDefault();
			openVideoModal(trigger);
		}
	});

	// Close handlers
	if (closeBtn) closeBtn.addEventListener('click', closeVideoModal);
	if (backdrop) backdrop.addEventListener('click', closeVideoModal);

	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && modal.classList.contains('is-open')) {
			closeVideoModal();
		}
	});
}
