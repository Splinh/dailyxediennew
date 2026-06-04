/**
 * Provider name badge component.
 */

interface ProviderBadgeOptions {
	provider: string;
	customLabel?: string;
	isCustomProvider?: boolean;
}

/**
 * Render a provider badge with appropriate styling.
 * Custom providers get special treatment with a custom icon and label.
 */
export function renderProviderBadge(
	providerOrOptions: string | ProviderBadgeOptions,
	customLabel?: string
): string {
	let provider: string;
	let label: string;
	let isCustom: boolean;

	// Handle both string and options object for backward compatibility
	if (typeof providerOrOptions === 'string') {
		provider = providerOrOptions;
		label = customLabel || provider;
		isCustom = isCustomProvider(provider);
	} else {
		provider = providerOrOptions.provider;
		label = providerOrOptions.customLabel || provider;
		isCustom = providerOrOptions.isCustomProvider || isCustomProvider(provider);
	}

	// Extract custom label from provider ID if it follows 'custom:' pattern
	if (isCustom && !customLabel && provider.startsWith('custom:')) {
		const extractedLabel = provider.substring(7); // Remove 'custom:' prefix
		if (extractedLabel) {
			label = extractedLabel;
		}
	}

	const badgeClass = isCustom ? 'badge badge-custom' : 'badge badge-indigo';
	const icon = isCustom ? renderCustomIcon() : '';

	return `<span class="${badgeClass}">${icon}${escapeHtml(label)}</span>`;
}

/**
 * Render custom provider icon (puzzle piece).
 */
function renderCustomIcon(): string {
	return `<svg class="inline-block w-3 h-3 mr-1 -mt-0.5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
		<path d="M10 3.5a1.5 1.5 0 013 0V4a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-.5a1.5 1.5 0 000 3h.5a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-.5a1.5 1.5 0 00-3 0v.5a1 1 0 01-1 1H6a1 1 0 01-1-1v-3a1 1 0 00-1-1h-.5a1.5 1.5 0 010-3H4a1 1 0 001-1V6a1 1 0 011-1h3a1 1 0 001-1v-.5z"/>
	</svg>`;
}

/**
 * Get provider display name from provider ID.
 * Handles built-in providers and custom providers.
 */
export function getProviderDisplayName(provider: string, customLabel?: string): string {
	if (isCustomProvider(provider)) {
		return customLabel || (provider.startsWith('custom:') ? provider.substring(7) : '') || 'Custom Provider';
	}

	// Map of built-in provider IDs to display names
	const providerNames: Record<string, string> = {
		'openai': 'OpenAI',
		'anthropic': 'Anthropic',
		'google': 'Google',
		'cohere': 'Cohere',
		'mistral': 'Mistral',
		'groq': 'Groq',
		'perplexity': 'Perplexity',
		'together': 'Together AI',
		'fireworks': 'Fireworks',
		'deepseek': 'DeepSeek',
		'xai': 'xAI',
	};

	return providerNames[provider.toLowerCase()] || provider;
}

/**
 * Check if a provider ID represents a custom provider.
 */
export function isCustomProvider(provider: string): boolean {
	return provider === 'custom' || provider.startsWith('custom:');
}

function escapeHtml(str: string): string {
	const div = document.createElement('div');
	div.textContent = str;
	return div.innerHTML;
}
