/**
 * Typed REST API client for HDAT admin.
 *
 * Reads restUrl + nonce from the `hdatAdmin` global localized by PHP.
 */

declare const hdatAdmin: { restUrl: string; nonce: string };

async function apiFetch<T>(path: string, init: RequestInit = {}): Promise<T> {
	const res = await fetch(hdatAdmin.restUrl + path, {
		...init,
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': hdatAdmin.nonce,
			...(init.headers as Record<string, string>),
		},
	});

	if (!res.ok) {
		const body = await res.json().catch(() => ({}));
		throw new Error(body.message ?? `HTTP ${res.status}`);
	}

	return res.json();
}

export const api = {
	dashboard: {
		stats: () => apiFetch<any>('/admin/dashboard'),
	},

	credentials: {
		list: (page = 1, perPage = 20) => apiFetch<any>(`/admin/credentials?page=${page}&per_page=${perPage}`),
		create: (data: any) => apiFetch<any>('/admin/credentials', { method: 'POST', body: JSON.stringify(data) }),
		update: (id: number, data: any) => apiFetch<any>(`/admin/credentials/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
		delete: (id: number) => apiFetch<void>(`/admin/credentials/${id}`, { method: 'DELETE' }),
		test: (id: number) => apiFetch<any>(`/admin/credentials/${id}/test`, { method: 'POST' }),
		health: (id: number) => apiFetch<any>(`/admin/credentials/${id}/health`, { method: 'POST' }),
	},

	tokens: {
		list: () => apiFetch<any[]>('/admin/tokens'),
		create: (data: any) => apiFetch<any>('/admin/tokens', { method: 'POST', body: JSON.stringify(data) }),
		update: (id: number, data: any) => apiFetch<any>(`/admin/tokens/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
		revoke: (id: number) => apiFetch<any>(`/admin/tokens/${id}`, { method: 'DELETE' }),
	},

	openrouter: {
		models: () => apiFetch<any[]>('/admin/openrouter/models'),
		allModels: () => apiFetch<any[]>('/admin/openrouter/all-models'),
		rateLimits: () => apiFetch<Record<string, any>>('/admin/openrouter/rate-limits'),
		pool: () => apiFetch<any>('/admin/openrouter/pool'),
		savePool: (config: any) => apiFetch<void>('/admin/openrouter/pool', { method: 'PUT', body: JSON.stringify(config) }),
		sync: () => apiFetch<any>('/admin/openrouter/sync', { method: 'POST' }),
	},

	usage: {
		stats: (filters: Record<string, string> = {}) => apiFetch<any>('/admin/usage?' + new URLSearchParams(filters)),
	},

	providers: {
		list: () => apiFetch<any[]>('/admin/providers'),
		models: (
			providerId: string,
			params: {
				credential_id?: number;
				api_key?: string;
				base_url?: string;
				// Custom-provider live model fetch (provider id "custom").
				models_url?: string;
				api_format?: 'openai_compatible' | 'anthropic_messages';
				auth_header_name?: string;
				auth_header_prefix?: string;
			},
		) =>
			apiFetch<{ models: any[]; message?: string }>(`/admin/providers/${providerId}/models`, {
				method: 'POST',
				body: JSON.stringify(params),
			}),
		validateCustom: (data: {
			api_format: 'openai_compatible' | 'anthropic_messages';
			base_url: string;
			api_key?: string;
			credential_id?: number;
			models_url?: string;
			auth_header_name?: string;
			auth_header_prefix?: string;
		}) =>
			apiFetch<{
				valid: boolean;
				detected_format?: string;
				sample_models?: any[];
				error?: string;
			}>('/admin/custom-providers/validate', {
				method: 'POST',
				body: JSON.stringify(data),
			}),
	},

	routeState: {
		list: () => apiFetch<any[]>('/admin/route-state'),
		reset: (hash: string) => apiFetch<void>(`/admin/route-state/${hash}`, { method: 'DELETE' }),
	},

	modules: {
		list: () => apiFetch<any[]>('/admin/modules'),
		save: (enabled: string[]) => apiFetch<void>('/admin/modules', { method: 'PUT', body: JSON.stringify({ enabled }) }),
	},

	settings: {
		get: () => apiFetch<any>('/admin/settings'),
		save: (data: any) => apiFetch<void>('/admin/settings', { method: 'PUT', body: JSON.stringify(data) }),
	},

	forceProvider: {
		get: () => apiFetch<{ credential_id: number | null; credential: any | null; error?: string }>('/admin/force-provider'),
		set: (credentialId: number) =>
			apiFetch<{ ok: boolean; credential_id: number }>('/admin/force-provider', {
				method: 'PUT',
				body: JSON.stringify({ credential_id: credentialId }),
			}),
		clear: () =>
			apiFetch<{ ok: boolean; credential_id: null }>('/admin/force-provider', {
				method: 'DELETE',
			}),
	},

	github: {
		status: () => apiFetch<{ has_token: boolean; source: 'db' | 'constant' | 'none' }>('/admin/github-token/status'),
		save: (token: string) =>
			apiFetch<{ ok: boolean; source: string }>('/admin/github-token', {
				method: 'PUT',
				body: JSON.stringify({ token }),
			}),
		remove: () =>
			apiFetch<{ ok: boolean; source: string }>('/admin/github-token', {
				method: 'DELETE',
			}),
	},
};
