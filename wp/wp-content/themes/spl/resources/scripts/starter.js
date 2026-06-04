// starter.js — Shared UI: mobile menu
// Dark mode is handled by preflight.js → utils/dark.js

import { $, on } from './core/dom.js';

const run = () => {
	// Mobile menu toggle
	const menuBtn = $('#mobile-menu-toggle');
	const nav = $('#main-nav');
	if (menuBtn && nav) {
		on(menuBtn, 'click', () => {
			const isOpen = menuBtn.getAttribute('aria-expanded') === 'true';
			menuBtn.setAttribute('aria-expanded', String(!isOpen));
			nav.classList.toggle('hidden', isOpen);
			nav.classList.toggle('block', !isOpen);
		});
	}
};

document.readyState === 'loading'
	? document.addEventListener('DOMContentLoaded', run, { once: true })
	: run();
