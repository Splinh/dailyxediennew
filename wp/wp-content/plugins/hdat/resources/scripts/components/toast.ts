/**
 * Toast notification system.
 */

let container: HTMLElement | null = null;

function ensure(): HTMLElement {
	if (!container) {
		container = document.createElement('div');
		container.className = 'hdat-toast-container';
		document.body.appendChild(container);
	}
	return container;
}

function show(message: string, type: 'success' | 'error'): void {
	const el = document.createElement('div');
	el.className = `hdat-toast hdat-toast-${type}`;
	el.textContent = message;

	ensure().appendChild(el);
	setTimeout(() => el.remove(), 3000);
}

export const toast = {
	success: (msg: string) => show(msg, 'success'),
	error: (msg: string) => show(msg, 'error'),
};
