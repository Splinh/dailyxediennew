/**
 * HDAT Admin SPA entry point.
 *
 * Imports styles (Vite extracts CSS), registers all pages,
 * builds the nav sidebar, and starts the hash router.
 */

import '../styles/admin.scss';

import { router } from './router';
import { createDashboardPage } from './pages/dashboard';
import { createCredentialsPage } from './pages/credentials';
import { createTokensPage } from './pages/tokens';
import { createUsagePage } from './pages/usage';
import { createOpenRouterPage } from './pages/openrouter';
import { createSettingsPage } from './pages/settings';
import { createPlaygroundPage } from './pages/playground';

// ── Nav links ────────────────────────────────────

const NAV_ITEMS = [
	{
		hash: '#/dashboard',
		label: 'Dashboard',
		icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
	},
	{
		hash: '#/credentials',
		label: 'Credentials',
		icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>',
	},
	{
		hash: '#/tokens',
		label: 'Tokens',
		icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
	},
	{
		hash: '#/usage',
		label: 'Usage',
		icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
	},
	{
		hash: '#/openrouter',
		label: 'OpenRouter',
		icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
	},
	{
		hash: '#/playground',
		label: 'Playground',
		icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
	},
	{
		hash: '#/settings',
		label: 'Settings',
		icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6m5.2-13.2l-4.2 4.2m-2 2l-4.2 4.2M23 12h-6m-6 0H1m18.2 5.2l-4.2-4.2m-2-2l-4.2-4.2"/></svg>',
	},
];

function buildNav(): void {
	const nav = document.getElementById('hdat-nav');
	if (!nav) return;

	nav.innerHTML = `<div class="nav-brand"><span>HD AI</span> Toolkit</div>` + NAV_ITEMS.map((item) => `<a href="${item.hash}"><span class="nav-icon">${item.icon}</span>${item.label}</a>`).join('');
}

// ── Route registration ───────────────────────────

router.register('#/dashboard', createDashboardPage);
router.register('#/credentials', createCredentialsPage);
router.register('#/tokens', createTokensPage);
router.register('#/usage', createUsagePage);
router.register('#/openrouter', createOpenRouterPage);
router.register('#/playground', createPlaygroundPage);
router.register('#/settings', createSettingsPage);

// ── Boot ─────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
	buildNav();
	router.init();
});
