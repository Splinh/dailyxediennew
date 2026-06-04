/**
 * Dashboard page — overview stats + route health.
 */

import type { Page } from '../router';
import { api } from '../api/client';

function formatTokens(n: number): string {
	if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M';
	if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K';
	return String(n);
}

export function createDashboardPage(): Page {
	return {
		async mount(el: HTMLElement) {
			el.innerHTML = '<div class="hdat-loading">Loading…</div>';

			try {
				const stats = await api.dashboard.stats();
				const todayReqs = stats.today?.requests ?? 0;
				const todayTokens = stats.today?.tokens ?? 0;
				const totalCreds = stats.credentials?.total ?? 0;
				const routeTotal = stats.routes?.total ?? 0;
				const routeHealthy = stats.routes?.healthy ?? 0;
				const routeDegraded = stats.routes?.degraded ?? 0;

				el.innerHTML = `<div class="hdat-page">
					<h2>Dashboard</h2>
					<div class="stats-grid">
						<div class="stat-card">
							<span class="stat-value">${todayReqs.toLocaleString()}</span>
							<span class="stat-label">Requests Today</span>
						</div>
						<div class="stat-card">
							<span class="stat-value">${formatTokens(todayTokens)}</span>
							<span class="stat-label">Tokens Today</span>
						</div>
						<div class="stat-card">
							<span class="stat-value">${totalCreds}</span>
							<span class="stat-label">Credentials</span>
						</div>
						<div class="stat-card">
							<span class="stat-value">${routeTotal}</span>
							<span class="stat-label">Routes</span>
						</div>
					</div>

					<h3>Route Health</h3>
					<div class="stats-grid">
						<div class="stat-card">
							<span class="stat-value success">${routeHealthy}</span>
							<span class="stat-label">Healthy</span>
						</div>
						<div class="stat-card">
							<span class="stat-value warning">${routeDegraded}</span>
							<span class="stat-label">Degraded</span>
						</div>
					</div>

					${
						stats.all_time
							? `
					<h3>All Time</h3>
					<div class="stats-grid">
						<div class="stat-card">
							<span class="stat-value">${(stats.all_time.requests ?? 0).toLocaleString()}</span>
							<span class="stat-label">Total Requests</span>
						</div>
						<div class="stat-card">
							<span class="stat-value">${formatTokens(stats.all_time.tokens ?? 0)}</span>
							<span class="stat-label">Total Tokens</span>
						</div>
					</div>`
							: ''
					}
				</div>`;
			} catch (err: any) {
				el.innerHTML = `<div class="hdat-page"><p class="error-message">Error: ${err.message}</p></div>`;
			}
		},
	};
}
