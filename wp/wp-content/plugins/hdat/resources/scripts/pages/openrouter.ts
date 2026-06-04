/**
 * OpenRouter pool management page.
 *
 * Model cards with enable/disable toggles, rate-limit bars (30s poll),
 * search filter, drag-reorder priority, sync button.
 */

import type { Page } from '../router';
import { api } from '../api/client';
import { toast } from '../components/toast';
import { renderRateLimitBar } from '../components/rate-limit-bar';

let models: any[] = [];
let pool: any = { models: [] };
let rl: Record<string, any> = {};
let pollId: ReturnType<typeof setInterval> | undefined;

function esc(s: string): string {
	const d = document.createElement('div');
	d.textContent = s;
	return d.innerHTML;
}

function statusDotClass(m: any): string {
	const rlData = rl[m.id];
	// No RL data yet → model hasn't been rate-limited, assume available.
	if (!rlData) return 'status-dot--active';
	return (rlData.remaining ?? 0) > 0 ? 'status-dot--active' : 'status-dot--exhausted';
}

function card(m: any): string {
	const entry = pool.models?.find((e: any) => e.id === m.id);
	const enabled = entry?.enabled ?? false;
	const rlData = rl[m.id];
	const rlHtml = rlData ? renderRateLimitBar(rlData.remaining ?? 0, rlData.limit ?? 0) : '';

	return `<div class="model-card${enabled ? ' enabled' : ''}" data-id="${esc(m.id)}" draggable="true">
		<div class="model-card-head">
			<span class="model-name" title="${esc(m.id)}">${esc(m.name || m.id)}</span>
			<span class="status-dot ${statusDotClass(m)}" data-dot="${esc(m.id)}"></span>
			<label class="toggle">
				<input type="checkbox" data-toggle="${esc(m.id)}"${enabled ? ' checked' : ''}>
				<span></span>
			</label>
		</div>
		${rlHtml}
		<div class="model-card-foot">
			<span class="badge badge-gray">${Math.round((m.context_length ?? 0) / 1000)}K ctx</span>
		</div>
	</div>`;
}

function updateBars(el: HTMLElement): void {
	for (const [id, data] of Object.entries(rl)) {
		const bar = el.querySelector<HTMLElement>(`[data-id="${id}"] .rl-bar`);
		if (bar) {
			const d = data as any;
			const pct = d.limit > 0 ? Math.round((d.remaining / d.limit) * 100) : 100;
			bar.style.width = `${pct}%`;
			bar.className = `rl-bar ${pct > 50 ? 'rl-green' : pct > 20 ? 'rl-yellow' : 'rl-red'}`;
		}

		// Update status dots.
		const dot = el.querySelector<HTMLElement>(`[data-dot="${id}"]`);
		if (dot) {
			const d = data as any;
			dot.className = `status-dot ${(d.remaining ?? 0) > 0 ? 'status-dot--active' : 'status-dot--exhausted'}`;
		}
	}
}

function initDragReorder(el: HTMLElement, list: HTMLElement): void {
	let dragItem: HTMLElement | null = null;

	list.addEventListener('dragstart', (e) => {
		dragItem = (e.target as HTMLElement).closest('.model-card');
		if (dragItem) dragItem.classList.add('dragging');
	});

	list.addEventListener('dragover', (e) => {
		e.preventDefault();
		const target = (e.target as HTMLElement).closest<HTMLElement>('.model-card');
		if (target && target !== dragItem && dragItem) {
			const rect = target.getBoundingClientRect();
			const mid = rect.top + rect.height / 2;
			if ((e as DragEvent).clientY < mid) {
				list.insertBefore(dragItem, target);
			} else {
				list.insertBefore(dragItem, target.nextSibling);
			}
		}
	});

	list.addEventListener('dragend', async () => {
		if (dragItem) dragItem.classList.remove('dragging');
		dragItem = null;

		// M4: Update priority from visible cards only (skip search-hidden).
		const cards = el.querySelectorAll<HTMLElement>('.model-card:not([style*="display: none"])');
		cards.forEach((c, i) => {
			const id = c.dataset.id!;
			let entry = pool.models?.find((m: any) => m.id === id);
			if (!entry) {
				entry = { id, enabled: false, priority: 5 };
				pool.models.push(entry);
			}
			entry.priority = cards.length - i;
		});

		try {
			await api.openrouter.savePool(pool);
		} catch (err: any) {
			toast.error(err.message);
		}
	});
}

