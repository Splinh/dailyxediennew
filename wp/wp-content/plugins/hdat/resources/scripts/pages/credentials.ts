/**
 * Credentials page — CRUD + test/health probe.
 */

import type { Page } from '../router';
import { api } from '../api/client';
import { toast } from '../components/toast';
import { renderProviderBadge, isCustomProvider } from '../components/provider-badge';

let providers: any[] = [];

function esc(s: string): string {
	const d = document.createElement('div');
	d.textContent = s;
	return d.innerHTML;
}

/**
 * Resolve a custom provider's display Name. The Name lives in
 * custom_provider_meta.custom_label and is independent of the credential Label.
 * Returns undefined for non-custom providers, or when no Name is stored — callers
 * then let the badge derive it from the `custom:<slug>` provider id.
 */
function customProviderName(c: any): string | undefined {
	if (!isCustomProvider(c.provider)) return undefined;
	let meta = c.custom_provider_meta;
	if (typeof meta === 'string') {
		try {
			meta = JSON.parse(meta);
		} catch {
			meta = null;
		}
	}
	const name = meta?.custom_label;
	return typeof name === 'string' && name.trim() ? name : undefined;
}

function tierBadge(tier: string): string {
	return tier === 'paid' ? '<span class="badge badge-green">Paid</span>' : '<span class="badge">Free</span>';
}

function modelBadge(model: string | null): string {
	if (!model) return '<span class="badge badge-gray">Auto</span>';
	const short = model.length > 30 ? '…' + model.slice(-28) : model;
	return `<span class="badge" title="${esc(model)}">${esc(short)}</span>`;
}

function renderTable(items: any[]): string {
	if (!items.length) {
		return '<div class="hdat-empty"><p>No credentials yet.</p></div>';
	}

	const rows = items
		.map((c) => {
			const customLabel = customProviderName(c);
			return `<tr>
			<td>${esc(c.label || c.provider)}</td>
			<td>${renderProviderBadge(c.provider, customLabel)}</td>
			<td><code>${esc(c.api_key_masked)}</code></td>
			<td>${tierBadge(c.tier)}</td>
			<td>${modelBadge(c.preferred_model)}</td>
			<td>${c.priority}</td>
			<td>${c.is_active ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-red">Inactive</span>'}</td>
			<td>
				<div class="btn-group">
					<button class="btn-xs" data-test="${c.id}">Test</button>
					<button class="btn-xs" data-edit="${c.id}">Edit</button>
					<button class="btn-xs btn-danger" data-del="${c.id}">Del</button>
				</div>
			</td>
		</tr>`;
		})
		.join('');

	return `<table class="hdat-table">
		<thead><tr>
			<th>Label</th><th>Provider</th><th>Key</th><th>Tier</th><th>Model</th><th>Priority</th><th>Status</th><th></th>
		</tr></thead>
		<tbody>${rows}</tbody>
	</table>`;
}

function providerOptions(): string {
	const builtInOptions = providers.map((p) => `<option value="${esc(p.id)}">${esc(p.label)}</option>`).join('');
	const customOption = '<option value="custom">Custom Provider</option>';
	return builtInOptions + customOption;
}

