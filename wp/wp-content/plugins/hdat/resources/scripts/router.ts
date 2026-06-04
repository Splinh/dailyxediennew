/**
 * Hash-based SPA router for HDAT admin.
 */

export interface Page {
	mount(el: HTMLElement): void | Promise<void>;
	unmount?(): void;
}

class Router {
	private routes = new Map<string, () => Page>();
	private current?: Page;

	register(hash: string, factory: () => Page): void {
		this.routes.set(hash, factory);
	}

	init(): void {
		window.addEventListener('hashchange', () => this.navigate());
		this.navigate();
	}

	private navigate(): void {
		const hash = location.hash || '#/dashboard';
		const factory = this.routes.get(hash);
		if (!factory) {
			return;
		}

		this.current?.unmount?.();

		const el = document.getElementById('hdat-content')!;
		el.innerHTML = '';

		this.current = factory();
		this.current.mount(el);

		// Update active nav link.
		document.querySelectorAll('#hdat-nav a').forEach((a) => a.classList.toggle('active', a.getAttribute('href') === hash));
	}
}

export const router = new Router();
