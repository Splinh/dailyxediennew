// index.js — Main entry point

import { initAll } from './core/index.js';

import './utils/global.js';
import scriptLoader from './utils/script-loader.js';

// Styles → Vite auto-extracts CSS
import '../styles/main.scss';

// TailwindCSS -> tw.xxx.css
import '../styles/tailwind/index.css';

const run = async () => {
	try {
		await initAll();
	} catch (e) {
		console.error('[HD] initAll failed:', e);
	}

	await scriptLoader();
};

document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', run, { once: true }) : run();
