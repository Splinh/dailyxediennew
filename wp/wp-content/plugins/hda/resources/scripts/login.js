/**
 * Login Page Scripts
 *
 * Entry point — wp-login.php (base styling + OTP handler).
 *
 * Features:
 * - Login page SCSS
 * - Remove privacy policy link
 * - Numeric-only OTP input filter
 * - Auto-submit when OTP is complete
 * - Countdown timer with expiration handling
 * - Double-submit prevention
 */
import '../styles/login.scss';

document.addEventListener('DOMContentLoaded', function () {
	// Remove privacy policy link.
	const login = document.getElementById('login');
	if (login) {
		login.querySelector('.privacy-policy-page-link')?.remove();
	}

	// ── OTP Login Handler ───────────────────────────

	const loginform = document.querySelector('#loginform');
	if (!loginform) return;

	loginform.classList.add('otp-loginform');

	// Prevent password managers from auto-filling.
	const pwdInput = document.querySelector("input[name='pwd']");
	if (pwdInput) {
		pwdInput.setAttribute('autocomplete', 'off');
		pwdInput.setAttribute('readonly', true);
		setTimeout(() => pwdInput.removeAttribute('readonly'), 500);
	}

	// Remove "Remember me" checkbox only when OTP is active.
	if (typeof hdaLogin !== 'undefined' && hdaLogin.hideRememberMe) {
		const rememberBox = document.querySelector('#rememberme');
		if (rememberBox) {
			rememberBox.checked = false;
			rememberBox.closest('p')?.remove();
		}
	}

	// Get submit button safely.
	const submitBtn = loginform.querySelector('input[type="submit"], button[type="submit"]');

	// Track if form is already submitting (prevent double submit).
	let isSubmitting = false;

	const submitForm = () => {
		if (isSubmitting || (submitBtn && submitBtn.disabled)) return;
		isSubmitting = true;
		if (submitBtn) submitBtn.disabled = true;

		if (typeof loginform.requestSubmit === 'function') {
			loginform.requestSubmit();
		} else {
			HTMLFormElement.prototype.submit.call(loginform);
		}
	};

	// OTP Input Handler.
	const inputEl = document.querySelector('input.authcode[inputmode="numeric"]');
	const expectedLength = Number(inputEl?.dataset.digits) || 0;

	if (inputEl) {
		inputEl.addEventListener('input', function () {
			let value = this.value.replace(/[^0-9]/g, '');
			this.value = value;
			if (expectedLength && value.length === expectedLength) {
				setTimeout(submitForm, 50);
			}
		});

		inputEl.addEventListener('paste', function (e) {
			e.preventDefault();
			const pastedText = (e.clipboardData || window.clipboardData).getData('text');
			this.value = pastedText.replace(/[^0-9]/g, '').slice(0, expectedLength || 20);
			this.dispatchEvent(new Event('input', { bubbles: true }));
		});
	}

	// Countdown Timer.
	const timer = document.querySelector('#countdown');
	if (!timer) return;

	let remaining = Number(timer.dataset.time) || 0;
	let intervalId = null;

	const render = () => {
		const mm = String(Math.floor(remaining / 60)).padStart(2, '0');
		const ss = String(remaining % 60).padStart(2, '0');
		timer.textContent = `${mm}:${ss}`;
	};

	const onExpire = () => {
		if (submitBtn) submitBtn.disabled = true;
		if (inputEl) inputEl.disabled = true;
		timer.classList.add('expired');
	};

	if (remaining <= 0) {
		render();
		onExpire();
	} else {
		render();
		intervalId = setInterval(() => {
			remaining--;
			render();
			if (remaining <= 0) {
				clearInterval(intervalId);
				intervalId = null;
				onExpire();
			}
		}, 1000);
	}

	window.addEventListener('beforeunload', () => {
		if (intervalId) {
			clearInterval(intervalId);
			intervalId = null;
		}
	});
});
