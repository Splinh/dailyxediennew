/**
 * Settings/Playground page — streaming chat playground in SPA.
 */

import type { Page } from '../router';

declare const hdatAdmin: { restUrl: string; nonce: string };

interface ChatMessage {
	role: 'user' | 'assistant';
	content: string;
}

function esc(s: string): string {
	const d = document.createElement('div');
	d.textContent = s;
	return d.innerHTML;
}

// Keep the messages array in the module scope so they persist if the user switches tabs and returns.
const messages: ChatMessage[] = [];

export function createPlaygroundPage(): Page {
	let log: HTMLElement | null = null;
	let form: HTMLFormElement | null = null;
	let input: HTMLTextAreaElement | null = null;
	let modelInput: HTMLInputElement | null = null;
	let sendBtn: HTMLButtonElement | null = null;

	function render(root: HTMLElement): void {
		root.innerHTML = `<div class="hdat-page">
			<div class="playground-header">
				<h2>Playground</h2>
				<div class="playground-model-picker">
					<span class="picker-label">Model:</span>
					<input id="pg-model" type="text" placeholder="auto-route (optional)" autocomplete="off">
				</div>
			</div>

			<div class="playground-console">
				<div id="pg-log" class="pg-log" aria-live="polite"></div>
				<form id="pg-form" class="pg-chat-input-bar">
					<textarea id="pg-input" placeholder="Type a message..." rows="2"></textarea>
					<div class="pg-chat-actions">
						<button type="submit" id="pg-send" class="btn-primary">Send</button>
						<button type="button" id="pg-clear" class="btn-sm">Clear</button>
					</div>
				</form>
			</div>
		</div>`;
	}

	function renderLog(): void {
		if (!log) return;
		log.innerHTML = messages.map((m) => `<div class="pg-msg pg-${m.role}"><strong>${m.role}</strong><div>${esc(m.content)}</div></div>`).join('');
		log.scrollTop = log.scrollHeight;
	}

	async function send(model: string, prompt: string): Promise<void> {
		if (!log) return;
		messages.push({ role: 'user', content: prompt });
		const assistant: ChatMessage = { role: 'assistant', content: '' };
		messages.push(assistant);
		renderLog();

		const res = await fetch(hdatAdmin.restUrl + '/admin/playground/stream', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': hdatAdmin.nonce,
			},
			body: JSON.stringify({
				messages: messages.filter((m) => '' !== m.content).map((m) => ({ role: m.role, content: m.content })),
				model: '' !== model ? model : undefined,
			}),
		});

		if (!res.ok || !res.body) {
			assistant.content = `Error: HTTP ${res.status}`;
			renderLog();
			return;
		}

		const reader = res.body.getReader();
		const decoder = new TextDecoder();
		let buffer = '';

		for (;;) {
			const { done, value } = await reader.read();
			if (done) {
				break;
			}
			buffer += decoder.decode(value, { stream: true });

			let nl: number;
			while ((nl = buffer.indexOf('\n')) !== -1) {
				const line = buffer.slice(0, nl).trim();
				buffer = buffer.slice(nl + 1);

				if (!line.startsWith('data:')) {
					continue;
				}
				const payload = line.slice(5).trim();
				if ('[DONE]' === payload) {
					return;
				}

				try {
					const json = JSON.parse(payload);
					if (json.error) {
						assistant.content += `\n[${json.error.message}]`;
					} else {
						const delta = json.choices?.[0]?.delta?.content;
						if (delta) {
							assistant.content += delta;
						}
					}
					renderLog();
				} catch {
					// Ignore malformed SSE fragments.
				}
			}
		}
	}

	return {
		mount(root: HTMLElement) {
			render(root);

			log = root.querySelector<HTMLElement>('#pg-log')!;
			form = root.querySelector<HTMLFormElement>('#pg-form')!;
			input = root.querySelector<HTMLTextAreaElement>('#pg-input')!;
			modelInput = root.querySelector<HTMLInputElement>('#pg-model')!;
			sendBtn = root.querySelector<HTMLButtonElement>('#pg-send')!;

			// Show initial log if there are existing messages
			renderLog();

			form.addEventListener('submit', async (e) => {
				e.preventDefault();
				if (!input || !modelInput || !sendBtn) return;
				const prompt = input.value.trim();
				if ('' === prompt) {
					return;
				}
				input.value = '';
				sendBtn.disabled = true;
				try {
					await send(modelInput.value.trim(), prompt);
				} finally {
					sendBtn.disabled = false;
				}
			});

			root.querySelector('#pg-clear')?.addEventListener('click', () => {
				messages.length = 0;
				renderLog();
			});
		},
	};
}
