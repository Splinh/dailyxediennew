/**
 * Settings page — core settings + module toggles + GitHub updater token.
 */

import type { Page } from '../router';
import { api } from '../api/client';
import { toast } from '../components/toast';

function esc(s: string): string {
	const d = document.createElement('div');
	d.textContent = s;
	return d.innerHTML;
}

function tokenBadge(status: { has_token: boolean; source: string }): string {
	if (!status.has_token) {
		return '<span class="badge badge-gray">Not configured</span>';
	}
	const label = 'db' === status.source ? 'DB' : 'wp-config.php';
	return `<span class="badge badge-green">${esc(label)}</span>`;
}

async function renderPage(el: HTMLElement): Promise<void> {
	el.innerHTML = '<div class="hdat-loading">Loading…</div>';

	try {
		const [settings, modules, ghStatus] = await Promise.all([api.settings.get(), api.modules.list(), api.github.status()]);

		const moduleToggles = Array.isArray(modules)
			? modules
					.map(
						(m: any) => `<label class="toggle-row${m.always_active ? ' disabled' : ''}">
				<span>${esc(m.title)} <small class="text-muted">${esc(m.description ?? '')}</small></span>
				<label class="toggle">
					<input type="checkbox" data-module="${esc(m.slug)}"
						${m.active ? ' checked' : ''}
						${m.always_active ? ' disabled' : ''}>
					<span></span>
				</label>
			</label>`,
					)
					.join('')
			: '';

		el.innerHTML = `<div class="hdat-page">
			<form id="settings-form" class="hdat-form">
				<h2>Settings</h2>

				<label>Max route attempts
					<input name="max_route_attempts" type="number" value="${settings.max_route_attempts ?? 6}" min="1" max="10">
				</label>

				<label>Circuit breaker threshold
					<input name="circuit_threshold" type="number" value="${settings.circuit_threshold ?? 5}" min="1" max="20">
				</label>

				<label>Circuit breaker cooldown (seconds)
					<input name="circuit_ttl" type="number" value="${settings.circuit_ttl ?? 300}" min="60">
				</label>

				<div class="toggle-row">
					<span>Emit X-Routed-Via headers</span>
					<label class="toggle">
						<input type="checkbox" name="route_headers"${settings.route_headers ? ' checked' : ''}>
						<span></span>
					</label>
				</div>

				<div class="toggle-row">
					<span>Clean uninstall <small class="text-muted">(delete all data on plugin delete)</small></span>
					<label class="toggle">
						<input type="checkbox" name="clean_uninstall"${settings.clean_uninstall ? ' checked' : ''}>
						<span></span>
					</label>
				</div>

				${moduleToggles ? `<h3>Modules</h3>${moduleToggles}` : ''}

				<h3>GitHub Updates</h3>
				<div class="field-row" id="github-token-row">
					<label>Personal Access Token ${tokenBadge(ghStatus)}</label>
					<div class="github-token-input-group">
						<input id="github-token-input" type="password" placeholder="ghp_xxxx" autocomplete="off">
						<button type="button" id="save-github-token" class="btn-sm">Save Token</button>
						${ghStatus.has_token && 'db' === ghStatus.source ? '<button type="button" id="del-github-token" class="btn-sm btn-danger">Remove</button>' : ''}
					</div>
					<small class="text-muted">Encrypted at rest. Or define <code>HDAT_GITHUB_TOKEN</code> in wp-config.php.</small>
				</div>

				<button type="submit" class="btn-primary" style="margin-top:1.5rem">Save Settings</button>
			</form>
		</div>`;

		el.querySelector('#settings-form')!.addEventListener('submit', async (e) => {
			e.preventDefault();
			const form = e.target as HTMLFormElement;

			// Collect settings.
			const data: any = {
				max_route_attempts: Number(form.querySelector<HTMLInputElement>('[name="max_route_attempts"]')?.value),
				circuit_threshold: Number(form.querySelector<HTMLInputElement>('[name="circuit_threshold"]')?.value),
				circuit_ttl: Number(form.querySelector<HTMLInputElement>('[name="circuit_ttl"]')?.value),
				route_headers: !!form.querySelector<HTMLInputElement>('[name="route_headers"]')?.checked,
				clean_uninstall: !!form.querySelector<HTMLInputElement>('[name="clean_uninstall"]')?.checked,
			};

			try {
				await api.settings.save(data);

				// Save module toggles.
				if (Array.isArray(modules)) {
					const enabled = modules.filter((m: any) => !m.always_active && form.querySelector<HTMLInputElement>(`[data-module="${m.slug}"]`)?.checked).map((m: any) => m.slug);
					await api.modules.save(enabled);
				}

				toast.success('Settings saved');
			} catch (err: any) {
				toast.error(err.message);
			}
		});

		el.querySelector('#save-github-token')?.addEventListener('click', async () => {
			const input = el.querySelector<HTMLInputElement>('#github-token-input');
			const token = input?.value.trim() ?? '';
			if ('' === token) {
				toast.error('Token required');
				return;
			}
			try {
				await api.github.save(token);
				toast.success('GitHub token saved');
				if (input) {
					input.value = '';
				}
				await renderPage(el);
			} catch (err: any) {
				toast.error(err.message);
			}
		});

		el.querySelector('#del-github-token')?.addEventListener('click', async () => {
			if (!confirm('Remove the stored GitHub token?')) {
				return;
			}
			try {
				await api.github.remove();
				toast.success('GitHub token removed');
				await renderPage(el);
			} catch (err: any) {
				toast.error(err.message);
			}
		});
	} catch (err: any) {
		el.innerHTML = `<div class="hdat-page"><p class="error-message">Error: ${err.message}</p></div>`;
	}
}

export function createSettingsPage(): Page {
	return { mount: renderPage };
}
