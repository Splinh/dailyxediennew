/**
 * Rate-limit progress bar component.
 */

export function renderRateLimitBar(remaining: number, limit: number): string {
	if (limit <= 0) {
		return '';
	}

	const pct = Math.round((remaining / limit) * 100);
	const color = pct > 50 ? 'rl-green' : pct > 20 ? 'rl-yellow' : 'rl-red';

	return `<div class="rl-row">
		<span>${remaining}/${limit}</span>
		<div class="rl-track"><div class="${color} rl-bar" style="width:${pct}%"></div></div>
	</div>`;
}
