// core/modules/form/form.js
// Lazy-loaded form handler — auto-discovers [data-form] elements.

import './form.scss';

const SUBMIT_URL = `${window.hdConfig?.restApiUrl || '/wp-json/hd/v1/'}form/submit`;
const NONCE = window.hdConfig?.restToken || '';

const LEGACY_HONEYPOT_FIELD = '_hp_field';
const HONEYPOT_META_KEYS = new Set(['_hp_name', '_hp_ts', '_hp_sig']);

function honeypotConfig(form) {
	const config = window.hdConfig?.form?.honeypot || {};
	const field = form.dataset.hpField || config.field || LEGACY_HONEYPOT_FIELD;
	const timestamp = form.dataset.hpTs || String(config.timestamp || '');
	const signature = form.dataset.hpSig || config.signature || '';

	return { field, timestamp, signature };
}

/**
 * Parse UTM parameters from the current URL.
 *
 * @returns {Object}
 */
function getUtmParams() {
	const params = new URLSearchParams(window.location.search);
	const keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
	const utm = {};

	keys.forEach((key) => {
		const value = params.get(key);
		if (value) {
			const subKey = key.replace('utm_', '');
			if (subKey !== '__proto__' && subKey !== 'constructor' && subKey !== 'prototype') {
				Reflect.set(utm, subKey, value);
			}
		}
	});

	return utm;
}

/**
 * Collect form data from a [data-form] element.
 *
 * @param {HTMLFormElement} form
 * @returns {Object}
 */
function collectFormData(form) {
	const formData = new FormData(form);
	const formType = form.dataset.form || 'generic';
	const formId = form.dataset.formId || form.id || `${formType}-${Date.now()}`;
	const captchaType = form.dataset.captcha || 'none';

	// Core fields.
	const name = formData.get('name') || '';
	const email = formData.get('email') || '';
	const phone = formData.get('phone') || '';
	const message = formData.get('message') || '';

	// Extra fields + labels from data-title attributes.
	const fields = {};
	const fieldLabels = {};
	const honeypot = honeypotConfig(form);
	const coreKeys = new Set(['name', 'email', 'phone', 'message', LEGACY_HONEYPOT_FIELD, honeypot.field, ...HONEYPOT_META_KEYS]);

	// Collect CAPTCHA tokens.
	const captchaKeys = new Set(['cf-turnstile-response', 'g-recaptcha-response', 'h-captcha-response']);

	let captchaToken = '';

	for (const [key, value] of formData.entries()) {
		if (captchaKeys.has(key)) {
			captchaToken = value;
			continue;
		}

		if (coreKeys.has(key)) continue;

		if (key !== '__proto__' && key !== 'constructor' && key !== 'prototype') {
			Reflect.set(fields, key, value);

			// Read data-title from the input element.
			const input = form.querySelector(`[name="${key}"]`);
			if (input?.dataset?.title) {
				Reflect.set(fieldLabels, key, input.dataset.title);
			}
		}
	}

	// Also read labels for core fields.
	['name', 'email', 'phone', 'message'].forEach((key) => {
		const input = form.querySelector(`[name="${key}"]`);
		if (input?.dataset?.title) {
			Reflect.set(fieldLabels, key, input.dataset.title);
		}
	});

	if (message) {
		fields.message = message;
	}

	return {
		form_type: formType,
		form_id: formId,
		name,
		email,
		phone,
		fields,
		field_labels: fieldLabels,
		utm: getUtmParams(),
		captcha_type: captchaType,
		captcha_token: captchaToken,
		page_url: window.location.href,
		_render_ts: form.dataset.renderTs || String(Date.now()),
		_hp_name: honeypot.field,
		_hp_ts: honeypot.timestamp,
		_hp_sig: honeypot.signature,
		[honeypot.field]: formData.get(honeypot.field) || '',
	};
}

/**
 * Validate form fields client-side.
 *
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
function validateForm(form) {
	let valid = true;

	// Clear previous errors.
	form.querySelectorAll('.hd-form-error').forEach((el) => el.remove());
	form.querySelectorAll('.hd-form-field--error').forEach((el) => {
		el.classList.remove('hd-form-field--error');
	});

	// Validate required fields.
	form.querySelectorAll('[required]').forEach((input) => {
		if (!input.value.trim()) {
			valid = false;
			input.classList.add('hd-form-field--error');

			const label = input.dataset.title || input.name;
			const error = document.createElement('span');
			error.className = 'hd-form-error';
			error.textContent = `${label} is required.`;
			input.parentNode.insertBefore(error, input.nextSibling);
		}
	});

	// Email format check.
	const emailInput = form.querySelector('[type="email"]');
	if (emailInput?.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
		valid = false;
		emailInput.classList.add('hd-form-field--error');

		const error = document.createElement('span');
		error.className = 'hd-form-error';
		error.textContent = 'Invalid email format.';
		emailInput.parentNode.insertBefore(error, emailInput.nextSibling);
	}

	return valid;
}

/**
 * Submit form data to REST API.
 *
 * @param {HTMLFormElement} form
 * @param {Object} payload
 */
