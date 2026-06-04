// scrollspy/fx-scrollspy.js
import './fx-scrollspy.scss';

import { $ as qs, $$ as qsa, on, off, addClass, removeClass } from '../../dom.js';
import { createWeakStore } from '../../weak.js';
import Events from '../../events.js';

const SELECTOR = '[data-fx-scrollspy]';
const ACTIVE = 'is-active';
const store = createWeakStore();

/**
 * Bind scrollspy and reading progress to a navigation container.
 * @param {HTMLElement} navEl
 */
const bind = (navEl) => {
	if (store.has(navEl)) return;

	// Target content area (defaults to body)
	const targetSelector = navEl.dataset.fxScrollspy;
	const contentEl = targetSelector ? qs(targetSelector) : document.body;
	if (!contentEl) return;

	// ── 1. SCROLLSPY ─────────────────────────────────────

	const links = qsa('a[href^="#"]', navEl);
	const sections = [];
	const linkMap = new Map();

	links.forEach((link) => {
		const id = link.getAttribute('href');
		if (id.length <= 1) return; // Skip bare "#"

		const section = qs(id, contentEl);
		if (section) {
			sections.push(section);
			linkMap.set(section, link);
		}
	});

	// Nothing to spy on → bail early
	if (!sections.length) return;

	let activeLink = null;

	/** Live map of all sections currently inside the rootMargin zone */
	const visibleSections = new Map();

	const setActive = (link) => {
		if (link === activeLink) return;

		if (activeLink) {
			removeClass(activeLink, ACTIVE);
			activeLink.removeAttribute('aria-current');
		}

		if (link) {
			addClass(link, ACTIVE);
			link.setAttribute('aria-current', 'true');
		}

		activeLink = link;
		Events.emit('fx:scrollspy:change', { el: navEl, activeLink: link });
	};

	const observer = new IntersectionObserver(
		(entries) => {
			// Update the live visibility map
			for (const entry of entries) {
				if (entry.isIntersecting) {
					visibleSections.set(entry.target, entry);
				} else {
					visibleSections.delete(entry.target);
				}
			}

			// Resolve best section from ALL currently visible ones (DOM order, highest ratio)
			let bestEntry = null;

			for (const section of sections) {
				const entry = visibleSections.get(section);
				if (!entry) continue;
				if (!bestEntry || entry.intersectionRatio > bestEntry.intersectionRatio) {
					bestEntry = entry;
				}
			}

			if (bestEntry) {
				setActive(linkMap.get(bestEntry.target));
			} else {
				const firstRect = sections[0].getBoundingClientRect();
				const lastRect = sections.at(-1).getBoundingClientRect();

				if (firstRect.top > window.innerHeight * 0.4) {
					// User is above all sections → clear
					setActive(null);
				} else if (lastRect.bottom <= window.innerHeight) {
					// User is at/past the end of content → keep last section active
					setActive(linkMap.get(sections.at(-1)));
				}
			}
		},
		{
			rootMargin: '-10% 0px -60% 0px',
			threshold: [0, 0.25, 0.5, 1],
		},
	);

	sections.forEach((sec) => observer.observe(sec));

	// ── 2. READING PROGRESS ──────────────────────────────

	const progressEl = qs('.hd-scrollspy-progress', navEl);
	let scrollHandler = null;
	let resizeHandler = null;

	if (progressEl) {
		const bar = qs('.hd-scrollspy-progress__bar', progressEl) || progressEl;
		let ticking = false;

		const updateProgress = () => {
			const rect = contentEl.getBoundingClientRect();
			const vh = window.innerHeight;

			// Content shorter than viewport → full
			if (rect.height <= vh) {
				bar.style.transform = 'scaleX(1)';
				return;
			}

			const scrolled = -rect.top;
			const total = rect.height - vh;
			const progress = Math.round(Math.max(0, Math.min(1, scrolled / total)) * 1000) / 1000;

			bar.style.transform = `scaleX(${progress})`;
		};

		scrollHandler = () => {
			if (ticking) return;
			ticking = true;
			requestAnimationFrame(() => {
				updateProgress();
				ticking = false;
			});
		};

		// Recalculate on resize (viewport height changes)
		resizeHandler = scrollHandler;

		on(window, 'scroll', scrollHandler, { passive: true });
		on(window, 'resize', resizeHandler, { passive: true });
		updateProgress(); // Initial state
	}

	store.set(navEl, { observer, scrollHandler, resizeHandler, visibleSections });
	Events.emit('fx:scrollspy:init', { el: navEl });
};

/**
 * Unbind scrollspy from a navigation container.
 * @param {HTMLElement} navEl
 */
const unbind = (navEl) => {
	// Clear active state from any remaining links
	const active = qs(`.${ACTIVE}`, navEl);
	if (active) {
		removeClass(active, ACTIVE);
		active.removeAttribute('aria-current');
	}

	store.cleanup(navEl, ({ observer, scrollHandler, resizeHandler, visibleSections }) => {
		if (observer) observer.disconnect();
		if (scrollHandler) off(window, 'scroll', scrollHandler);
		if (resizeHandler) off(window, 'resize', resizeHandler);
		if (visibleSections) visibleSections.clear();
	});
};

const FxScrollspy = {
	initAll(root = document) {
		qsa(SELECTOR, root).forEach(bind);
	},

	destroyAll(root = document) {
		qsa(SELECTOR, root).forEach(unbind);
	},
};

export default FxScrollspy;