function showModal(el: HTMLElement, credential?: any): void {
	const isEdit = !!credential;
	const c = credential ?? {};
	const isCustom = isCustomProvider(c.provider || '');

	const overlay = document.createElement('div');
	overlay.className = 'hdat-modal-overlay';
	overlay.innerHTML = `<div class="hdat-modal">
		<h3>${isEdit ? 'Edit' : 'Add'} Credential</h3>
		<form class="hdat-form" id="cred-form">
			<label>Provider <select name="provider">${providerOptions()}</select></label>

			<!-- Custom Provider Form Fields (hidden by default) -->
			<div id="custom-provider-fields" style="display: none;">
				<div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px; padding: 12px; margin-bottom: 16px;">
					<p style="margin: 0 0 8px 0; font-weight: 500; color: #0369a1;">Custom Provider Configuration</p>
					<p style="margin: 0; font-size: 0.875rem; color: #0c4a6e;">Configure your custom LLM provider by specifying the API format and endpoint details.</p>
				</div>

				<label><span>Custom Provider Name <span style="color: #dc2626;">*</span></span>
					<input type="text" name="custom_label" placeholder="e.g., My Local LLM, Acme AI API" value="${esc(c.custom_label ?? '')}">
				</label>
				<small class="text-muted" style="display: block; margin-top: -6px; margin-bottom: 10px;">A friendly name to identify this custom provider</small>

				<label><span>API Format <span style="color: #dc2626;">*</span></span></label>
				<div style="margin-bottom: 16px;">
					<label style="display: flex; flex-flow: row nowrap; align-items: center; margin-bottom: 8px; cursor: pointer;">
						<input type="radio" name="api_format" value="openai_compatible" checked style="margin-right: 8px;">
						<span>OpenAI Compatible (/v1/chat/completions)</span>
					</label>
					<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer;">
						<input type="radio" name="api_format" value="anthropic_messages" style="margin-right: 8px;">
						<span>Anthropic Messages (/v1/messages)</span>
					</label>
				</div>

				<label><span>Base URL <span style="color: #dc2626;">*</span></span>
					<input type="text" name="custom_base_url" placeholder="https://api.example.com/v1" value="${esc(c.custom_base_url ?? '')}">
				</label>
				<small class="text-muted" id="custom-base-url-hint" style="display: block; margin-top: -6px; margin-bottom: 10px;">OpenAI: nhập kèm version path (vd <code>/v1</code>) — hệ thống tự thêm <code>/chat/completions</code>.</small>

				<label><span>Models Endpoint (optional)</span>
					<input type="text" name="models_url" placeholder="https://api.example.com/v1/models" value="${esc(c.models_url ?? '')}">
				</label>
				<small class="text-muted" style="display: block; margin-top: -6px; margin-bottom: 10px;">Leave empty to manually enter model IDs</small>

				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
					<div>
						<label>Auth Header Name
							<input type="text" name="auth_header_name" placeholder="Authorization" value="${esc(c.auth_header_name ?? 'Authorization')}">
						</label>
					</div>
					<div>
						<label>Auth Header Prefix
							<input type="text" name="auth_header_prefix" placeholder="Bearer" value="${esc(c.auth_header_prefix ?? 'Bearer')}">
						</label>
					</div>
				</div>

				<label style="margin-bottom: 12px;">Capabilities</label>
				<div style="display: grid; grid-template-columns: repeat(2, 1fr); column-gap: 8px;">
					<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer;">
						<input type="checkbox" name="capability_chat" checked style="margin-right: 8px;">
						<span>Chat</span>
					</label>
					<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer;">
						<input type="checkbox" name="capability_vision" style="margin-right: 8px;">
						<span>Vision</span>
					</label>
					<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer;">
						<input type="checkbox" name="capability_function_call" style="margin-right: 8px;">
						<span>Function Calling</span>
					</label>
					<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer;">
						<input type="checkbox" name="capability_tool_use" style="margin-right: 8px;">
						<span>Tool Use</span>
					</label>
					<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer;">
						<input type="checkbox" name="capability_embedding" style="margin-right: 8px;">
						<span>Embeddings</span>
					</label>
					<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer;">
						<input type="checkbox" name="capability_image" style="margin-right: 8px;">
						<span>Image Generation</span>
					</label>
				</div>

				<label style="display: flex; flex-flow: row nowrap; align-items: center; cursor: pointer; margin-bottom: 16px;">
					<input type="checkbox" name="supports_live_models" style="margin-right: 8px;">
					<span>Supports Live Model Fetching</span>
				</label>
				<small class="text-muted" style="display: block; margin-top: -12px; margin-bottom: 10px;">Check if models_url returns a valid model list</small>

				<button type="button" class="btn-sm" id="validate-custom-provider" style="width: 100%; margin-bottom: 16px;">
					Validate Configuration
				</button>
				<div id="validation-result" style="display: none; padding: 10px; border-radius: 6px; margin-bottom: 16px;"></div>
			</div>

			<!-- Standard Fields -->
			<label>Label <input type="text" name="label" value="${esc(c.label ?? '')}"></label>
			<label>API Key <input type="text" name="api_key" value="" placeholder="${isEdit ? '(unchanged)' : ''}"></label>
			<small class="text-muted" id="api-key-hint" style="display: none; margin-top: -6px; margin-bottom: 10px;"></small>
			<label id="base-url-row">Base URL (optional) <input type="text" name="base_url" value="${esc(c.base_url ?? '')}"></label>
			<label>Tier <select name="tier"><option value="free">Free</option><option value="paid"${c.tier === 'paid' ? ' selected' : ''}>Paid</option></select></label>
			<div id="preferred-model-row">
				<label>Preferred Model (optional)
					<div id="preferred-model-container"></div>
				</label>
				<small class="text-muted" id="preferred-model-hint"></small>
			</div>
			<label>Priority <input type="number" name="priority" value="${c.priority ?? 5}" min="1" max="10"></label>
			<div class="toggle-row"><span>Active</span><label class="toggle"><input type="checkbox" name="is_active"${c.is_active !== false ? ' checked' : ''}><span></span></label></div>
			<div class="hdat-modal-actions">
				<button type="button" class="btn-sm" id="modal-cancel">Cancel</button>
				<button type="submit" class="btn-primary">${isEdit ? 'Update' : 'Create'}</button>
			</div>
		</form>
	</div>`;

	document.body.appendChild(overlay);

	const sel = overlay.querySelector<HTMLSelectElement>('select[name="provider"]');
	const baseUrlInput = overlay.querySelector<HTMLInputElement>('input[name="base_url"]');
	const baseUrlRow = overlay.querySelector<HTMLElement>('#base-url-row');
	const tierSel = overlay.querySelector<HTMLSelectElement>('select[name="tier"]');
	const modelContainer = overlay.querySelector<HTMLElement>('#preferred-model-container');
	const modelHint = overlay.querySelector<HTMLElement>('#preferred-model-hint');
	const apiKeyHint = overlay.querySelector<HTMLElement>('#api-key-hint');
	const customProviderFields = overlay.querySelector<HTMLElement>('#custom-provider-fields');
	const validateBtn = overlay.querySelector<HTMLButtonElement>('#validate-custom-provider');
	const validationResult = overlay.querySelector<HTMLElement>('#validation-result');

	let orModelsCache: any[] | null = null;
	let orFreeModelsCache: any[] | null = null;

	// Toggle custom provider fields visibility
	const toggleCustomProviderFields = (show: boolean) => {
		if (customProviderFields) {
			customProviderFields.style.display = show ? 'block' : 'none';
		}
		if (baseUrlRow) {
			baseUrlRow.style.display = show ? 'none' : 'block';
		}
	};

	// Validate custom provider configuration
	const validateCustomProvider = async () => {
		if (!validateBtn || !validationResult) return;

		const customBaseUrl = overlay.querySelector<HTMLInputElement>('input[name="custom_base_url"]')?.value.trim();
		const apiKey = overlay.querySelector<HTMLInputElement>('input[name="api_key"]')?.value.trim();
		const apiFormat = overlay.querySelector<HTMLInputElement>('input[name="api_format"]:checked')?.value as 'openai_compatible' | 'anthropic_messages';
		const modelsUrl = overlay.querySelector<HTMLInputElement>('input[name="models_url"]')?.value.trim();
		const authHeaderName = overlay.querySelector<HTMLInputElement>('input[name="auth_header_name"]')?.value.trim() || 'Authorization';
		const authHeaderPrefix = overlay.querySelector<HTMLInputElement>('input[name="auth_header_prefix"]')?.value.trim() || 'Bearer';

		// Validation
		if (!customBaseUrl) {
			validationResult.innerHTML = '<span style="color: #dc2626;">❌ Base URL is required</span>';
			validationResult.style.display = 'block';
			validationResult.style.background = '#fee2e2';
			validationResult.style.border = '1px solid #fca5a5';
			return;
		}

		// On edit, an empty key means "reuse the stored key" — only require a key
		// when adding a new credential (no credential_id to fall back on).
		const reuseStoredKey = isEdit && !!c.id && !apiKey;
		if (!apiKey && !reuseStoredKey) {
			validationResult.innerHTML = '<span style="color: #dc2626;">❌ API Key is required for validation</span>';
			validationResult.style.display = 'block';
			validationResult.style.background = '#fee2e2';
			validationResult.style.border = '1px solid #fca5a5';
			return;
		}

		// URL format validation
		try {
			new URL(customBaseUrl);
		} catch {
			validationResult.innerHTML = '<span style="color: #dc2626;">❌ Invalid URL format</span>';
			validationResult.style.display = 'block';
			validationResult.style.background = '#fee2e2';
			validationResult.style.border = '1px solid #fca5a5';
			return;
		}

		validateBtn.disabled = true;
		validateBtn.textContent = 'Validating...';

		try {
			const result = await api.providers.validateCustom({
				api_format: apiFormat,
				base_url: customBaseUrl,
				...(apiKey ? { api_key: apiKey } : {}),
				...(reuseStoredKey ? { credential_id: c.id } : {}),
				models_url: modelsUrl || undefined,
				auth_header_name: authHeaderName,
				auth_header_prefix: authHeaderPrefix,
			});

			if (result.valid) {
				let message = `Valid ${result.detected_format || apiFormat} API`;
				if (result.sample_models && result.sample_models.length > 0) {
					message += ` — Found ${result.sample_models.length} models`;
				}
				validationResult.innerHTML = `<span style="color: #16a34a;">${message}</span>`;
				validationResult.style.background = '#dcfce7';
				validationResult.style.border = '1px solid #86efac';
			} else {
				validationResult.innerHTML = `<span style="color: #dc2626;">❌ ${result.error || 'Validation failed'}</span>`;
				validationResult.style.background = '#fee2e2';
				validationResult.style.border = '1px solid #fca5a5';
			}
			validationResult.style.display = 'block';
		} catch (err: any) {
			validationResult.innerHTML = `<span style="color: #dc2626;">❌ ${err.message || 'Validation failed'}</span>`;
			validationResult.style.display = 'block';
			validationResult.style.background = '#fee2e2';
			validationResult.style.border = '1px solid #fca5a5';
		} finally {
			validateBtn.disabled = false;
			validateBtn.textContent = 'Validate Configuration';
		}
	};

	// Bind validation button
	if (validateBtn) {
		validateBtn.addEventListener('click', validateCustomProvider);
	}

	// Keep the Base URL hint/placeholder in sync with the selected API format.
	// OpenAI-compat expects the version path in the base (code appends
	// /chat/completions); Anthropic expects the root (code appends /v1/messages).
	const baseUrlHint = overlay.querySelector<HTMLElement>('#custom-base-url-hint');
	const customBaseUrlField = overlay.querySelector<HTMLInputElement>('input[name="custom_base_url"]');
	const syncBaseUrlHint = () => {
		const fmt = overlay.querySelector<HTMLInputElement>('input[name="api_format"]:checked')?.value;
		if (fmt === 'anthropic_messages') {
			if (baseUrlHint) baseUrlHint.innerHTML = 'Anthropic: nhập domain gốc (vd <code>https://api.example.com</code>) — hệ thống tự thêm <code>/v1/messages</code>.';
			if (customBaseUrlField) customBaseUrlField.placeholder = 'https://api.example.com';
		} else {
			if (baseUrlHint) baseUrlHint.innerHTML = 'OpenAI: nhập kèm version path (vd <code>/v1</code>) — hệ thống tự thêm <code>/chat/completions</code>.';
			if (customBaseUrlField) customBaseUrlField.placeholder = 'https://api.example.com/v1';
		}
	};
	overlay.querySelectorAll<HTMLInputElement>('input[name="api_format"]').forEach((radio) => {
		radio.addEventListener('change', syncBaseUrlHint);
	});
	syncBaseUrlHint();

	// A custom provider's models_url is its equivalent of static models: gaining or
	// losing it flips whether Preferred Model is selectable on the free tier. Re-run
	// the model loader when the field changes so the row shows/hides accordingly.
	overlay.querySelector<HTMLInputElement>('input[name="models_url"]')?.addEventListener('change', () => {
		if (sel?.value === 'custom') {
			loadModelOptions('custom', tierSel?.value ?? 'free');
		}
	});

	// Initialize custom provider fields if editing a custom provider
	if (isCustomProvider(c.provider || '')) {
		toggleCustomProviderFields(true);

		const customLabelInput = overlay.querySelector<HTMLInputElement>('input[name="custom_label"]');
		const customBaseUrlInput = overlay.querySelector<HTMLInputElement>('input[name="custom_base_url"]');

		// Base URL lives in the DB `base_url` column, independent of the meta blob.
		// Populate it unconditionally — a credential may have a base_url even when
		// custom_provider_meta is missing (legacy rows saved before the meta fix).
		if (customBaseUrlInput) customBaseUrlInput.value = c.base_url || '';

		// Custom Provider Name lives in custom_provider_meta.custom_label (set below).
		// Seed it from the credential label only as a legacy fallback for rows saved
		// before Name/Label were stored independently — meta overrides it when present.
		if (customLabelInput) customLabelInput.value = c.label || '';

		// Populate the rest from custom_provider_meta. Backend writes snake_case
		// (CustomProviderMeta::toArray()); also accept camelCase for legacy rows.
		if (c.custom_provider_meta) {
			const meta = typeof c.custom_provider_meta === 'string' ? JSON.parse(c.custom_provider_meta) : c.custom_provider_meta;
			const pick = (snake: string, camel: string) => meta[snake] ?? meta[camel];

			const customLabel = pick('custom_label', 'customLabel');
			if (customLabelInput && customLabel) customLabelInput.value = customLabel;

			// Legacy rows stored base_url inside the meta blob — use it as a fallback
			// when the column is empty.
			const metaBaseUrl = pick('base_url', 'baseUrl');
			if (customBaseUrlInput && !customBaseUrlInput.value && metaBaseUrl) customBaseUrlInput.value = metaBaseUrl;

			const apiFormat = pick('api_format', 'apiFormat');
			const apiFormatRadio = overlay.querySelector<HTMLInputElement>(`input[name="api_format"][value="${apiFormat}"]`);
			if (apiFormatRadio) apiFormatRadio.checked = true;

			const modelsUrlInput = overlay.querySelector<HTMLInputElement>('input[name="models_url"]');
			if (modelsUrlInput) modelsUrlInput.value = pick('models_url', 'modelsUrl') || '';

			const authHeaderNameInput = overlay.querySelector<HTMLInputElement>('input[name="auth_header_name"]');
			if (authHeaderNameInput) authHeaderNameInput.value = pick('auth_header_name', 'authHeaderName') || 'Authorization';

			const authHeaderPrefixInput = overlay.querySelector<HTMLInputElement>('input[name="auth_header_prefix"]');
			if (authHeaderPrefixInput) authHeaderPrefixInput.value = pick('auth_header_prefix', 'authHeaderPrefix') || 'Bearer';

			const supportsLiveModelsCheckbox = overlay.querySelector<HTMLInputElement>('input[name="supports_live_models"]');
			if (supportsLiveModelsCheckbox) supportsLiveModelsCheckbox.checked = pick('supports_live_models', 'supportsLiveModels') || false;

			// Populate capabilities
			if (Array.isArray(meta.capabilities)) {
				meta.capabilities.forEach((cap: string) => {
					const checkbox = overlay.querySelector<HTMLInputElement>(`input[name="capability_${cap}"]`);
					if (checkbox) checkbox.checked = true;
				});
			}
		}

		// Radio was set programmatically (no change event) — refresh the hint.
		syncBaseUrlHint();
	}

	const updateBaseUrl = (providerId: string) => {
		const p = providers.find((x) => x.id === providerId);
		if (baseUrlInput) {
			baseUrlInput.value = p?.base_url ?? '';
		}
	};

	const updateApiKeyHint = (providerId: string) => {
		const p = providers.find((x) => x.id === providerId);
		if (apiKeyHint) {
			if (p?.reg_url) {
				apiKeyHint.innerHTML = `Lấy API Key tại: <a href="${esc(p.reg_url)}" target="_blank" rel="noopener noreferrer" style="color: #2271b1; text-decoration: underline;">${esc(p.label)} Dashboard ↗</a>`;
				apiKeyHint.style.display = 'block';
			} else {
				apiKeyHint.innerHTML = '';
				apiKeyHint.style.display = 'none';
			}
		}
	};

	const modelRow = overlay.querySelector<HTMLElement>('#preferred-model-row');
	const apiKeyInput = overlay.querySelector<HTMLInputElement>('input[name="api_key"]');

	/** Cache live-fetched models keyed by provider id to avoid refetching on tier toggle. */
	const liveModelsCache: Record<string, any[]> = {};

	const loadModelOptions = async (providerId: string, tier: string) => {
		if (!modelContainer || !modelRow) return;

		if (providerId === 'custom') {
			// Custom providers fetch their model list from the configured models_url
			// (when present). Without it, fall back to manual model-id entry.
			const modelsUrl = overlay.querySelector<HTMLInputElement>('input[name="models_url"]')?.value.trim() ?? '';
			const current = c.preferred_model || '';
			const manualInput = () => {
				modelContainer.innerHTML = `<input type="text" name="preferred_model" value="${esc(current)}" placeholder="e.g. gpt-4o, claude-sonnet-4">`;
			};

			modelRow.style.display = '';

			if (!modelsUrl) {
				manualInput();
				if (modelHint) modelHint.textContent = 'Chưa có Models Endpoint — nhập model id thủ công.';
				return;
			}

			const apiFormat = (overlay.querySelector<HTMLInputElement>('input[name="api_format"]:checked')?.value ?? 'openai_compatible') as
				| 'openai_compatible'
				| 'anthropic_messages';
			const params: Parameters<typeof api.providers.models>[1] = {
				models_url: modelsUrl,
				api_format: apiFormat,
				auth_header_name: overlay.querySelector<HTMLInputElement>('input[name="auth_header_name"]')?.value.trim() || 'Authorization',
				auth_header_prefix: overlay.querySelector<HTMLInputElement>('input[name="auth_header_prefix"]')?.value.trim() || 'Bearer',
			};
			// Auth: raw key (add flow / changed key) wins; else reuse stored key.
			const rawKey = apiKeyInput?.value?.trim() ?? '';
			if (rawKey) {
				params.api_key = rawKey;
			} else if (isEdit && c.id) {
				params.credential_id = c.id;
			}

			modelContainer.innerHTML = '<span class="text-muted">Đang tải danh sách model…</span>';
			if (modelHint) modelHint.textContent = '';

			try {
				const res = await api.providers.models('custom', params);
				const models = res.models ?? [];
				if (models.length === 0) {
					manualInput();
					if (modelHint) modelHint.textContent = res.message || 'Không tìm thấy model nào — nhập thủ công.';
				} else {
					renderModelSelect(models, current);
					if (modelHint) modelHint.textContent = 'Chọn model ưu tiên. Để trống = do request quyết định.';
				}
			} catch (err: any) {
				manualInput();
				if (modelHint) modelHint.textContent = 'Không load được model: ' + (err.message || '');
			}
			return;
		}

		if (providerId === 'openrouter') {
			// OpenRouter: always show — dynamic select from cached model list.
			modelRow.style.display = '';

			let models: any[];
			if (tier === 'paid') {
				if (!orModelsCache) {
					try {
						orModelsCache = await api.openrouter.allModels();
					} catch {
						orModelsCache = [];
					}
				}
				models = orModelsCache;
				if (modelHint) modelHint.textContent = 'Paid: model ưu tiên. Fallback → Pool → openrouter/auto';
			} else {
				if (!orFreeModelsCache) {
					try {
						orFreeModelsCache = await api.openrouter.models();
					} catch {
						orFreeModelsCache = [];
					}
				}
				models = orFreeModelsCache;
				if (modelHint) modelHint.textContent = 'Free: model ưu tiên. Fallback → Pool → openrouter/free';
			}

			const current = c.preferred_model || '';
			let options =
				'<option value="">(Auto — use Pool or fallback)</option>' +
				models.map((m: any) => `<option value="${esc(m.id)}"${m.id === current ? ' selected' : ''}>${esc(m.name || m.id)}</option>`).join('');

			if (current && !models.find((m: any) => m.id === current)) {
				options += `<option value="${esc(current)}" selected>${esc(current)} (cached)</option>`;
			}

			modelContainer.innerHTML = `<select name="preferred_model">${options}</select>`;

			if (models.length === 0 && modelHint) {
				modelHint.textContent += ' — No models cached. Run Sync on the OpenRouter tab first.';
			}
		} else if (tier === 'paid') {
			// Non-OpenRouter + Paid: live fetch from provider API → select box.
			modelRow.style.display = '';

			// Determine auth params: edit uses credential_id, add uses raw api_key.
			const params: any = {};
			if (isEdit && c.id) {
				params.credential_id = c.id;
			} else {
				const rawKey = apiKeyInput?.value?.trim() ?? '';
				if (!rawKey) {
					// No key yet → show placeholder input.
					const current = c.preferred_model || '';
					modelContainer.innerHTML = `<input type="text" name="preferred_model" value="${esc(current)}" placeholder="e.g. claude-sonnet-4-20250514">`;
					if (modelHint) modelHint.textContent = 'Nhập API Key trước để load danh sách model.';
					return;
				}
				params.api_key = rawKey;
				const baseVal = baseUrlInput?.value?.trim() ?? '';
				if (baseVal) params.base_url = baseVal;
			}

			// Check cache first.
			const cacheKey = providerId + ':' + (params.credential_id ?? params.api_key);
			if (liveModelsCache[cacheKey]) {
				renderModelSelect(liveModelsCache[cacheKey], c.preferred_model || '');
				if (modelHint) modelHint.textContent = 'Chọn model ưu tiên. Để trống = mặc định.';
				return;
			}

			// Loading state.
			modelContainer.innerHTML = '<span class="text-muted">Đang tải danh sách model…</span>';
			if (modelHint) modelHint.textContent = '';

			try {
				const res = await api.providers.models(providerId, params);
				const models = res.models ?? [];
				liveModelsCache[cacheKey] = models;

				renderModelSelect(models, c.preferred_model || '');

				if (models.length === 0 && modelHint) {
					modelHint.textContent = res.message || 'Không tìm thấy model nào.';
				} else if (modelHint) {
					modelHint.textContent = 'Chọn model ưu tiên. Để trống = mặc định.';
				}
			} catch (err: any) {
				const current = c.preferred_model || '';
				modelContainer.innerHTML = `<input type="text" name="preferred_model" value="${esc(current)}" placeholder="e.g. claude-sonnet-4-20250514">`;
				if (modelHint) modelHint.textContent = 'Không load được model: ' + (err.message || '');
			}
		} else {
			// Non-OpenRouter + Free: live fetch isn't available, but a provider may
			// ship a static model list (ProviderMeta::staticModels, exposed as
			// `models` on the provider record). Show a select when it does; otherwise
			// hide Preferred Model entirely (nothing to choose from).
			const p = providers.find((x) => x.id === providerId);
			const staticModels: any[] = Array.isArray(p?.models) ? p.models : [];

			if (staticModels.length > 0) {
				modelRow.style.display = '';
				renderModelSelect(staticModels, c.preferred_model || '');
				if (modelHint) modelHint.textContent = 'Chọn model ưu tiên. Để trống = mặc định.';
			} else {
				modelRow.style.display = 'none';
				modelContainer.innerHTML = '';
				if (modelHint) modelHint.textContent = '';
			}
		}
	};

	/** Render a <select> dropdown from a model list. */
	const renderModelSelect = (models: any[], current: string) => {
		let options = '<option value="">(Mặc định)</option>' + models.map((m: any) => `<option value="${esc(m.id)}"${m.id === current ? ' selected' : ''}>${esc(m.name || m.id)}</option>`).join('');

		if (current && !models.find((m: any) => m.id === current)) {
			options += `<option value="${esc(current)}" selected>${esc(current)} (saved)</option>`;
		}

		modelContainer!.innerHTML = `<select name="preferred_model">${options}</select>`;
	};

	if (sel) {
		// Custom providers store their id as `custom:<slug>`, but the dropdown
		// option value is just `custom`. Map it back so the select resolves.
		if (c.provider) sel.value = isCustomProvider(c.provider) ? 'custom' : c.provider;

		sel.addEventListener('change', () => {
			const isCustomSelected = sel.value === 'custom';
			toggleCustomProviderFields(isCustomSelected);

			if (!isCustomSelected) {
				updateBaseUrl(sel.value);
				updateApiKeyHint(sel.value);
				loadModelOptions(sel.value, tierSel?.value ?? 'free');
			} else {
				// Switching to custom: show the manual model input and clear stale
				// validation feedback.
				loadModelOptions('custom', tierSel?.value ?? 'free');
				if (validationResult) {
					validationResult.style.display = 'none';
				}
			}
		});

		if (!isEdit && baseUrlInput && '' === baseUrlInput.value.trim()) {
			updateBaseUrl(sel.value);
		}

		updateApiKeyHint(sel.value);
		loadModelOptions(sel.value, tierSel?.value ?? c.tier ?? 'free');
	}

	if (tierSel) {
		tierSel.addEventListener('change', () => {
			loadModelOptions(sel?.value ?? '', tierSel.value);
		});
	}

	// When user finishes typing API key (add-new flow), reload models.
	if (apiKeyInput && !isEdit) {
		apiKeyInput.addEventListener('blur', () => {
			const prov = sel?.value ?? '';
			const tier = tierSel?.value ?? 'free';
			if (prov !== 'openrouter' && tier === 'paid' && apiKeyInput.value.trim()) {
				loadModelOptions(prov, tier);
			}
		});
	}

	overlay.querySelector('#modal-cancel')!.addEventListener('click', () => overlay.remove());
	overlay.querySelector('#cred-form')!.addEventListener('submit', async (e) => {
		e.preventDefault();
		const fd = new FormData(e.target as HTMLFormElement);
		const provider = fd.get('provider') as string;
		const isCustom = provider === 'custom';
		const label = ((fd.get('label') as string) || '').trim();
		const key = ((fd.get('api_key') as string) || '').trim();

		// Validation
		if (isCustom) {
			const customLabel = ((fd.get('custom_label') as string) || '').trim();
			const customBaseUrl = ((fd.get('custom_base_url') as string) || '').trim();
			const apiFormat = fd.get('api_format') as string;

			if (!customLabel) {
				toast.error('Custom Provider Name is required');
				return;
			}

			if (!customBaseUrl) {
				toast.error('Base URL is required for custom providers');
				return;
			}

			if (!apiFormat) {
				toast.error('API Format is required');
				return;
			}

			// Validate URL format
			try {
				new URL(customBaseUrl);
			} catch {
				toast.error('Invalid Base URL format');
				return;
			}
		} else {
			if (!label) {
				toast.error('Label is required');
				return;
			}
		}

		if (!isEdit && !key) {
			toast.error('API Key is required');
			return;
		}

		const data: any = {
			provider: isCustom ? 'custom' : provider,
			// Label is an independent field for every provider, custom included. The
			// backend canonicalizes the custom provider id from Name. Label only feeds
			// the display column, so fall back to Name to keep the column non-empty.
			label: isCustom ? label || ((fd.get('custom_label') as string) || '').trim() : label,
			tier: fd.get('tier'),
			priority: Number(fd.get('priority')),
			is_active: !!(e.target as HTMLFormElement).querySelector<HTMLInputElement>('[name="is_active"]')?.checked,
			preferred_model: fd.get('preferred_model') || null,
		};

		// Handle custom provider metadata
		if (isCustom) {
			const capabilities: string[] = [];
			if ((e.target as HTMLFormElement).querySelector<HTMLInputElement>('[name="capability_chat"]')?.checked) capabilities.push('chat');
			if ((e.target as HTMLFormElement).querySelector<HTMLInputElement>('[name="capability_vision"]')?.checked) capabilities.push('vision');
			if ((e.target as HTMLFormElement).querySelector<HTMLInputElement>('[name="capability_function_call"]')?.checked) capabilities.push('function_call');
			if ((e.target as HTMLFormElement).querySelector<HTMLInputElement>('[name="capability_tool_use"]')?.checked) capabilities.push('tool_use');
			if ((e.target as HTMLFormElement).querySelector<HTMLInputElement>('[name="capability_embedding"]')?.checked) capabilities.push('embedding');
			if ((e.target as HTMLFormElement).querySelector<HTMLInputElement>('[name="capability_image"]')?.checked) capabilities.push('image');

			// Serialize in snake_case to match CustomProviderMeta::fromArray()/toArray()
			// — the canonical on-disk format. base_url is NOT part of the meta blob.
			data.custom_provider_meta = JSON.stringify({
				api_format: fd.get('api_format'),
				custom_label: ((fd.get('custom_label') as string) || '').trim(),
				models_url: ((fd.get('models_url') as string) || '').trim() || null,
				auth_header_name: ((fd.get('auth_header_name') as string) || '').trim() || 'Authorization',
				auth_header_prefix: ((fd.get('auth_header_prefix') as string) || '').trim() || 'Bearer',
				capabilities,
				supports_live_models: !!(e.target as HTMLFormElement).querySelector<HTMLInputElement>('[name="supports_live_models"]')?.checked,
			});

			// Base URL is persisted in the `base_url` column (CustomProvider reads
			// $cred->baseUrl), not inside custom_provider_meta.
			data.base_url = ((fd.get('custom_base_url') as string) || '').trim() || null;
		} else {
			data.base_url = ((fd.get('base_url') as string) || '').trim() || null;
		}

		if (key) {
			data.api_key = key;
		}

		try {
			if (isEdit) {
				await api.credentials.update(c.id, data);
				toast.success('Credential updated');
			} else {
				data.api_key = key;
				await api.credentials.create(data);
				toast.success('Credential created');
			}
			overlay.remove();
			renderPage(el);
		} catch (err: any) {
			toast.error(err.message);
		}
	});
}