async function submitForm(form, payload) {
	const submitBtn = form.querySelector('[type="submit"]');
	const originalText = submitBtn?.textContent || '';

	try {
		// Loading state.
		form.classList.add('hd-form--loading');
		if (submitBtn) {
			submitBtn.disabled = true;
			submitBtn.textContent = submitBtn.dataset.loading || 'Sending...';
		}

		// Detect file inputs with actual files selected.
		const fileInputs = [...form.querySelectorAll('input[type="file"]')].filter((i) => i.files?.length);
		const hasFiles = fileInputs.length > 0;

		let fetchOptions;

		if (hasFiles) {
			// Multipart: send payload as JSON string + raw file fields.
			const fd = new FormData();
			fd.append('payload', JSON.stringify(payload));

			fileInputs.forEach((input) => {
				[...input.files].forEach((file) => fd.append(input.name, file));
			});

			fetchOptions = {
				method: 'POST',
				headers: { 'X-WP-Nonce': NONCE },
				body: fd,
			};
		} else {
			fetchOptions = {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': NONCE,
				},
				body: JSON.stringify(payload),
			};
		}

		const response = await fetch(SUBMIT_URL, fetchOptions);

		const result = await response.json();

		if (result.success) {
			// Dispatch tracking event (bubbles to document for listeners).
			form.dispatchEvent(
				new CustomEvent('hd:form:success', {
					bubbles: true,
					detail: {
						formType: payload.form_type,
						formId: payload.form_id,
						pageUrl: payload.page_url,
						tracking: JSON.parse(form.dataset.tracking || '{}'),
					},
				}),
			);

			showMessage(form, 'success', result.message || 'Thank you!');
			form.reset();
			form.dataset.renderTs = String(Date.now());

			// Reset CAPTCHA widgets if present.
			resetCaptcha(form);
		} else {
			showMessage(form, 'error', result.message || 'An error occurred.');
		}
	} catch {
		showMessage(form, 'error', 'Network error. Please try again.');
	} finally {
		form.classList.remove('hd-form--loading');
		if (submitBtn) {
			submitBtn.disabled = false;
			submitBtn.textContent = originalText;
		}
	}
}

/**
 * Show success/error message.
 *
 * @param {HTMLFormElement} form
 * @param {'success'|'error'} type
 * @param {string} text
 */
function showMessage(form, type, text) {
	// Remove existing messages.
	form.querySelectorAll('.hd-form-message').forEach((el) => el.remove());

	const msg = document.createElement('div');
	msg.className = `hd-form-message hd-form-message--${type}`;
	msg.textContent = text;
	msg.setAttribute('role', 'alert');

	form.prepend(msg);

	// Auto-dismiss after 8s.
	setTimeout(() => msg.remove(), 8000);
}

/**
 * Inject honeypot hidden field.
 *
 * @param {HTMLFormElement} form
 */
function injectHoneypot(form) {
	const honeypot = honeypotConfig(form);
	if (form.querySelector(`[name="${honeypot.field}"]`)) return;

	const hp = document.createElement('input');
	hp.type = 'text';
	hp.name = honeypot.field;
	hp.tabIndex = -1;
	hp.autocomplete = 'off';
	hp.setAttribute('aria-hidden', 'true');
	hp.style.cssText = 'position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;opacity:0;';
	form.appendChild(hp);

	form.dataset.hpField = honeypot.field;
	form.dataset.hpTs = honeypot.timestamp;
	form.dataset.hpSig = honeypot.signature;
	form.dataset.renderTs = String(Date.now());
}

/**
 * Reset CAPTCHA widget after successful submission.
 *
 * @param {HTMLFormElement} form
 */
function resetCaptcha(form) {
	// Turnstile
	const turnstile = form.querySelector('.cf-turnstile');
	if (turnstile && window.turnstile) {
		const widgetId = turnstile.dataset.widgetId;
		if (widgetId) window.turnstile.reset(widgetId);
	}

	// reCAPTCHA v2
	const recaptcha = form.querySelector('.g-recaptcha');
	if (recaptcha && window.grecaptcha) {
		try {
			window.grecaptcha.reset();
		} catch {
			// Ignore if widget not found.
		}
	}
}

/**
 * Initialize a single form element.
 *
 * @param {HTMLFormElement} form
 */
function initForm(form) {
	if (form._hdFormInited) return;

	// Inject honeypot.
	injectHoneypot(form);

	const handler = (e) => {
		e.preventDefault();

		if (!validateForm(form)) return;

		const payload = collectFormData(form);
		submitForm(form, payload);
	};

	form._hdSubmitHandler = handler;
	form.addEventListener('submit', handler);

	// Set flag last — if anything above throws, re-init is still possible.
	form._hdFormInited = true;
}

/**
 * Destroy form handler.
 *
 * @param {HTMLFormElement} form
 */
function destroyForm(form) {
	if (form._hdSubmitHandler) {
		form.removeEventListener('submit', form._hdSubmitHandler);
		form._hdSubmitHandler = null;
	}

	form._hdFormInited = false;

	// Remove honeypot.
	const hp = form.querySelector(`[name="${honeypotConfig(form).field}"]`);
	if (hp) hp.remove();

	// Remove messages.
	form.querySelectorAll('.hd-form-message, .hd-form-error').forEach((el) => el.remove());
}

// -- Module API (createLoader compatible) --

export default {
	initAll(root = document) {
		root.querySelectorAll('[data-form]').forEach(initForm);
	},

	destroyAll(root = document) {
		root.querySelectorAll('[data-form]').forEach(destroyForm);
	},
};
