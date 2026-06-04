/**
 * Health status badge component.
 */

const COLORS: Record<string, string> = {
	ok: 'badge-green',
	healthy: 'badge-green',
	degraded: 'badge-yellow',
	error: 'badge-red',
};

export function renderHealthBadge(status: string): string {
	const cls = COLORS[status] ?? '';
	return `<span class="badge ${cls}">${status}</span>`;
}
