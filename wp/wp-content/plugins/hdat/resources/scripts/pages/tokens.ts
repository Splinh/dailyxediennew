/**
 * Consumer tokens page — list, create, revoke.
 */

import type { Page } from '../router';
import { api } from '../api/client';
import { toast } from '../components/toast';

function esc(s: string): string {
	const d = document.createElement('div');
	d.textContent = s;
	return d.innerHTML;
}

function renderTable(tokens: any[]): string {
	if (!tokens.length) {
		return '<div class="hdat-empty"><p>No consumer tokens yet.</p></div>';
	}

	const rows = tokens
		.map((t) => {
			const revoked = !!t.revoked_at;
			const expired = t.expires_at && new Date(t.expires_at) < new Date();
			const status = revoked ? '<span class="badge badge-red">Revoked</span>' : expired ? '<span class="badge badge-yellow">Expired</span>' : '<span class="badge badge-green">Active</span>';

			return `<tr>
			<td>${esc(t.name)}</td>
			<td><code>${esc(t.token_prefix)}…</code></td>
			<td>${t.rpm_limit ?? '∞'} / ${t.rpd_limit ?? '∞'}</td>
			<td>${t.tpm_limit ?? '∞'} / ${t.tpd_limit ?? '∞'}</td>
			<td>${status}</td>
			<td>${!revoked ? `<button class="btn-xs btn-danger" data-revoke="${t.id}">Revoke</button>` : ''}</td>
		</tr>`;
		})
		.join('');

	return `<table class="hdat-table">
		<thead><tr><th>Name</th><th>Prefix</th><th>RPM/RPD</th><th>TPM/TPD</th><th>Status</th><th></th></tr></thead>
		<tbody>${rows}</tbody>
	</table>`;
}

function showCreateModal(el: HTMLElement, onDone: () => void): void {
	const overlay = document.createElement('div');
	overlay.className = 'hdat-modal-overlay';
	overlay.innerHTML = `<div class="hdat-modal">
		<h3>Create Token</h3>
		<form class="hdat-form" id="token-form">
			<label>Name <input type="text" name="name" required></label>
			<label>RPM Limit <input type="number" name="rpm_limit" placeholder="unlimited"></label>
			<label>RPD Limit <input type="number" name="rpd_limit" placeholder="unlimited"></label>
			<label>TPM Limit <input type="number" name="tpm_limit" placeholder="unlimited"></label>
			<label>TPD Limit <input type="number" name="tpd_limit" placeholder="unlimited"></label>
			<label>Expires at <input type="date" name="expires_at"></label>
			<div class="hdat-modal-actions">
				<button type="button" class="btn-sm" id="modal-cancel">Cancel</button>
				<button type="submit" class="btn-primary">Create</button>
			</div>
		</form>
	</div>`;

	document.body.appendChild(overlay);
	overlay.querySelector('#modal-cancel')!.addEventListener('click', () => overlay.remove());

	overlay.querySelector('#token-form')!.addEventListener('submit', async (e) => {
		e.preventDefault();
		const fd = new FormData(e.target as HTMLFormElement);
		const data: any = { name: fd.get('name') };
		for (const key of ['rpm_limit', 'rpd_limit', 'tpm_limit', 'tpd_limit']) {
			const v = fd.get(key) as string;
			if (v) data[key] = Number(v);
		}
		const exp = fd.get('expires_at') as string;
		if (exp) data.expires_at = exp;

		try {
			const result = await api.tokens.create(data);
			overlay.remove();

			// Show the raw token once.
			if (result.raw) {
				const tokenOverlay = document.createElement('div');
				tokenOverlay.className = 'hdat-modal-overlay';
				tokenOverlay.innerHTML = `<div class="hdat-modal">
					<h3>Token Created</h3>
					<p class="token-warning">Copy this token now — it won't be shown again.</p>
					<input type="text" value="${esc(result.raw)}" readonly class="token-display">
					<div class="hdat-modal-actions">
						<button class="btn-sm" id="copy-token">Copy</button>
						<button class="btn-primary" id="close-token">Close</button>
					</div>
				</div>`;
				document.body.appendChild(tokenOverlay);

				tokenOverlay.querySelector('#copy-token')!.addEventListener('click', () => {
					navigator.clipboard.writeText(result.raw);
					toast.success('Copied to clipboard');
				});
				tokenOverlay.querySelector('#close-token')!.addEventListener('click', () => {
					tokenOverlay.remove();
					onDone();
				});
			} else {
				toast.success('Token created');
				onDone();
			}
		} catch (err: any) {
			toast.error(err.message);
		}
	});
}

async function renderPage(el: HTMLElement): Promise<void> {
	el.innerHTML = '<div class="hdat-loading">Loading…</div>';

	try {
		const tokens = await api.tokens.list();

		el.innerHTML = `<div class="hdat-page">
			<div class="hdat-toolbar">
				<h2>Consumer Tokens</h2>
				<span class="badge">${tokens.length} total</span>
				<button class="btn-sm" id="token-add">+ Create</button>
			</div>
			${renderTable(tokens)}
		</div>`;

		el.querySelector('#token-add')?.addEventListener('click', () => showCreateModal(el, () => renderPage(el)));

		el.onclick = async (e: MouseEvent) => {
			const btn = (e.target as HTMLElement).closest<HTMLElement>('[data-revoke]');
			if (!btn) return;
			if (!confirm('Revoke this token?')) return;

			try {
				await api.tokens.revoke(Number(btn.dataset.revoke));
				toast.success('Token revoked');
				renderPage(el);
			} catch (err: any) {
				toast.error(err.message);
			}
		};
	} catch (err: any) {
		el.innerHTML = `<div class="hdat-page"><p class="error-message">Error: ${err.message}</p></div>`;
	}
}

export function createTokensPage(): Page {
	return { mount: renderPage };
}