async function render(el: HTMLElement): Promise<void> {
	// Re-rendering (Sync click, etc.) must not stack pollers.
	if (pollId) {
		clearInterval(pollId);
		pollId = undefined;
	}

	el.innerHTML = '<div class="hdat-loading">Loading…</div>';

	try {
		[models, pool, rl] = await Promise.all([api.openrouter.models(), api.openrouter.pool(), api.openrouter.rateLimits()]);

		if (!Array.isArray(pool.models)) {
			pool = { models: [] };
		}

		const free = Array.isArray(models) ? models.filter((m) => m.id?.endsWith(':free')) : models;
		// O6: Count only pool entries whose id is in the free models list.
		const freeIds = new Set(free.map((m: any) => m.id));
		const enabledCount = pool.models.filter((m: any) => m.enabled && freeIds.has(m.id)).length;
		const allEnabled = enabledCount >= free.length;

		el.innerHTML = `<div class="hdat-page">
			<div class="playground-header">
				<div class="hdat-toolbar-title" style="display: flex; align-items: center; gap: 0.75rem;">
					<h2>OpenRouter Pool</h2>
					<span class="badge badge-indigo">${enabledCount}/${free.length} enabled</span>
				</div>
				<div class="hdat-toolbar-actions" style="display: flex; align-items: center; gap: 0.75rem;">
					<input id="or-search" type="text" placeholder="Search models…" class="input-search" style="margin: 0; width: 220px;">
					<button id="or-toggle-all" class="btn-sm${allEnabled ? ' btn-danger' : ''}">${allEnabled ? 'Disable All' : 'Enable All'}</button>
					<button id="or-sync" class="btn-sm">Sync models</button>
				</div>
			</div>
			<p class="text-muted" style="margin-top:-0.5rem; margin-bottom:1.5rem; font-size:0.85rem; max-width: 800px; line-height: 1.45;">
				Pool models supplement the Preferred Model as fallback. If a credential's Preferred Model is rate-limited, the system falls back to pool models in priority order (drag to reorder). If all pool models are also limited: free credentials fall back to <code>openrouter/free</code>, paid credentials to <code>openrouter/auto</code>.
			</p>
			<div id="or-list" class="model-grid">
				${[...free]
					.sort((a, b) => {
						const pa = pool.models?.find((e: any) => e.id === a.id)?.priority ?? 0;
						const pb = pool.models?.find((e: any) => e.id === b.id)?.priority ?? 0;
						return pb - pa;
					})
					.map((m) => card(m))
					.join('')}
			</div>
		</div>`;

		// Sync button.
		el.querySelector('#or-sync')?.addEventListener('click', async () => {
			const btn = el.querySelector<HTMLButtonElement>('#or-sync')!;
			btn.disabled = true;
			btn.textContent = 'Syncing…';
			try {
				await api.openrouter.sync();
				models = await api.openrouter.models();
				toast.success('Models synced');
				render(el);
			} catch (err: any) {
				toast.error(err.message);
				btn.disabled = false;
				btn.textContent = 'Sync models';
			}
		});

		// Search.
		el.querySelector('#or-search')?.addEventListener('input', (e) => {
			const q = (e.target as HTMLInputElement).value.toLowerCase();
			el.querySelectorAll<HTMLElement>('.model-card').forEach((c) => {
				c.style.display = c.dataset.id!.toLowerCase().includes(q) ? '' : 'none';
			});
		});

		// Toggle all models.
		el.querySelector('#or-toggle-all')?.addEventListener('click', async () => {
			const btn = el.querySelector<HTMLButtonElement>('#or-toggle-all')!;
			const currentEnabled = pool.models.filter((m: any) => m.enabled).length;
			const totalFree = Array.isArray(models) ? models.filter((m) => m.id?.endsWith(':free')).length : 0;
			const willEnable = currentEnabled < totalFree;

			btn.disabled = true;
			btn.textContent = willEnable ? 'Enabling…' : 'Disabling…';

			// Update pool entries for all free models.
			const freeModels = Array.isArray(models) ? models.filter((m) => m.id?.endsWith(':free')) : [];
			for (const m of freeModels) {
				const entry = pool.models.find((e: any) => e.id === m.id);
				if (entry) {
					entry.enabled = willEnable;
				} else {
					pool.models.push({ id: m.id, enabled: willEnable, priority: 5 });
				}
			}

			try {
				await api.openrouter.savePool(pool);
				toast.success(willEnable ? 'All models enabled' : 'All models disabled');
				render(el);
			} catch (err: any) {
				toast.error(err.message);
				btn.disabled = false;
				btn.textContent = willEnable ? 'Enable All' : 'Disable All';
			}
		});

		// H2: Use onchange assignment to prevent stacking handlers on re-render.
		el.onchange = async (e: Event) => {
			const cb = (e.target as HTMLElement).closest<HTMLInputElement>('[data-toggle]');
			if (!cb) return;
			const id = cb.dataset.toggle!;
			const entry = pool.models.find((m: any) => m.id === id);
			if (entry) {
				entry.enabled = cb.checked;
			} else {
				pool.models.push({ id, enabled: cb.checked, priority: 5 });
			}

			// Toggle card style.
			const cardEl = cb.closest('.model-card');
			cardEl?.classList.toggle('enabled', cb.checked);

			try {
				await api.openrouter.savePool(pool);
			} catch (err: any) {
				toast.error(err.message);
			}
		};

		// Drag reorder.
		const list = el.querySelector<HTMLElement>('#or-list');
		if (list) initDragReorder(el, list);

		// Rate-limit polling.
		pollId = setInterval(async () => {
			try {
				rl = await api.openrouter.rateLimits();
				updateBars(el);
			} catch {
				// Silently ignore poll failures.
			}
		}, 30_000);
	} catch (err: any) {
		el.innerHTML = `<div class="hdat-page"><p class="error-message">Error: ${err.message}</p></div>`;
	}
}

export function createOpenRouterPage(): Page {
	return {
		mount: render,
		unmount() {
			if (pollId) clearInterval(pollId);
			pollId = undefined;
		},
	};
}
