/**
 * Usage stats page — per-provider breakdown with date filters.
 */

import type { Page } from '../router';
import { api } from '../api/client';

function formatTokens(n: number): string {
	const units = ['', 'K', 'M', 'B', 'T'];
	let idx = 0;
	let val = n;
	while (Math.abs(val) >= 1_000 && idx < units.length - 1) {
		val /= 1_000;
		idx++;
	}
	return (idx === 0 ? String(val) : val.toFixed(1).replace(/\.0$/, '')) + units[idx];
}

async function renderPage(el: HTMLElement, filters: Record<string, string> = {}): Promise<void> {
	el.innerHTML = '<div class="hdat-loading">Loading…</div>';

	try {
		const stats = await api.usage.stats(filters);

		const summary = stats.summary ?? {};
		const byProvider = stats.by_provider ?? [];

		const rows = Array.isArray(byProvider)
			? byProvider
					.map(
						(p: any) => `<tr>
				<td>${p.provider}</td>
				<td><code>${p.model || '—'}</code></td>
				<td>${(p.requests ?? 0).toLocaleString()}</td>
				<td>${formatTokens(p.prompt_tokens ?? 0)}</td>
				<td>${formatTokens(p.completion_tokens ?? 0)}</td>
				<td>${formatTokens(p.total_tokens ?? 0)}</td>
			</tr>`,
					)
					.join('')
			: '';

		el.innerHTML = `<div class="hdat-page">
			<h2>Usage</h2>

			<form id="usage-filters" class="usage-filters">
				<label class="field-label">
					From <input type="date" name="from" value="${filters.from ?? ''}" class="input-search">
				</label>
				<label class="field-label">
					To <input type="date" name="to" value="${filters.to ?? ''}" class="input-search">
				</label>
				<button type="submit" class="btn-sm">Filter</button>
			</form>

			<div class="stats-grid">
				<div class="stat-card">
					<span class="stat-value">${(summary.requests ?? 0).toLocaleString()}</span>
					<span class="stat-label">Requests</span>
				</div>
				<div class="stat-card">
					<span class="stat-value">${formatTokens(summary.tokens ?? 0)}</span>
					<span class="stat-label">Total Tokens</span>
				</div>
			</div>

			${
				rows
					? `<table class="hdat-table">
				<thead><tr><th>Provider</th><th>Model</th><th>Requests</th><th>Prompt</th><th>Completion</th><th>Total</th></tr></thead>
				<tbody>${rows}</tbody>
			</table>`
					: '<div class="hdat-empty"><p>No usage data for this period.</p></div>'
			}
		</div>`;

		el.querySelector('#usage-filters')?.addEventListener('submit', (e) => {
			e.preventDefault();
			const fd = new FormData(e.target as HTMLFormElement);
			const f: Record<string, string> = {};
			const from = fd.get('from') as string;
			const to = fd.get('to') as string;
			if (from) f.from = from;
			if (to) f.to = to;
			renderPage(el, f);
		});
	} catch (err: any) {
		el.innerHTML = `<div class="hdat-page"><p class="error-message">Error: ${err.message}</p></div>`;
	}
}

export function createUsagePage(): Page {
	return { mount: (el) => renderPage(el) };
}