let currentPage = 1;
let forcedProviderId: number | null = null;

function renderForceProviderSection(items: any[]): string {
	const activeItems = items.filter((c) => c.is_active);

	if (activeItems.length === 0) {
		return '';
	}

	const isForceMode = forcedProviderId !== null;
	const forcedCred = isForceMode ? activeItems.find((c) => c.id === forcedProviderId) : null;

	const options = [
		'<option value="">Auto (All Providers)</option>',
		...activeItems.map((c) => {
			const customLabel = customProviderName(c);
			const label = c.label || c.provider;
			const selected = c.id === forcedProviderId ? ' selected' : '';
			return `<option value="${c.id}"${selected}>${esc(label)} (${esc(customLabel || c.provider)})</option>`;
		}),
	].join('');

	const statusBadge = isForceMode ? '<span class="badge badge-orange" style="margin-left: 8px;">FORCE MODE ACTIVE</span>' : '';

	const infoMessage =
		isForceMode && forcedCred
			? `Only "${esc(forcedCred.label || forcedCred.provider)}" is active. No fallback to other providers.`
			: 'When set, only the selected provider will be used (no fallback). Errors will be shown to users.';

	return `
		<div style="background: ${isForceMode ? '#fff7ed' : '#f0f9ff'}; border: 1px solid ${isForceMode ? '#fed7aa' : '#bae6fd'}; border-radius: 6px; padding: 16px; margin-bottom: 20px;">
			<div style="display: flex; align-items: center; margin-bottom: 12px;">
				<label style="margin: 0; font-weight: 600; color: ${isForceMode ? '#9a3412' : '#0369a1'}; display: flex; align-items: center;">
					Force Single Provider:
					${statusBadge}
				</label>
			</div>
			<select id="force-provider-select" style="width: 100%; max-width: 400px; margin-bottom: 8px;">
				${options}
			</select>
			<p style="margin: 8px 0 0 0; font-size: 0.875rem; color: ${isForceMode ? '#9a3412' : '#0c4a6e'};">
				${infoMessage}
			</p>
		</div>
	`;
}

