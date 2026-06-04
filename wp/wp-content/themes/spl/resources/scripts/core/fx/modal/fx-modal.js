// modal/fx-modal.js
import './fx-modal.scss';
import { $ as qs, $$ as qsa, on, off } from '../../dom.js';
import { createWeakStore } from '../../weak.js';

const instanceStore = createWeakStore();

/**
 * FxModal — Lightweight modal wrapper around native <dialog> API.
 *
 * Usage:
 *   const modal = new FxModal();
 *   modal.show('<h2>Hello</h2>', { className: 'my-modal', onOpen: (dialog) => {} });
 *   modal.close();
 */
class FxModal {
	/** @type {HTMLDialogElement|null} */
	#dialog = null;

	/** @type {Function|null} */
	#onClose = null;

	/** @type {Function|null} */
	#handleBackdropClick = null;

	/** @type {Function|null} */
	#handleCloseEvent = null;

	/**
	 * Get or create the reusable <dialog> element.
	 * @returns {HTMLDialogElement}
	 */
	#getDialog() {
		if (this.#dialog) return this.#dialog;

		const dialog = document.createElement('dialog');
		dialog.classList.add('hd-modal');
		dialog.setAttribute('aria-modal', 'true');

		// Inner wrapper for content + close button
		const inner = document.createElement('div');
		inner.classList.add('hd-modal__inner');

		// Close button
		const closeBtn = document.createElement('button');
		closeBtn.type = 'button';
		closeBtn.classList.add('hd-modal__close');
		closeBtn.setAttribute('aria-label', 'Close');
		closeBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
		closeBtn.addEventListener('click', () => this.close());

		// Content container
		const content = document.createElement('div');
		content.classList.add('hd-modal__content');

		inner.append(closeBtn, content);
		dialog.append(inner);
		document.body.append(dialog);

		this.#dialog = dialog;

		// Backdrop click → close (clicks on <dialog> itself, outside .hd-modal__inner)
		this.#handleBackdropClick = (e) => {
			if (e.target === dialog) {
				this.close();
			}
		};
		dialog.addEventListener('click', this.#handleBackdropClick);

		// ESC is handled natively by <dialog>, but we hook the close event for cleanup
		this.#handleCloseEvent = () => this.#cleanup();
		dialog.addEventListener('close', this.#handleCloseEvent);

		return dialog;
	}

	/**
	 * Show modal with content.
	 *
	 * @param {string|HTMLElement} content - HTML string or DOM element
	 * @param {Object} [options]
	 * @param {string} [options.className] - Additional class for the dialog
	 * @param {Function} [options.onOpen] - Callback after dialog is shown (receives dialog element)
	 * @param {Function} [options.onClose] - Callback after dialog is closed
	 */
	show(content, options = {}) {
		const dialog = this.#getDialog();
		const contentEl = dialog.querySelector('.hd-modal__content');

		// Close existing dialog before re-opening (prevents InvalidStateError)
		if (dialog.open) {
			dialog.close();
		}

		// Clear previous content
		contentEl.innerHTML = '';

		// Add custom class
		if (options.className) {
			dialog.dataset.modalClass = options.className;
			dialog.classList.add(options.className);
		}

		// Store onClose callback
		this.#onClose = options.onClose || null;

		// Inject content
		if (typeof content === 'string') {
			contentEl.innerHTML = content;
		} else if (content instanceof HTMLElement) {
			contentEl.append(content);
		}

		// Show as modal (with backdrop)
		dialog.showModal();

		// Fire onOpen after dialog is visible (next frame ensures rendering)
		if (options.onOpen) {
			requestAnimationFrame(() => options.onOpen(dialog));
		}
	}

	/**
	 * Close modal and clean up.
	 */
	close() {
		if (!this.#dialog || !this.#dialog.open) return;
		this.#dialog.close();
	}

	/**
	 * Internal cleanup after dialog closes (called by both close() and ESC).
	 */
	#cleanup() {
		const dialog = this.#dialog;
		if (!dialog) return;

		// Clear content to prevent stale handlers
		const contentEl = dialog.querySelector('.hd-modal__content');
		if (contentEl) {
			contentEl.innerHTML = '';
		}

		// Remove custom class
		const customClass = dialog.dataset.modalClass;
		if (customClass) {
			dialog.classList.remove(customClass);
			delete dialog.dataset.modalClass;
		}

		// Fire onClose callback
		if (this.#onClose) {
			this.#onClose(dialog);
			this.#onClose = null;
		}
	}

	/**
	 * Destroy the modal instance entirely (remove from DOM).
	 */
	destroy() {
		if (!this.#dialog) return;

		if (this.#dialog.open) {
			this.#dialog.close();
		}

		this.#dialog.removeEventListener('click', this.#handleBackdropClick);
		this.#dialog.removeEventListener('close', this.#handleCloseEvent);
		this.#dialog.remove();
		this.#dialog = null;
		this.#onClose = null;
		this.#handleBackdropClick = null;
		this.#handleCloseEvent = null;
	}

	/**
	 * Initialize declarative modal triggers in root
	 * @param {Document|Element} root
	 */
	static initAll(root = document) {
		qsa('[data-fx-modal]', root).forEach((trigger) => {
			if (instanceStore.has(trigger)) return;

			const targetSelector = trigger.dataset.fxModal;
			if (!targetSelector) return;

			const targetEl = qs(targetSelector, document);
			if (!targetEl) return;

			const modal = new FxModal();
			const handler = (e) => {
				e.preventDefault();
				modal.show(targetEl.innerHTML, { className: trigger.dataset.modalClass });
			};

			on(trigger, 'click', handler);
			instanceStore.set(trigger, { modal, handler });
		});
	}

	/**
	 * Destroy declarative instances
	 * @param {Document|Element} root
	 */
	static destroyAll(root = document) {
		qsa('[data-fx-modal]', root).forEach((trigger) => {
			instanceStore.cleanup(trigger, ({ modal, handler }) => {
				off(trigger, 'click', handler);
				modal.destroy();
			});
		});
	}
}

export default FxModal;