async function renderPage(el: HTMLElement): Promise<void> {
	el.innerHTML = '<div class="hdat-loading">Loading…</div>';

	try {
		if (!providers.length) {
			providers = await api.providers.list();
		}

		// Load forced provider setting
		const forceResult = await api.forceProvider.get();
		forcedProviderId = forceResult.credential_id;

		const result = await api.credentials.list(currentPage);
		const items = result.items ?? [];
		const total = result.total ?? 0;
		const pages = result.pages ?? 1;

		el.innerHTML = `<div class="hdat-page">
			<div class="hdat-toolbar">
				<h2>Credentials</h2>
				<span class="badge">${total} total</span>
				<button class="btn-sm" id="cred-add">+ Add</button>
			</div>
			${renderForceProviderSection(items)}
			${renderTable(items)}
			${
				pages > 1
					? `<div class="hdat-pagination">
				<button id="pg-prev"${currentPage <= 1 ? ' disabled' : ''}>← Prev</button>
				<span>Page ${currentPage} / ${pages}</span>
				<button id="pg-next"${currentPage >= pages ? ' disabled' : ''}>Next →</button>
			</div>`
					: ''
			}
		</div>`;

		// Bind force provider select
		const forceSelect = el.querySelector<HTMLSelectElement>('#force-provider-select');
		if (forceSelect) {
			forceSelect.addEventListener('change', async () => {
				const value = forceSelect.value;
				try {
					if (value === '') {
						await api.forceProvider.clear();
						toast.success('Force mode disabled');
					} else {
						await api.forceProvider.set(Number(value));
						toast.success('Force mode enabled');
					}
					renderPage(el);
				} catch (err: any) {
					toast.error(err.message);
					renderPage(el);
				}
			});
		}

		// Bind events.
		el.querySelector('#cred-add')?.addEventListener('click', () => showModal(el));

		el.querySelector('#pg-prev')?.addEventListener('click', () => {
			if (currentPage > 1) {
				currentPage--;
				renderPage(el);
			}
		});
		el.querySelector('#pg-next')?.addEventListener('click', () => {
			if (currentPage < pages) {
				currentPage++;
				renderPage(el);
			}
		});

		// Use onclick assignment (not addEventListener) to avoid duplicate handlers on re-render.
		el.onclick = async (e: MouseEvent) => {
			const target = e.target as HTMLElement;

			const testBtn = target.closest<HTMLElement>('[data-test]');
			if (testBtn) {
				testBtn.textContent = '…';
				try {
					const res = await api.credentials.test(Number(testBtn.dataset.test));
					toast[res.ok ? 'success' : 'error'](res.ok ? `OK (${res.latency_ms}ms)` : res.error);
				} catch (err: any) {
					toast.error(err.message);
				}
				testBtn.textContent = 'Test';
				return;
			}

			const editBtn = target.closest<HTMLElement>('[data-edit]');
			if (editBtn) {
				const cred = items.find((c: any) => c.id === Number(editBtn.dataset.edit));
				if (cred) showModal(el, cred);
				return;
			}

			const delBtn = target.closest<HTMLElement>('[data-del]');
			if (delBtn) {
				if (!confirm('Delete this credential?')) return;
				try {
					await api.credentials.delete(Number(delBtn.dataset.del));
					toast.success('Deleted');
					renderPage(el);
				} catch (err: any) {
					toast.error(err.message);
				}
			}
		};
	} catch (err: any) {
		el.innerHTML = `<div class="hdat-page"><p class="error-message">Error: ${err.message}</p></div>`;
	}
}

export function createCredentialsPage(): Page {
	currentPage = 1;
	return { mount: renderPage };
}
